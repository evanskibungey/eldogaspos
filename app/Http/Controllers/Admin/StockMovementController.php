<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockMovementController extends Controller
{
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
            DB::transaction(function() use ($request) {
                $product = Product::findOrFail($request->product_id);
                
                // Calculate new stock level
                $newStock = $request->type === 'in' 
                    ? $product->stock + $request->quantity
                    : $product->stock - $request->quantity;

                // Validate new stock level
                if ($newStock < 0) {
                    throw new \Exception('Stock cannot be negative');
                }

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type' => $request->type,
                    'quantity' => $request->quantity,
                    'unit_price' => $request->unit_price,
                    'reference_type' => 'manual_adjustment',
                    'notes' => $request->notes,
                    'serial_number' => $request->serial_number,
                    'created_by' => Auth::id()
                ]);

                // Update product stock
                $product->update(['stock' => $newStock]);
            });

            return redirect()->back()->with('success', 'Stock adjusted successfully');
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