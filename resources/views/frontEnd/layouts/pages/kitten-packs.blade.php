@extends('frontEnd.layouts.master')
@section('title', 'Kitten Packs — Starter Kits for Your New Kitten | Bilai Ghor')

@section('content')
<div class="bilai-kitten-packs">
    @include('frontEnd.layouts.partials.kitten-packs.hero')

    <div class="container">
        @include('frontEnd.layouts.partials.kitten-packs.choose-your-kit')
        @include('frontEnd.layouts.partials.kitten-packs.why-bangladesh')
        @include('frontEnd.layouts.partials.kitten-packs.add-to-your-kit')
        @include('frontEnd.layouts.partials.kitten-packs.faq')
    </div>
</div>
@endsection
