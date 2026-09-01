@extends('frontEnd.layouts.master')
@section('title', $category->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('css_after')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-listing-figma.css') }}?v=7" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ route('category', $category->slug) }}" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ $category->meta_description }}" />
    <meta name="keywords" content="{{ $category->slug }}" />
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="{{ $category->name }}" />
    <meta name="twitter:title" content="{{ $category->name }}" />
    <meta name="twitter:description" content="{{ $category->meta_description }}" />
    <meta name="twitter:creator" content="bilaighor.bd" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta name="twitter:image" content="{{ asset($category->image) }}" />
    <meta property="og:title" content="{{ $category->name }}" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta property="og:image" content="{{ asset($category->image) }}" />
    <meta property="og:description" content="{{ $category->meta_description }}" />
    <meta property="og:site_name" content="{{ $category->name }}" />
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container bilai-listing-container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">{{ $category->name }}</span>
        </nav>

        {{-- SUBCATEGORY CARDS — act as filters --}}
        @if($subcategories->count() > 0)
        <div class="bilai-cat-sub-row">
            @foreach($subcategories as $subcat)
            @php
                $isActiveSub = $activeSubcatSlug === $subcat->slug;
                // Build the filter URL: keep all current params except subcategory and page
                $otherParams = request()->except(['subcategory', 'page']);
                $subCardUrl  = $isActiveSub
                    ? route('category', $category->slug) . ($otherParams ? '?' . http_build_query($otherParams) : '')
                    : route('category', $category->slug) . '?' . http_build_query(array_merge($otherParams, ['subcategory' => $subcat->slug]));
            @endphp
            <a href="{{ $subCardUrl }}" class="bilai-cat-sub-card {{ $isActiveSub ? 'active' : '' }}">
                <div class="bilai-cat-sub-img-box">
                    @if($subcat->image)
                    <img src="{{ asset($subcat->image) }}" alt="{{ $subcat->subcategoryName }}" loading="lazy">
                    @else
                    <i class="fas fa-tag"></i>
                    @endif
                </div>
                <span>{{ $subcat->subcategoryName }}</span>
            </a>
            @endforeach
        </div>
        @endif

        {{-- MAIN LAYOUT --}}
        <div class="bilai-cat-layout">

            {{-- LEFT SIDEBAR --}}
            <aside class="bilai-cat-sidebar">
                @include('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('category', $category->slug)])
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

{{-- BOTTOM DESCRIPTION ACCORDION --}}
@if($category->full_description)
<div class="container bilai-listing-container">
<div class="bilai-seo-accordion">
        <button class="bilai-seo-toggle" id="bilaiSeoToggle" type="button" aria-expanded="false">
            <span>View Full Description</span>
            <i class="fas fa-chevron-down bilai-seo-icon"></i>
        </button>
        <div class="bilai-seo-body" id="bilaiSeoBody" style="display:none;">
            <div class="bilai-seo-content">
                {!! $category->full_description !!}
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('script')
    @include('frontEnd.layouts.partials.listing-js')
    @include('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $category->name,
        'listSlug' => $category->slug,
        'fbEvent'  => 'ViewCategory',
    ])
@endpush
