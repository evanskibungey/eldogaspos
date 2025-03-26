{{-- resources/views/pos/receipts/thermal.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $sale->receipt_number }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            width: 57mm;
        }
        .container {
            width: 57mm;
            padding: 2mm;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 3mm;
        }
        .company-name {
            font-weight: bold;
            font-size: 12pt;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
        .receipt-info {
            margin-bottom: 3mm;
        }
        .item-row {
            font-size: 9pt;
            margin-bottom: 1mm;
        }
        .serial {
            font-size: 8pt;
            padding-left: 3mm;
        }
        .totals {
            margin-top: 2mm;
            font-weight: bold;
        }
        .align-right {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            font-size: 9pt;
        }
        td {
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">{{ $company['name'] }}</div>
            <div>{{ $company['address'] }}</div>
            <div>{{ $company['phone'] }}</div>
        </div>
        
        <div class="divider"></div>
        
        <div class="receipt-info">
            <div>Receipt: {{ $sale->receipt_number }}</div>
            <div>Date: {{ $sale->created_at->format('Y-m-d H:i:s') }}</div>
            <div>Customer: {{ $sale->customer->name }}</div>
            <div>Payment: {{ ucfirst($sale->payment_method) }}</div>
        </div>
        
        <div class="divider"></div>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th class="align-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr class="item-row">
                    <td>{{ Str::limit($item->product->name, 10) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td class="align-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @if($item->serial_number)
                <tr>
                    <td colspan="4" class="serial">S/N: {{ $item->serial_number }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        
        <div class="divider"></div>
        
        <div class="totals">
            <table>
                <tr>
                    <td>TOTAL:</td>
                    <td class="align-right">{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>
        
        <div class="divider"></div>
        
        <div class="footer">
            <div>Thank you for your business!</div>
            <div>Please come again</div>
        </div>
    </div>
</body>
</html>