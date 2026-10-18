@extends('frontEnd.layouts.master')
@php
    // Admin SEO fields when filled, otherwise generated text.
    $seoTitle = \App\Support\SeoText::plain($brand->seo_title) ?: $brand->name . ' Price in Bangladesh | Bilai Ghor';
    $seoH1 = \App\Support\SeoText::plain($brand->seo_h1) ?: $brand->name . ' Price in Bangladesh';
    $seoDescription = \App\Support\SeoText::plain($brand->meta_description, 170)
        ?: 'Buy original ' . $brand->name . ' cat products in Bangladesh at the best price. Cash on delivery all over BD.'
            . ($inStockCount > 0 ? ' ' . $inStockCount . ' ' . \Illuminate\Support\Str::plural('product', $inStockCount) . ' in stock.' : '');
    $canonicalUrl = \App\Support\Canonical::current(route('brand.products', $brand->slug));
    $seoImage = $brand->image ? \App\Support\JsonLd::fileUrl($brand->image) : null;

    $breadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Brands', 'item' => route('brands')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $brand->name, 'item' => route('brand.products', $brand->slug)],
        ],
    ];
    $collectionJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $seoH1,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'about' => ['@type' => 'Brand', 'name' => $brand->name, 'logo' => $seoImage],
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => $productCount,
            'itemListElement' => $products->values()->map(fn ($p, $i) => [
                '@type' => 'ListItem',
                'position' => ($products->firstItem() ?? 1) + $i,
                'url' => route('product', $p->slug),
                'name' => $p->name,
            ])->all(),
        ],
    ];
@endphp
@section('title', $seoTitle)
@section('canonical', $canonicalUrl)
@push('css')
    <link rel="stylesheet" href="{{ asset_v('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('css_after')
    <link rel="stylesheet" href="{{ asset_v('public/frontEnd/css/bilai-listing-figma.css') }}" />
@endpush
@push('seo')
    <meta name="robots" content="index,follow" />
    <meta name="description" content="{{ $seoDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:site_name" content="Bilai Ghor" />
    @if($seoImage)
    <meta property="og:image" content="{{ $seoImage }}" />
    @endif
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    <meta name="twitter:description" content="{{ $seoDescription }}" />
    <script type="application/ld+json">{!! \App\Support\JsonLd::encode($breadcrumbJsonLd) !!}</script>
    <script type="application/ld+json">{!! \App\Support\JsonLd::encode($collectionJsonLd) !!}</script>
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container bilai-listing-container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="{{ route('brands') }}">Brands</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">{{ $brand->name }}</span>
        </nav>

        <section class="bilai-listing-seo-header">
            <h1>{{ $seoH1 }}</h1>
            <p>{{ $seoDescription }}</p>
        </section>

        {{-- MAIN LAYOUT --}}
        <div class="bilai-cat-layout">

            {{-- LEFT SIDEBAR --}}
            <aside class="bilai-cat-sidebar">
                @include('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('brand.products', $brand->slug)])
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

{{-- BOTTOM DESCRIPTION ACCORDION (admin "SEO Content") --}}
@if(filled($brand->seo_content))
<div class="container bilai-listing-container">
<div class="bilai-seo-accordion">
        <button class="bilai-seo-toggle" id="bilaiSeoToggle" type="button" aria-expanded="false">
            <span>View Full Description</span>
            <i class="fas fa-chevron-down bilai-seo-icon"></i>
        </button>
        <div class="bilai-seo-body" id="bilaiSeoBody" style="display:none;">
            <div class="bilai-seo-content">
                {!! $brand->seo_content !!}
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('script')
    @include('frontEnd.layouts.partials.listing-js')
    @include('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $brand->name,
        'listSlug' => $brand->slug,
        'fbEvent'  => 'ViewBrand',
    ])
@endpush
