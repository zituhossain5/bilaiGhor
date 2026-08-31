@extends('frontEnd.layouts.master')
@section('title', $subcategory->subcategoryName)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('css_after')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-listing-figma.css') }}?v=2" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ route('subcategory', $subcategory->slug) }}" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ $subcategory->meta_description }}" />
    <meta name="keywords" content="{{ $subcategory->slug }}" />
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="{{ $subcategory->subcategoryName }}" />
    <meta name="twitter:title" content="{{ $subcategory->subcategoryName }}" />
    <meta name="twitter:description" content="{{ $subcategory->meta_description }}" />
    <meta name="twitter:creator" content="bilaighor.bd" />
    <meta property="og:url" content="{{ route('subcategory', $subcategory->slug) }}" />
    <meta name="twitter:image" content="{{ asset($subcategory->image) }}" />
    <meta property="og:title" content="{{ $subcategory->subcategoryName }}" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="{{ route('subcategory', $subcategory->slug) }}" />
    <meta property="og:image" content="{{ asset($subcategory->image) }}" />
    <meta property="og:description" content="{{ $subcategory->meta_description }}" />
    <meta property="og:site_name" content="{{ $subcategory->subcategoryName }}" />
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
                    <img src="{{ asset($sibling->image) }}" alt="{{ $sibling->subcategoryName }}" loading="lazy">
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
