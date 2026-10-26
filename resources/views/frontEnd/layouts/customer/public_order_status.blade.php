<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $heading ?? ($success ? 'Order received' : 'Order tracking') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #fffaf1; color: #2b1608; font: 16px/1.5 Arial, sans-serif; }
        main { box-sizing: border-box; width: min(440px, calc(100% - 32px)); padding: 28px; border: 1px solid #d8b990; border-radius: 16px; background: #fff; }
        h1 { margin: 0 0 16px; font-size: 24px; }
        dl { margin: 0; } dl div { display: flex; justify-content: space-between; gap: 18px; padding: 10px 0; border-bottom: 1px solid #f1e4d2; }
        dt { color: #765b45; } dd { margin: 0; text-align: right; overflow-wrap: anywhere; }
        button, a.button { display: block; width: 100%; box-sizing: border-box; margin-top: 22px; padding: 12px; border: 0; border-radius: 9px; background: #ed8515; color: #fff; text-align: center; text-decoration: none; font: inherit; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        <h1>{{ $heading ?? ($success ? 'Your order has been received' : 'Order tracking') }}</h1>
        <dl>
            <div><dt>Order reference</dt><dd>{{ $order->invoice_id }}</dd></div>
            <div><dt>Order status</dt><dd>{{ $order->status?->name ?? 'Unknown' }}</dd></div>
            @if($order->courierParcel?->status)
                <div><dt>Courier status</dt><dd>{{ $order->courierParcel->status }}</dd></div>
            @endif
            <div><dt>Order date</dt><dd>{{ $order->created_at?->format('d M Y') }}</dd></div>
        </dl>
        @if(isset($downloads) && $downloads->isNotEmpty())
            <h2>Your digital downloads</h2>
            @foreach($downloads as $download)
                <a class="button" href="{{ route('digital.download', $download->token) }}">Download purchased file</a>
            @endforeach
        @endif
        @if($success && $trackAction)
            <p>Save this private confirmation link to check your order later. Anyone with the link can view its status.</p>
            <form method="POST" action="{{ $trackAction }}">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $order->invoice_id }}">
                <input type="hidden" name="token" value="{{ $order->public_token }}">
                <button type="submit">Check current status</button>
            </form>
        @endif
    </main>
</body>
</html>
