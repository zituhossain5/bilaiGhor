{{--
    <img> with a WebP source when an up-to-date .webp copy exists (php artisan images:optimize,
    or created on upload), else the plain <img> exactly as before.
    `src`  : the path the views already pass to asset().
    `size` : optional [width, height] for the width/height attributes (e.g. ImageSize::of(...)).
    Every other attribute (alt, class, loading, fetchpriority, ...) goes onto the <img>; null
    values are left out. The <picture> is display:contents (bilai-header-footer.css), so the
    layout is unchanged.
--}}
@props(['src', 'size' => null])
@php
    $webp = \App\Support\WebpVariant::src($src);
    // srcset splits on whitespace and commas, so those must be percent-encoded (e.g. "Herro Banner.png").
    $webpUrl = $webp ? str_replace([' ', ','], ['%20', '%2C'], asset($webp)) : null;
    if ($size) {
        $attributes = $attributes->merge(['width' => $size[0], 'height' => $size[1]]);
    }
@endphp
@if($webp)
<picture class="bilai-picture"><source type="image/webp" srcset="{{ $webpUrl }}"><img src="{{ asset($src) }}" {{ $attributes }}></picture>
@else
<img src="{{ asset($src) }}" {{ $attributes }}>
@endif
