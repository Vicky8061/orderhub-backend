<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $order->order_number }}</title>
    <style>
        @page {
            margin: 10px;
            size: 80mm 200mm; /* POS Thermal Receipt size */
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 5px;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .logo { font-size: 18px; font-weight: 900; letter-spacing: 1px; margin-bottom: 2px; }
        .divider { border-bottom: 1px dashed #777; margin: 8px 0; }
        .double-divider { border-bottom: 2px solid #111; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 3px 0; }
        .item-row td { vertical-align: top; }
        .mod-row td { font-size: 10px; color: #555; padding-left: 10px; }
        .notes-row td { font-size: 9px; font-style: italic; color: #666; padding-left: 10px; }
        .totals-table td { padding: 2px 0; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="logo">QUICKBITE POS</div>
        <div style="font-size: 9px; color: #666;">Fresh & Delicious Fast Food</div>
        <div style="font-size: 9px; color: #666;">Phone: (555) 123-4567</div>
    </div>

    <div class="divider"></div>

    <div>
        <table style="font-size: 10px;">
            <tr>
                <td><strong>Order #:</strong> {{ $order->order_number }}</td>
                <td class="text-right"><strong>Type:</strong> {{ strtoupper(str_replace('_', ' ', $order->order_type)) }}</td>
            </tr>
            <tr>
                <td><strong>Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</td>
                <td class="text-right"><strong>Cashier:</strong> {{ $order->cashier?->name ?? 'Kiosk' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Status:</strong> {{ strtoupper($order->status) }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr style="border-bottom: 1px solid #111; font-size: 10px;">
                <th style="text-align: left; width: 60%;">ITEM</th>
                <th style="text-align: center; width: 15%;">QTY</th>
                <th style="text-align: right; width: 25%;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php
                    $name = $item->combo_id ? ($item->combo?->name ?? 'Combo') : ($item->menuItem?->name ?? 'Item');
                    $itemTotal = $item->unit_price * $item->quantity;
                @endphp
                <tr class="item-row">
                    <td>
                        <strong>{{ $name }}</strong>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($itemTotal, 2) }}</td>
                </tr>

                @if($item->notes)
                    <tr class="notes-row">
                        <td colspan="3">* Note: {{ $item->notes }}</td>
                    </tr>
                @endif

                @foreach ($item->modifiers as $mod)
                    @php $modTotal = $mod->price_at_order * $item->quantity; @endphp
                    <tr class="mod-row">
                        <td colspan="2">+ {{ $mod->modifier?->name ?? 'Modifier' }}</td>
                        <td class="text-right">
                            @if($mod->price_at_order > 0)
                                +${{ number_format($modTotal, 2) }}
                            @else
                                $0.00
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Tax (8%):</td>
            <td class="text-right">${{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        <tr style="font-size: 13px; font-weight: bold;">
            <td>TOTAL:</td>
            <td class="text-right">${{ number_format($order->total, 2) }}</td>
        </tr>
        <tr>
            <td style="font-size: 10px; color: #555;">Payment ({{ strtoupper($order->payment_method) }}):</td>
            <td class="text-right" style="font-size: 10px; color: #555;">Paid in Full</td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <div class="text-center" style="margin-top: 10px; font-size: 10px;">
        <div>Thank you for dining with us!</div>
        <div style="margin-top: 4px; font-size: 8px; color: #888;">Order ID: {{ $order->id }} | Powered by QuickBite POS</div>
    </div>
</body>
</html>
