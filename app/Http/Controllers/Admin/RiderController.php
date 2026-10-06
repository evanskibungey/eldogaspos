<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\RiderAllocation;
use App\Models\RiderAllocationItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SmsLog;
use App\Services\OrderNumberService;
use App\Services\ReferenceNumberService;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\RiderMessage;
use App\Services\Sms\SmsService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Riders and the cylinders they are carrying.
 *
 * An allocation is the same movement as a cylinder advance collection - stock
 * leaves now, empties return later - so it reuses the same StockService calls
 * and the same reserved/committed/released vocabulary rather than inventing a
 * parallel set. Money is recorded only on completion: cylinders on a bike may
 * still come back.
 */
class RiderController extends Controller
{
    protected StockService $stockService;
    protected ReferenceNumberService $referenceNumbers;
    protected OrderNumberService $orderNumbers;

    public function __construct(
        StockService $stockService,
        ReferenceNumberService $referenceNumbers,
        OrderNumberService $orderNumbers
    ) {
        $this->stockService = $stockService;
        $this->referenceNumbers = $referenceNumbers;
        $this->orderNumbers = $orderNumbers;
    }

    /*
    |--------------------------------------------------------------------------
    | Rider cylinder management
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $riders = Rider::assignable()
            ->orderBy('name')
            ->get();

        // Listed separately so they can be reactivated, but kept out of the
        // counts: an inactive rider is neither available nor out.
        $inactiveRiders = Rider::where('status', Rider::STATUS_INACTIVE)
            ->orderBy('name')
            ->get();

        $allocations = RiderAllocation::with(['rider', 'items.product', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when(!$request->filled('status'), fn ($q) => $q->pending())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'riders_out' => $riders->filter->isOut()->count(),
            'riders_available' => $riders->reject->isOut()->count(),
            'open_allocations' => RiderAllocation::pending()->count(),
            'cylinders_out' => (int) RiderAllocationItem::whereIn(
                'rider_allocation_id',
                RiderAllocation::pending()->select('id')
            )->sum('quantity'),
        ];

        return view('admin.riders.index', compact('riders', 'inactiveRiders', 'allocations', 'stats'));
    }

    public function show(Rider $rider)
    {
        $rider->loadCount('activeAllocations');

        $allocations = $rider->allocations()
            ->with(['items.product', 'user', 'sale'])
            ->latest()
            ->paginate(20);

        return view('admin.riders.show', compact('rider', 'allocations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'national_id' => 'nullable|string|max:50',
        ]);

        // Matched across spellings, like customers: the same rider saved as
        // 0712... and +254712... would otherwise become two people with two
        // separate allocation histories.
        $existing = Rider::whereIn('phone', PhoneNumber::variants($validated['phone']))->first();

        if ($existing) {
            $existing->update(['status' => Rider::STATUS_ACTIVE]);

            return back()->with('success', "{$existing->name} is already registered on that number.");
        }

        $rider = Rider::create($validated + ['status' => Rider::STATUS_ACTIVE]);

        return back()->with('success', "{$rider->name} added.");
    }

    public function toggleStatus(Rider $rider)
    {
        if ($rider->activeAllocations()->exists()) {
            return back()->withErrors([
                'error' => "{$rider->name} is still holding cylinders. Complete or cancel those first.",
            ]);
        }

        $rider->update([
            'status' => $rider->status === Rider::STATUS_ACTIVE
                ? Rider::STATUS_INACTIVE
                : Rider::STATUS_ACTIVE,
        ]);

        return back()->with('success', "{$rider->name} is now {$rider->status}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Used by the POS pick-up button
    |--------------------------------------------------------------------------
    */

    /**
     * Riders the POS may assign to, with whether each is free.
     *
     * Availability is computed from open allocations rather than stored, so it
     * cannot disagree with reality.
     */
    public function available()
    {
        $riders = Rider::assignable()->orderBy('name')->get();

        return response()->json(
            $riders->map(fn (Rider $rider) => [
                'id' => $rider->id,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'availability' => $rider->availability,
                'is_out' => $rider->isOut(),
                'open_allocations' => $rider->active_allocations_count ?? 0,
            ])->values()
        );
    }

    /**
     * Book cylinders out to a rider. One click, no confirmation.
     */
    public function allocate(Request $request)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_phone' => 'nullable|string|max:20',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        // Assignment happens on a single click with no confirm step, so the
        // same guard the quick-sale uses applies here: a repeated key returns
        // the allocation already made instead of booking the cylinders out
        // twice.
        if ($request->filled('idempotency_key')) {
            $existing = RiderAllocation::where('idempotency_key', $request->input('idempotency_key'))->first();

            if ($existing) {
                return response()->json($this->allocationPayload($existing, true));
            }
        }

        $rider = Rider::active()->find($validated['rider_id']);

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'That rider is no longer active.',
            ], 422);
        }

        // Optional, and only rejected when it was actually typed. Leaving it
        // blank is the ordinary case - the till often books cylinders out
        // before anyone has the customer on the phone. A number that IS given
        // has to be dialable, or the rider gets a text they cannot act on.
        $customerPhone = null;
        $typedPhone = trim((string) $request->input('customer_phone', ''));

        if ($typedPhone !== '') {
            $customerPhone = PhoneNumber::normalise($typedPhone);

            if ($customerPhone === null) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'invalid_customer_phone',
                    'message' => "\"{$typedPhone}\" is not a usable Kenyan mobile number.",
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $allocation = RiderAllocation::create([
                'reference_number' => $this->referenceNumbers->generateRiderReference(),
                'idempotency_key' => $request->input('idempotency_key'),
                'rider_id' => $rider->id,
                'customer_phone' => $customerPhone,
                'user_id' => Auth::id(),
                'status' => RiderAllocation::STATUS_PENDING,
                'stock_status' => RiderAllocation::STOCK_RESERVED,
                'total_amount' => 0,
                'allocated_at' => now(),
            ]);

            $stockItems = collect($validated['items'])->map(fn ($line) => [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
                // Resolved from the locked product row by the service.
                'unit_price' => null,
                'serial_number' => null,
            ])->all();

            // Reserved, not deducted: the cylinders are spoken for and must not
            // be sellable, but nothing has been sold yet. Throws if short, and
            // the catch rolls everything back.
            $results = $this->stockService->reserveMultipleStock(
                $stockItems,
                'rider_allocation',
                $allocation->id
            );

            $total = 0.0;

            foreach ($stockItems as $line) {
                $productId = $line['product_id'];
                $product = $results[$productId]['product'];
                $unitPrice = (float) $product->price;
                $subtotal = round($unitPrice * $line['quantity'], 2);
                $total += $subtotal;

                RiderAllocationItem::create([
                    'rider_allocation_id' => $allocation->id,
                    'product_id' => $productId,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $allocation->update(['total_amount' => round($total, 2)]);

            DB::commit();
        } catch (\App\Exceptions\InsufficientStockException $e) {
            DB::rollBack();

            return response()->json($e->toArray(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rider allocation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not allocate to this rider: ' . $e->getMessage(),
            ], 422);
        }

        // After the commit: the cylinders are booked out whether or not the
        // rider's phone is reachable.
        $allocation->load('items.product', 'rider');
        $this->notifyRider(
            $allocation,
            RiderMessage::allocated($allocation),
            SmsLog::PURPOSE_RIDER_ALLOCATED
        );

        return response()->json($this->allocationPayload($allocation->fresh(['items.product', 'rider']), false));
    }

    /*
    |--------------------------------------------------------------------------
    | Completion
    |--------------------------------------------------------------------------
    */

    /**
     * The rider is back and the empties are in. Commit the stock, record the
     * sale, close the allocation and tell the rider.
     */
    public function complete(Request $request, RiderAllocation $allocation)
    {
        if (!$allocation->isPending()) {
            return $this->respond($request, false, 'That allocation is already ' . $allocation->status . '.');
        }

        try {
            DB::beginTransaction();

            $allocation->load('items.product');

            // Guarded on stock_status so a repeated completion can never deduct
            // the same cylinders twice.
            $orderNumber = null;

            if ($allocation->hasReservedStock()) {
                $results = $this->stockService->commitReservedStock(
                    $allocation->stockLines(),
                    'rider_delivery',
                    $allocation->id,
                    "Delivered by rider - allocation #{$allocation->id} "
                        . "(Ref: {$allocation->reference_number}, Rider: {$allocation->rider->name}, "
                        . 'Product: {product_name}, Qty: {quantity})'
                );

                $orderNumber = $this->orderNumbers->forCollection(
                    $results,
                    $allocation->productIdsInOrder()
                );
            }

            $sale = $this->recordSaleFor($allocation, $orderNumber);

            $allocation->update([
                'status' => RiderAllocation::STATUS_COMPLETED,
                'stock_status' => RiderAllocation::STOCK_COMMITTED,
                'sale_id' => $sale->id,
                'completed_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rider allocation completion failed', [
                'allocation_id' => $allocation->id,
                'error' => $e->getMessage(),
            ]);

            return $this->respond($request, false, 'Could not complete: ' . $e->getMessage());
        }

        $this->sendCompletionNotice($allocation->fresh(['rider', 'items.product']));

        return $this->respond($request, true, "Order #{$allocation->reference_number} completed.");
    }

    public function cancel(Request $request, RiderAllocation $allocation)
    {
        if (!$allocation->isPending()) {
            return $this->respond($request, false, 'Only an open allocation can be cancelled.');
        }

        try {
            DB::beginTransaction();

            $allocation->load('items');

            if ($allocation->hasReservedStock()) {
                $this->stockService->releaseMultipleReservations(
                    $allocation->stockLines(),
                    'rider_allocation_cancelled',
                    $allocation->id
                );
            }

            $allocation->update([
                'status' => RiderAllocation::STATUS_CANCELLED,
                'stock_status' => RiderAllocation::STOCK_RELEASED,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->respond($request, false, 'Could not cancel: ' . $e->getMessage());
        }

        return $this->respond($request, true, 'Allocation cancelled and stock released.');
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Write the sale the completed allocation represents.
     *
     * Stock was already moved by commitReservedStock, so this deliberately does
     * NOT go through StockService again - doing so would deduct the same
     * cylinders a second time. The movement is filed under the allocation and
     * the sale is reachable from it via sale_id.
     */
    private function recordSaleFor(RiderAllocation $allocation, ?int $orderNumber): Sale
    {
        $walkIn = Customer::firstOrCreate(
            ['phone' => PhoneNumber::WALK_IN],
            ['name' => 'Walk-in Customer', 'status' => 'active']
        );

        $sale = Sale::create([
            'user_id' => Auth::id() ?? $allocation->user_id,
            'customer_id' => $walkIn->id,
            'receipt_number' => $this->referenceNumbers->generateReceiptNumber(),
            'order_number' => $orderNumber,
            'total_amount' => $allocation->total_amount,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => Sale::STATUS_COMPLETED,
            'notes' => "Delivered by rider {$allocation->rider->name} (Ref: {$allocation->reference_number})",
        ]);

        foreach ($allocation->items as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'order_number' => $orderNumber,
                'serial_number' => null,
            ]);
        }

        return $sale;
    }

    /**
     * Tell the rider the order is closed - once.
     *
     * The timestamp is what makes it once: completing an already-completed
     * allocation is refused earlier, but a retried request or a double-submit
     * must not buy a second message.
     */
    private function sendCompletionNotice(RiderAllocation $allocation): void
    {
        if ($allocation->completionAlreadyNotified()) {
            Log::info('Rider completion SMS suppressed - already sent', [
                'allocation_id' => $allocation->id,
            ]);

            return;
        }

        $sent = $this->notifyRider(
            $allocation,
            RiderMessage::completed($allocation),
            SmsLog::PURPOSE_RIDER_COMPLETED
        );

        if ($sent) {
            $allocation->forceFill(['completion_notified_at' => now()])->save();
        }
    }

    /**
     * Queue a message to the rider. Failures are logged, never surfaced: by the
     * time this runs the stock and the sale are already committed.
     */
    private function notifyRider(RiderAllocation $allocation, string $message, string $purpose): bool
    {
        try {
            $log = app(SmsService::class)->queue(
                $allocation->rider->phone,
                $message,
                $purpose,
                [
                    'reference_type' => 'rider_allocation',
                    'reference_id' => $allocation->id,
                ]
            );

            return $log !== null;
        } catch (\Throwable $e) {
            Log::error('Rider SMS failed to queue', [
                'allocation_id' => $allocation->id,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function allocationPayload(RiderAllocation $allocation, bool $duplicate): array
    {
        $allocation->loadMissing(['rider', 'items.product']);

        return [
            'success' => true,
            'duplicate' => $duplicate,
            'reference_number' => $allocation->reference_number,
            'allocation_id' => $allocation->id,
            'rider' => [
                'id' => $allocation->rider->id,
                'name' => $allocation->rider->name,
            ],
            // Dialable form for the screen, same as the rider is texted.
            'customer_phone' => PhoneNumber::local($allocation->customer_phone),
            'items' => $allocation->items->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => optional($item->product)->name,
                'quantity' => $item->quantity,
                'stock_after' => optional($item->product)->available_stock,
            ])->values(),
            'message' => $duplicate
                ? 'Already allocated'
                : "Allocated to {$allocation->rider->name}",
        ];
    }

    private function respond(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('success', $message)
            : back()->withErrors(['error' => $message]);
    }
}
