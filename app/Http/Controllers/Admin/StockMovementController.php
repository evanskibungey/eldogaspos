<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockMovementController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'creator'])
            ->when($request->product_id, function($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->type, function($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->reference_type, function($q) use ($request) {
                return $q->where('reference_type', $request->reference_type);
            })
            ->when($request->date_from, function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            });

        $movements = $query->latest()->paginate(15);

        return view('admin.stock.movements.index', compact('movements'));
    }

    /**
     * Display low stock products.
     */
    public function lowStock()
    {
        $lowStockProducts = Product::whereRaw('stock <= min_stock')
            ->with(['category', 'stockMovements' => function($query) {
                $query->latest()->take(5);
            }])
            ->paginate(15);

        return view('admin.stock.low-stock', compact('lowStockProducts'));
    }

    /**
     * Adjust stock levels.
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'type' => 'required|in:in,out',
            'notes' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:stock_movements,serial_number',
            'unit_price' => 'nullable|numeric|min:0'
        ]);

        try {
            // Delegated to StockService: the previous read-modify-write took no
            // row lock, so two concurrent adjustments could lose one another.
            // The service locks the row, applies the delta and writes the ledger
            // entry in a single transaction, and refuses to drop stock below the
            // quantity reserved for cylinder collections awaiting pickup.
            $result = $this->stockService->adjustStockBy(
                (int) $request->product_id,
                $request->type === 'in' ? (int) $request->quantity : -(int) $request->quantity,
                $request->notes ?? 'Manual stock adjustment',
                'manual_adjustment',
                $request->serial_number,
                $request->unit_price !== null ? (float) $request->unit_price : null
            );

            return redirect()->back()->with(
                'success',
                "Stock adjusted: {$result['stock_before']} -> {$result['stock_after']} "
                    . "({$result['available_after']} available to sell)"
            );
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error adjusting stock: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Export stock movements.
     */
    public function export(Request $request)
    {
        $query = StockMovement::with(['product', 'creator'])
            ->when($request->product_id, function($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->type, function($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->reference_type, function($q) use ($request) {
                return $q->where('reference_type', $request->reference_type);
            })
            ->when($request->date_from, function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            });

        $movements = $query->latest()->get();

        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=stock_movements.csv',
        ];

        $columns = [
            'Date', 'Product', 'Type', 'Quantity', 'Serial Number', 
            'Reference Type', 'Notes', 'Updated By'
        ];

        $callback = function() use ($movements, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($movements as $movement) {
                fputcsv($file, [
                    $movement->created_at->format('Y-m-d H:i:s'),
                    $movement->product->name,
                    ucfirst($movement->type),
                    $movement->quantity,
                    $movement->serial_number ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $movement->reference_type)),
                    $movement->notes ?? 'N/A',
                    $movement->creator->name ?? 'System'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get stock movement statistics.
     */
    public function getStats()
    {
        $stats = [
            'total_in' => StockMovement::where('type', 'in')->sum('quantity'),
            'total_out' => StockMovement::where('type', 'out')->sum('quantity'),
            'low_stock_count' => Product::whereRaw('stock <= min_stock')->count(),
            'recent_movements' => StockMovement::with(['product', 'creator'])
                ->latest()
                ->take(5)
                ->get()
        ];

        return response()->json($stats);
    }
}