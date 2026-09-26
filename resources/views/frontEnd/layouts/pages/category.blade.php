@extends('frontEnd.layouts.master')
@php
    $seoTitle = \App\Support\SeoText::plain($category->meta_title) ?: $category->name . ' | Bilai Ghor';
    $seoDescription = \App\Support\SeoText::plain($category->meta_description, 170);
    $canonicalUrl = route('category', $category->slug);
    $currentPage = (int) request('page', 1);
    if ($currentPage > 1) {
        $canonicalUrl .= '?page=' . $currentPage;
    }
    $seoH1 = \App\Support\SeoText::plain($category->seo_h1) ?: $category->name;
    $seoImage = $category->image ? asset($category->image) : null;
    $breadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $category->name,
                'item' => route('category', $category->slug),
            ],
        ],
    ];
@endphp
@section('title', $seoTitle)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('css_after')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-listing-figma.css') }}?v=9" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ $canonicalUrl }}" />
    <meta name="robots" content="index,follow" />
    @if($seoDescription)
    <meta name="description" content="{{ $seoDescription }}" />
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    @if($seoDescription)
    <meta property="og:description" content="{{ $seoDescription }}" />
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:site_name" content="Bilai Ghor" />
    @if($seoImage)
    <meta property="og:image" content="{{ $seoImage }}" />
    @endif
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    @if($seoDescription)
    <meta name="twitter:description" content="{{ $seoDescription }}" />
    @endif
    @if($seoImage)
    <meta name="twitter:image" content="{{ $seoImage }}" />
    @endif
    <script type="application/ld+json">{!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
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

        <section class="bilai-listing-seo-header">
            <h1>{{ $seoH1 }}</h1>
            @if($seoDescription)
            <p>{{ $seoDescription }}</p>
            @endif
        </section>

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
                    <img src="{{ asset($subcat->image) }}" alt="{{ $subcat->image_alt ?: $subcat->subcategoryName }}" loading="lazy">
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
