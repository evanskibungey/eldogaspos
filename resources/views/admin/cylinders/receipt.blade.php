<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $cylinder->reference_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; padding: 20px; max-width: 400px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .company-info { font-size: 12px; }
        .section { margin: 15px 0; }
        .section-title { font-weight: bold; font-size: 14px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 3px; }
        .row { display: flex; justify-between; margin: 5px 0; font-size: 13px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; padding: 5px 0; font-size: 12px; }
        .items-table td { padding: 5px 0; font-size: 12px; }
        .items-table .item-row { border-bottom: 1px dashed #ccc; }
        .totals { border-top: 2px solid #000; margin-top: 15px; padding-top: 10px; }
        .total-row { display: flex; justify-between; font-size: 14px; margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 18px; margin-top: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; border-top: 2px dashed #000; padding-top: 15px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-orange { background: #fed7aa; color: #9a3412; }
        .badge-green { background: #bbf7d0; color: #166534; }
        .badge-yellow { background: #fef08a; color: #854d0e; }
        .print-buttons { margin: 20px 0; text-align: center; }
        .btn { padding: 10px 20px; margin: 0 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #ea580c; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ELDOGAS POS</div>
        <div class="company-info">
            Gas Cylinder Management System<br>
            Tel: +254 XXX XXX XXX<br>
            Eldoret, Kenya
        </div>
    </div>

    <div class="section">
        <div class="section-title">TRANSACTION DETAILS</div>
        <div class="row">
            <span>Receipt #:</span>
            <span><strong>{{ $cylinder->reference_number }}</strong></span>
        </div>
        <div class="row">
            <span>Date:</span>
            <span>{{ $cylinder->drop_off_date->format('d/m/Y h:i A') }}</span>
        </div>
        <div class="row">
            <span>Type:</span>
            <span class="badge badge-orange">
                {{ $cylinder->isDropOff() ? 'DROP-OFF' : 'ADVANCE COLLECTION' }}
            </span>
        </div>
        <div class="row">
            <span>Payment:</span>
            <span class="badge {{ $cylinder->payment_status === 'paid' ? 'badge-green' : 'badge-yellow' }}">
                {{ strtoupper($cylinder->payment_status) }}
            </span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">CUSTOMER INFORMATION</div>
        <div class="row">
            <span>Name:</span>
            <span><strong>{{ $cylinder->customer_name }}</strong></span>
        </div>
        <div class="row">
            <span>Phone:</span>
            <span>{{ $cylinder->customer_phone }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">ITEMS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cylinder->items as $item)
                <tr class="item-row">
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->brand ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 0) }}</td>
                    <td>{{ number_format($item->subtotal, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>KSh {{ number_format($cylinder->amount, 0) }}</span>
        </div>
        @if($cylinder->deposit_amount > 0)
        <div class="total-row">
            <span>Deposit:</span>
            <span>KSh {{ number_format($cylinder->deposit_amount, 0) }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>KSh {{ number_format($cylinder->getTotalAmount(), 0) }}</span>
        </div>
    </div>

    @if($cylinder->notes)
    <div class="section">
        <div class="section-title">NOTES</div>
        <div style="font-size: 12px; line-height: 1.4;">{{ $cylinder->notes }}</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">IMPORTANT INFORMATION</div>
        <div style="font-size: 11px; line-height: 1.6;">
            @if($cylinder->isDropOff())
                ✓ Keep this receipt for cylinder collection<br>
                ✓ Present this receipt when collecting your refilled cylinder<br>
                ✓ Collection available during business hours<br>
            @else
                ✓ Return empty cylinder to claim deposit refund<br>
                ✓ Deposit: KSh {{ number_format($cylinder->deposit_amount, 0) }}<br>
                ✓ Present this receipt when returning cylinder<br>
            @endif
            @if($cylinder->isPending())
                ⚠ Payment Status: PENDING<br>
                ⚠ Amount Due: KSh {{ number_format($cylinder->getTotalAmount(), 0) }}<br>
            @endif
        </div>
    </div>

    <div class="footer">
        <div style="margin-bottom: 10px;">
            Served by: <strong>{{ $cylinder->createdBy->name }}</strong>
        </div>
        <div style="margin-bottom: 10px;">
            ═══════════════════════════
        </div>
        <div>
            {{ setting('receipt_footer', 'ItishaTunaDeliver, Asante.') }}<br>
            For inquiries: +254 XXX XXX XXX
        </div>
        <div style="margin-top: 10px; font-size: 10px;">
            Generated: {{ now()->format('d/m/Y h:i A') }}
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="print-buttons no-print" style="margin-top: 30px; display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">
            🖨️ Print Receipt
        </button>
        <a href="{{ route(str_starts_with(request()->route()->getName(), 'pos.') ? 'pos.cylinders.create' : 'admin.cylinders.create') }}" class="btn btn-secondary" style="flex: 1; text-decoration: none; display: inline-block; text-align: center;">
            ➕ New Transaction
        </a>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
