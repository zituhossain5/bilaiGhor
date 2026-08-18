@extends('frontEnd.layouts.master')
@section('title', $listingPageTitle ?? 'All Products')

@push('seo')
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ $listingMetaDescription ?? optional($generalsetting)->tagline ?? 'Browse all approved products in one place.' }}" />
@endpush

@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">{{ $listingBreadcrumb ?? 'Shop' }}</span>
        </nav>

        {{-- MAIN LAYOUT --}}
        <div class="bilai-cat-layout">

            {{-- LEFT SIDEBAR --}}
            <aside class="bilai-cat-sidebar">
                @include('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => $filterBaseUrl ?? route('shop')])
            </aside>

            {{-- RIGHT PRODUCT AREA --}}
            <div class="bilai-cat-main">

                {{-- TOP BAR --}}
                @include('frontEnd.layouts.partials.listing-topbar')

                {{-- PRODUCT GRID --}}
                @if($products->count() > 0)
                <div class="bilai-cat-grid">
                    @foreach($products as $key => $value)
                        @include('frontEnd.layouts.partials.product-card', ['value' => $value, 'key' => $key])
                    @endforeach
                </div>
                @else
                <div class="bilai-cat-empty">
                    <i class="fas fa-box-open"></i>
                    <p>No products found.</p>
                </div>
                @endif

                {{-- PAGINATION --}}
                @if($products->hasPages())
                <div class="bilai-cat-pagination">
                    {{ $products->links('pagination::bootstrap-4') }}
                </div>
                @endif

            </div>{{-- end .bilai-cat-main --}}
        </div>{{-- end .bilai-cat-layout --}}

    </div>{{-- end .container --}}
</div>{{-- end .bilai-cat-page --}}
@endsection

@push('script')
    @include('frontEnd.layouts.partials.listing-js')
    @include('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $listingAnalyticsName ?? 'Shop',
        'listSlug' => $listingAnalyticsSlug ?? 'shop',
        'fbEvent'  => $listingFacebookEvent ?? 'ViewShop',
    ])
@endpush
