{{-- Compact receipt. $receipt comes from App\Services\ReceiptLayout (merged rows, density, pages). --}}
@php
    $pageCount = count($receipt['pages']);
    $showSubtotal = $delivery > 0 || $discount > 0;
@endphp
@foreach($receipt['pages'] as $pageRows)
<article class="mi-doc{{ $receipt['dense'] ? ' mi-dense' : '' }}{{ $loop->first ? '' : ' mi-doc-break' }}">
    @if($loop->first)
        <table class="mi-top" role="presentation">
            <tr>
                <td class="mi-logo">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $generalsetting?->name ?: 'Bilai Ghor' }}">
                    @endif
                </td>
                <td class="mi-title">
                    <h1>Receipt</h1>
                    <p><strong>{{ $invoice }}</strong> · {{ $order->created_at?->format('d/m/Y') }}</p>
                    @if($pageCount > 1)<p class="mi-page-no">Page 1/{{ $pageCount }}</p>@endif
                </td>
            </tr>
        </table>

        <section class="mi-customer">
            <div class="mi-customer-line"><strong>{{ $customerName }}</strong>@if($customerPhone) · {{ $customerPhone }}@endif</div>
            @if($customerAddress)<div class="mi-address">{{ $customerAddress }}</div>@endif
        </section>
    @else
        <div class="mi-continued"><strong>{{ $invoice }}</strong> · Page {{ $loop->iteration }}/{{ $pageCount }}</div>
    @endif

    @if($receipt['commaList'])
        <p class="mi-comma-list">{{ $receipt['commaText'] }}</p>
    @else
        <table class="mi-table">
            <thead>
                <tr>
                    <th class="mi-item-col">Item</th>
                    <th class="mi-num mi-qty-col">Qty</th>
                    <th class="mi-num mi-price-col">Price</th>
                    <th class="mi-num mi-amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pageRows as $row)
                    <tr>
                        <td class="mi-item"><span class="mi-item-name">{{ $row['name'] }}</span>@if($row['variant'])<span class="mi-item-variant"> · {{ $row['variant'] }}</span>@endif</td>
                        <td class="mi-num">{{ $row['qty'] }}</td>
                        <td class="mi-num">{{ number_format($row['price'], 0) }}</td>
                        <td class="mi-num">{{ number_format($row['amount'], 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($loop->last)
        <table class="mi-summary" role="presentation">
            <colgroup>
                <col class="mi-summary-label-col">
                <col class="mi-summary-value-col">
            </colgroup>
            @if($showSubtotal)
                <tr><td class="mi-summary-label">Subtotal</td><td class="mi-summary-value">&#2547;{{ number_format($subtotal, 0) }}</td></tr>
            @endif
            @if($delivery > 0)
                <tr><td class="mi-summary-label">Delivery</td><td class="mi-summary-value">&#2547;{{ number_format($delivery, 0) }}</td></tr>
            @endif
            @if($discount > 0)
                <tr><td class="mi-summary-label">Discount</td><td class="mi-summary-value">&minus;&#2547;{{ number_format($discount, 0) }}</td></tr>
            @endif
            <tr class="mi-total-row"><td class="mi-summary-label">TOTAL</td><td class="mi-summary-value">&#2547;{{ number_format($total, 0) }}</td></tr>
            @if($receipt['payment'])
                <tr class="mi-payment"><td colspan="2">{{ $receipt['payment'] }}</td></tr>
            @endif
        </table>

        <table class="mi-footer" role="presentation">
            <tr>
                <td class="mi-footer-text">
                    <div class="mi-thanks">Thank you for your purchase!</div>
                    <div class="mi-social-line"><span class="mi-social-icon"><img src="{{ $facebookIconUrl }}" alt="Facebook"></span><span>{{ $facebook }}</span></div>
                    <div class="mi-social-line"><span class="mi-social-icon"><img src="{{ $whatsappIconUrl }}" alt="WhatsApp"></span><span>{{ $whatsapp }}</span></div>
                </td>
                <td class="mi-qr">
                    <img src="{{ $qrUrl }}" alt="Invoice verification QR">
                    <div class="mi-qr-caption">Scan to verify</div>
                </td>
            </tr>
        </table>
    @endif
</article>
@endforeach
