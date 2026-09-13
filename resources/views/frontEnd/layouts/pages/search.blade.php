@extends('frontEnd.layouts.master')
@section('title', $keyword ?: 'Search')

@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('css_after')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-listing-figma.css') }}?v=9" />
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container bilai-listing-container">
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">
                {{ $keyword ? 'Search: ' . $keyword : 'Search Results' }}
            </span>
        </nav>

        <div class="bilai-cat-layout bilai-cat-layout--products-only">
            <main class="bilai-cat-main">
                @include('frontEnd.layouts.partials.listing-topbar')

                @if($products->count() > 0)
                <div class="bilai-cat-grid">
                    @foreach($products as $key => $value)
                        @include('frontEnd.layouts.partials.product-card', ['value' => $value, 'key' => $key])
                    @endforeach
                </div>
                @else
                <div class="bilai-cat-empty">
                    <i class="fas fa-search"></i>
                    <p>No products found for "{{ $keyword }}".</p>
                </div>
                @endif

                @if($products->hasPages())
                <div class="bilai-cat-pagination">
                    {{ $products->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </main>
        </div>
    </div>
</div>
@endsection

@push('script')
    @include('frontEnd.layouts.partials.listing-js')
@endpush
