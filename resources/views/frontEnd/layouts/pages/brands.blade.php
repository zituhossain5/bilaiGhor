@extends('frontEnd.layouts.master')
@php
    $seoTitle = 'Cat Food & Accessory Brands in Bangladesh | Bilai Ghor';
    $seoDescription = 'Shop original cat food, litter and accessory brands in Bangladesh: '
        . $brands->take(6)->pluck('name')->implode(', ') . ' and more. Cash on delivery all over BD.';
    $canonicalUrl = route('brands');

    $breadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Brands', 'item' => $canonicalUrl],
        ],
    ];
    $collectionJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Shop by Brand',
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => $brands->count(),
            'itemListElement' => $brands->values()->map(fn ($b, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => route('brand.products', $b->slug),
                'name' => $b->name,
            ])->all(),
        ],
    ];
@endphp
@section('title', $seoTitle)
@section('canonical', $canonicalUrl)
@push('css_after')
    <link rel="stylesheet" href="{{ asset_v('public/frontEnd/css/bilai-listing-figma.css') }}" />
    <style>
        .bilai-brand-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin: 8px 0 40px; }
        .bilai-brand-card {
            display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 18px 12px;
            background: #fff; border: 1px solid #ecdcc5; border-radius: 14px; text-align: center;
            color: #3c2a1e; text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s;
        }
        .bilai-brand-card:hover, .bilai-brand-card:focus-visible { border-color: #e8861a; box-shadow: 0 6px 18px rgba(58, 31, 15, .08); transform: translateY(-2px); color: #3c2a1e; }
        .bilai-brand-logo { width: 96px; height: 96px; display: flex; align-items: center; justify-content: center; }
        .bilai-brand-logo img, .bilai-brand-logo picture { max-width: 100%; max-height: 100%; object-fit: contain; }
        .bilai-brand-name { font-weight: 600; font-size: 15px; line-height: 1.3; }
        .bilai-brand-count { font-size: 13px; color: #6b5d52; }
        @media (max-width: 575.98px) {
            .bilai-brand-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .bilai-brand-logo { width: 72px; height: 72px; }
        }
    </style>
@endpush
@push('seo')
    <meta name="robots" content="index,follow" />
    <meta name="description" content="{{ $seoDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:site_name" content="Bilai Ghor" />
    <script type="application/ld+json">{!! \App\Support\JsonLd::encode($breadcrumbJsonLd) !!}</script>
    <script type="application/ld+json">{!! \App\Support\JsonLd::encode($collectionJsonLd) !!}</script>
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container bilai-listing-container">

        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">Brands</span>
        </nav>

        <section class="bilai-listing-seo-header">
            <h1>Shop by Brand</h1>
            <p>Original cat food, litter and accessories from the brands cat parents trust, delivered all over Bangladesh.</p>
        </section>

        @if($brands->isNotEmpty())
        <div class="bilai-brand-grid">
            @foreach($brands as $b)
            <a href="{{ route('brand.products', $b->slug) }}" class="bilai-brand-card">
                <span class="bilai-brand-logo">
                    @if($b->image)
                        <x-picture :src="$b->image" alt="" loading="lazy" />
                    @else
                        <i class="fas fa-tag fa-2x" aria-hidden="true"></i>
                    @endif
                </span>
                <span class="bilai-brand-name">{{ $b->name }}</span>
                <span class="bilai-brand-count">{{ $b->products_count }} {{ \Illuminate\Support\Str::plural('product', $b->products_count) }}</span>
            </a>
            @endforeach
        </div>
        @else
        <div class="bilai-cat-empty">
            <i class="fas fa-box-open"></i>
            <p>No brands yet.</p>
        </div>
        @endif

    </div>
</div>
@endsection
