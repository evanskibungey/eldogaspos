{{--
    Standalone 57mm thermal receipt for one sale.

    A page of its own, not a hidden div on the POS screen. The dashboard used
    to print itself with `visibility: hidden` on everything else, but hidden
    elements still occupy layout, so the dashboard's full height still
    paginated - every receipt came out on two sheets. Nothing else is on this
    page, so nothing else can paginate.

    Loaded into a hidden iframe by the POS Print button, and reachable directly
    for a reprint. `?autoprint=1` prints on load; without it the page just
    renders with a Print button.

    Every figure comes from the sale row, never from the till's memory, so a
    reprint months later is identical to the original.
--}}
@php
    $isAutoPrint = request()->boolean('autoprint');

    /*
        Deliberately the same literals the previous receipt printed, NOT
        settings. `company_name` currently holds "EldoGas POS" and
        `company_phone` holds the placeholder "0700123456" - wiring those in
        would put a dead number on every customer's receipt. Switch these to
        setting() once those two values are correct in Settings.
    */
    $company = 'Eldogas';
    $phone = '+254724556855';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->receipt_number }}</title>
    <style>
        /*
            57mm paper on a 58mm roll. `auto` height is what stops the printer
            feeding a fixed page length and cutting mid-receipt.
        */
        @page {
            size: 57mm auto;
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9pt;
            line-height: 1.25;
            color: #000;
            background: #fff;
            width: 57mm;
            margin: 0 auto;
            padding: 2mm;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }

        .company-name { font-size: 13pt; font-weight: bold; text-align: center; }
        .company-info { font-size: 8pt; text-align: center; }

        .sep { text-align: center; margin: 1.5mm 0; overflow: hidden; white-space: nowrap; }

        .row { display: flex; justify-content: space-between; font-size: 8.5pt; }
        .row .label { font-weight: bold; }

        .section-title { text-align: center; font-weight: bold; margin: 1mm 0; font-size: 9pt; }

        table { width: 100%; border-collapse: collapse; }
        th, td { font-size: 8pt; padding: 0.4mm 0; vertical-align: top; }
        th { border-bottom: 1px solid #000; text-align: left; font-size: 8pt; }
        .qty, .price, .total { text-align: right; white-space: nowrap; }
        .name { word-break: break-word; }
        .meta { font-size: 7pt; font-style: italic; }

        .grand { font-size: 11pt; font-weight: bold; }

        .footer { text-align: center; font-size: 8pt; margin-top: 2mm; }

        /* Screen-only chrome for a direct visit / reprint. */
        .actions { margin: 6mm 0 2mm; display: flex; gap: 2mm; }
        .actions button, .actions a {
            flex: 1; padding: 2.5mm; border: none; border-radius: 1mm;
            font-size: 8pt; font-weight: bold; cursor: pointer; text-align: center;
            text-decoration: none; color: #fff; background: #ea580c;
        }
        .actions a { background: #6b7280; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 2mm; }
        }
    </style>
</head>
<body>
    <div class="company-name">{{ $company }}</div>
    <div class="company-info">Tel:{{ $phone }}</div>
    <div class="sep">****************************</div>

    @if($sale->order_number !== null)
        <div class="row"><span class="label">ORDER NO:</span><span>{{ $sale->order_number }}</span></div>
    @endif
    <div class="row"><span class="label">RECEIPT #:</span><span>{{ $sale->receipt_number }}</span></div>
    <div class="row"><span class="label">DATE:</span><span>{{ $sale->created_at->format('d/m/Y') }}</span></div>
    <div class="row"><span class="label">TIME:</span><span>{{ $sale->created_at->format('h:i A') }}</span></div>
    <div class="row"><span class="label">PAYMENT:</span><span>{{ ucfirst($sale->payment_method) }}</span></div>
    <div class="row"><span class="label">CASHIER:</span><span>{{ Str::limit(optional($sale->user)->name ?? '-', 10, '') }}</span></div>

    <div class="sep">----------------------------</div>
    <div class="section-title">ITEMS</div>

    <table>
        <tr>
            <th class="name">ITEM</th>
            <th class="qty">QTY</th>
            <th class="price">PRICE</th>
            <th class="total">TOTAL</th>
        </tr>
        @foreach($sale->items as $item)
            <tr>
                <td class="name">
                    {{ optional($item->product)->name ?? 'Item #' . $item->product_id }}
                    @if($item->order_number !== null)
                        <div class="meta">ORDER NO: {{ $item->order_number }}</div>
                    @endif
                    @if($item->serial_number)
                        <div class="meta">S/N:{{ $item->serial_number }}</div>
                    @endif
                </td>
                <td class="qty">{{ $item->quantity }}</td>
                <td class="price">{{ number_format($item->unit_price, 0) }}</td>
                <td class="total">{{ number_format($item->subtotal, 0) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="sep">----------------------------</div>
    <div class="row"><span class="label">SUBTOTAL:</span><span>KSH {{ number_format($sale->total_amount, 0) }}</span></div>
    <div class="row"><span class="label">TAX (0%):</span><span>KSH 0</span></div>
    <div class="row grand"><span>TOTAL:</span><span>KSH {{ number_format($sale->total_amount, 0) }}</span></div>

    @if($sale->payment_method === 'credit' && $sale->customer)
        <div class="sep">----------------------------</div>
        <div class="row"><span class="label">CUSTOMER:</span><span>{{ Str::limit($sale->customer->name, 14, '') }}</span></div>
        <div class="row"><span class="label">BALANCE:</span><span>KSH {{ number_format($sale->customer->balance, 0) }}</span></div>
    @endif

    <div class="sep">****************************</div>
    <div class="footer">
        <div>{{ setting('receipt_footer', 'ItishaTunaDeliver, Asante.') }}</div>
        <div class="bold">*{{ $company }}*</div>
        <div>{{ $sale->created_at->format('d/m/Y') }}</div>
    </div>

    @unless($isAutoPrint)
        <div class="actions no-print">
            <button type="button" onclick="window.print()">Print</button>
            <a href="{{ route('pos.dashboard') }}">Back to POS</a>
        </div>
    @endunless

    @if($isAutoPrint)
        <script>
            // Printed by whoever loaded this page - the POS iframe, or a direct
            // visit with ?autoprint=1. Waiting for `load` rather than firing
            // immediately means fonts and layout are settled, so the printer
            // never receives a half-measured receipt.
            window.addEventListener('load', function () {
                window.focus();
                window.print();
            });
        </script>
    @endif
</body>
</html>
