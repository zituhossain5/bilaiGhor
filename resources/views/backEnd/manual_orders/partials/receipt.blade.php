<article class="mi-doc">
    <table class="mi-top" role="presentation">
        <tr>
            <td class="mi-logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $generalsetting?->name ?: 'Bilai Ghor' }}">
                @endif
            </td>
            <td class="mi-title">
                <h1>Receipt</h1>
                <p>Order ID: <strong>{{ $invoice }}</strong></p>
                <p>Date: {{ $order->created_at?->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <section class="mi-customer">
        <h2 class="mi-box-title">Customer Details:</h2>
        <table class="mi-details" role="presentation">
            <tr><td class="mi-label">Name:</td><td class="mi-value">{{ $customerName }}</td></tr>
            <tr><td class="mi-label">Address:</td><td class="mi-value">{{ $customerAddress }}</td></tr>
            <tr><td class="mi-label">Phone:</td><td class="mi-value">{{ $customerPhone }}</td></tr>
        </table>
    </section>

    <table class="mi-table">
        <thead>
            <tr>
                <th class="mi-product-col">Product Details</th>
                <th class="mi-num mi-qty-col">Qty.</th>
                <th class="mi-num mi-price-col">Price</th>
                <th class="mi-num mi-amount-col">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $item)
                @php
                    $lineAmount = $item->line_total ?: (($item->sale_price * $item->qty) - ($item->line_discount ?? 0));
                @endphp
                <tr>
                    <td>
                        <div class="mi-product-name">{{ $item->product_name }}</div>
                        @if($item->manual_variant)<div class="mi-product-variant">{{ $item->manual_variant }}</div>@endif
                    </td>
                    <td class="mi-num">{{ $item->qty }}</td>
                    <td class="mi-num">{{ number_format($item->sale_price, 0) }}</td>
                    <td class="mi-num">{{ number_format($lineAmount, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="mi-summary" role="presentation">
        <colgroup>
            <col class="mi-summary-label-col">
            <col class="mi-summary-value-col">
        </colgroup>
        <tr><td class="mi-summary-label">Subtotal</td><td class="mi-summary-value">&#2547;{{ number_format($subtotal, 0) }}</td></tr>
        <tr><td class="mi-summary-label">Delivery Charge</td><td class="mi-summary-value">&#2547;{{ number_format($delivery, 0) }}</td></tr>
        <tr><td class="mi-summary-label">Discount</td><td class="mi-summary-value">@if($discount > 0) &#2547;{{ number_format($discount, 0) }} @else --- @endif</td></tr>
        <tr class="mi-total-row"><td class="mi-summary-label">TOTAL</td><td class="mi-summary-value">&#2547;{{ number_format($total, 0) }}</td></tr>
    </table>

    @php
        $paymentMethod = $order->payment_method ?: optional($order->payment)->payment_method;
        $paymentMethodLabel = match ($paymentMethod) {
            'bkash' => 'Mobile Banking (Bkash)',
            'nagad' => 'Mobile Banking (Nagad)',
            'rocket' => 'Mobile Banking (Rocket)',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            default => $paymentMethod ? ucwords(str_replace('_', ' ', $paymentMethod)) : null,
        };
    @endphp
    @if($paymentMethodLabel)
        <div class="mi-payment">Payment Method: {{ $paymentMethodLabel }}</div>
    @endif

    <div class="mi-thanks">Thank You For Your Purchase</div>

    <table class="mi-footer" role="presentation">
        <tr>
            <td class="mi-social">
                <div class="mi-social-line"><span class="mi-social-icon"><img src="{{ $facebookIconUrl }}" alt="Facebook"></span><span>{{ $facebook }}</span></div>
                <div class="mi-social-line"><span class="mi-social-icon"><img src="{{ $whatsappIconUrl }}" alt="WhatsApp"></span><span>{{ $whatsapp }}</span></div>
            </td>
            <td class="mi-qr">
                <img src="{{ $qrUrl }}" alt="Invoice verification QR">
                <div class="mi-qr-caption">Scan to verify invoice</div>
            </td>
        </tr>
    </table>
</article>
