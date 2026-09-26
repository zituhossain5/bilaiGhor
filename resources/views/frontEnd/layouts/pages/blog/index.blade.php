@extends('frontEnd.layouts.master')
@section('title','Blog')

@push('css_after')
    {{-- Listing pagination styles (.bilai-cat-pagination), same as the product listing pages. --}}
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-listing-figma.css') }}?v=9" />
@endpush

@section('content')
<section class="bilai-blog-section bilai-blog-page">
    <div class="container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">Blog</span>
        </nav>

        <div class="bilai-blog-header">
            <h1 class="bilai-testimonial-title">What You Should Know As<br>A Cat Parent</h1>
        </div>

        @if($blogs->count())
            <div class="bilai-blog-grid">
                @foreach($blogs as $blog)
                    <x-blog-card :blog="$blog" />
                @endforeach
            </div>

            @if($blogs->hasPages())
            <div class="bilai-cat-pagination">
                {{ $blogs->links('pagination::bootstrap-4') }}
            </div>
            @endif
        @else
            <p class="bilai-blog-empty">No blog posts yet — check back soon.</p>
        @endif

    </div>
</section>
@endsection
