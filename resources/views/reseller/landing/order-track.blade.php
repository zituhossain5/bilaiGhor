<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অর্ডার ট্রাকিং | {{ $landing->title }}</title>
    @if(!empty($landing->favicon))<link rel="icon" type="{{ str_ends_with($landing->favicon, '.ico') ? 'image/x-icon' : 'image/png' }}" href="{{ asset($landing->favicon) }}">@endif
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('reseller.landing.partials.tracking-head')
</head>
<body class="bg-gray-100 font-sans text-gray-800 pb-20 md:pb-0">
    @include('reseller.landing.partials.tracking-body')
    <div class="bg-gray-900 text-gray-300 text-xs py-2 px-4">
        <div class="container mx-auto flex justify-between">
            @if($landing->phone)<a href="tel:{{ $landing->phone }}" class="hover:text-white"><i class="fa-solid fa-phone mr-1"></i> {{ $landing->phone }}</a>@endif
            <div class="flex gap-4">
                <a href="{{ landing_url($landing->slug, '') }}" class="hover:text-white">হোম</a>
                <span>|</span>
                <a href="{{ route('reseller.landing.contact', $landing->slug) }}" class="hover:text-white">যোগাযোগ</a>
            </div>
        </div>
    </div>

    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ landing_url($landing->slug, '') }}">
                @if($landing->logo)<img src="{{ asset($landing->logo) }}" alt="" class="h-10 w-auto">@else<span class="text-xl font-bold text-blue-700">{{ $landing->title }}</span>@endif
            </a>
            <a href="{{ landing_url($landing->slug, '') }}" class="text-blue-600 font-semibold hover:text-blue-700"><i class="fa-solid fa-arrow-left mr-1"></i> হোমে ফিরে যান</a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-blue-600 text-white p-6 sm:p-8 text-center">
                <i class="fa-solid fa-truck-fast text-5xl mb-4 opacity-90"></i>
                <h1 class="text-xl md:text-2xl font-bold">অর্ডার ট্রাকিং</h1>
                <p class="text-sm mt-2 opacity-90">Use the order reference and private tracking code from your confirmation link.</p>
            </div>
            <div class="p-6 sm:p-8">
                <form action="{{ route('reseller.landing.order-track.result', $landing->slug) }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice / order reference</label>
                        <input type="text" name="invoice_id" required value="{{ old('invoice_id') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl" placeholder="Order reference">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Private tracking code from your confirmation link</label>
                        <input type="text" name="token" required autocomplete="off" value="{{ old('token') }}" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl" placeholder="Tracking code">
                    </div>
                    <button type="submit" class="w-full py-4 bg-blue-600 text-white font-bold rounded-xl">Track order</button>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t z-50 md:hidden py-2 flex justify-center">
        <a href="{{ landing_url($landing->slug, '') }}" class="text-blue-600 font-semibold"><i class="fa-solid fa-house mr-1"></i> হোমে ফিরে যান</a>
    </div>
</body>
</html>
