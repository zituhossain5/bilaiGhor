@extends('frontEnd.layouts.master')
@php
    $seoTitle = \App\Support\SeoText::plain($subcategory->meta_title) ?: $subcategory->subcategoryName . ' | Bilai Ghor';
    $seoDescription = \App\Support\SeoText::plain($subcategory->meta_description, 170);
    $canonicalUrl = route('subcategory', $subcategory->slug);
    $currentPage = (int) request('page', 1);
    if ($currentPage > 1) {
        $canonicalUrl .= '?page=' . $currentPage;
    }
    $seoH1 = \App\Support\SeoText::plain($subcategory->seo_h1) ?: $subcategory->subcategoryName;
    $seoImage = $subcategory->image ? asset($subcategory->image) : null;
    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => route('home'),
        ],
    ];
    if ($category) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $category->name,
            'item' => route('category', $category->slug),
        ];
    }
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $subcategory->subcategoryName,
        'item' => route('subcategory', $subcategory->slug),
    ];
    $breadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
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
            @if($category)
            <a href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            @endif
            <span class="bilai-cat-breadcrumb-current">{{ $subcategory->subcategoryName }}</span>
        </nav>

        <section class="bilai-listing-seo-header">
            <h1>{{ $seoH1 }}</h1>
            @if($seoDescription)
            <p>{{ $seoDescription }}</p>
            @endif
        </section>

        {{-- SIBLING SUBCATEGORY CARDS --}}
        @if($siblings->count() > 0)
        <div class="bilai-cat-sub-row">
            @foreach($siblings as $sibling)
            @php
                $isActive = $sibling->slug === $subcategory->slug;
            @endphp
            <a href="{{ route('subcategory', $sibling->slug) }}"
               class="bilai-cat-sub-card {{ $isActive ? 'active' : '' }}">
                <div class="bilai-cat-sub-img-box">
                    @if($sibling->image)
                    <img src="{{ asset($sibling->image) }}" alt="{{ $sibling->image_alt ?: $sibling->subcategoryName }}" loading="lazy">
                    @else
                    <i class="fas fa-tag"></i>
                    @endif
                </div>
                <span>{{ $sibling->subcategoryName }}</span>
            </a>
            @endforeach
        </div>
        @endif

        {{-- MAIN LAYOUT --}}
        <div class="bilai-cat-layout">

            {{-- LEFT SIDEBAR --}}
            <aside class="bilai-cat-sidebar">
                @include('frontEnd.layouts.partials.listing-filter-sidebar', ['filterBaseUrl' => route('subcategory', $subcategory->slug)])
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

{{-- BOTTOM DESCRIPTION --}}
@if($subcategory->full_description)
<div class="bilai-seo-accordion">
    <div class="container bilai-listing-container">
        <button class="bilai-seo-toggle" id="bilaiSeoToggle" type="button" aria-expanded="false">
            <span>View Full Description</span>
            <i class="fas fa-chevron-down bilai-seo-icon"></i>
        </button>
        <div class="bilai-seo-body" id="bilaiSeoBody" style="display:none;">
            <div class="bilai-seo-content">
                {!! $subcategory->full_description !!}
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('script')
    @include('frontEnd.layouts.partials.listing-js')
    @include('frontEnd.layouts.partials.listing-analytics-js', [
        'listName' => $subcategory->subcategoryName,
        'listSlug' => $subcategory->slug,
        'fbEvent'  => 'ViewSubcategory',
    ])
@endpush
