@extends('frontEnd.layouts.master')
@php
    $pageTitle = (filled($page->title) ? trim($page->title) : $page->name) . ' | ' . (optional($generalsetting)->name ?: 'Bilai Ghor');
    $pageDescription = Str::limit((string) \App\Support\SeoText::plain($page->description), 155, '…', preserveWords: true);
@endphp
@section('title', $pageTitle)
@section('canonical', route('page', $page->slug))
@push('seo')
    @if($pageDescription)
    <meta name="description" content="{{ $pageDescription }}" />
    @endif
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $pageTitle }}" />
    <meta property="og:url" content="{{ route('page', $page->slug) }}" />
@endpush
@section('content')

<section class="comn_sec">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="cmn_menu">
                    <ul>
                        @foreach($cmnmenu as $key=>$value)
                        <li>
                            <a href="{{route('page',$value->slug)}}">{{$value->name}}</a>
                        </li>
                        @endforeach
                        <li>
                            <a href="{{route('contact')}}">Contact Us</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="createpage-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-content">
                    <div class="page-title mb-2">
                        <h5>{{$page->title}}</h5>
                    </div>
                    <div class="page-description">
                        {!! $page->description !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
