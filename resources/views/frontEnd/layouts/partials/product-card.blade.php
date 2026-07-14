{{-- Shared product card — used by category, subcategory, and shop listings.
     Expects: $value (product with image/reviews/prosizes/procolors/category eager-loaded), $key (grid index). --}}
@php
    $avgRating   = $value->reviews->avg('ratting');
    $filledStars = floor($avgRating);
    $hasHalf     = $avgRating - $filledStars >= 0.5;
    $emptyStars  = 5 - $filledStars - ($hasHalf ? 1 : 0);
    $discount    = ($value->old_price && $value->old_price > $value->new_price)
                   ? round((($value->old_price - $value->new_price) * 100) / $value->old_price)
                   : 0;
@endphp
<div class="bilai-product-card">
    <div class="bilai-product-top">
        @if(!empty($value->product_badge))
        <span class="bilai-card-badge">{{ $value->product_badge }}</span>
        @else
        <span></span>
        @endif
        <button class="bilai-wishlist-btn" type="button" data-product-id="{{ $value->id }}" aria-label="Toggle wishlist" aria-pressed="false">
            <i class="far fa-heart"></i>
        </button>
    </div>
    <div class="bilai-product-image">
        <a href="{{ route('product', $value->slug) }}">
            <img src="{{ asset($value->image ? $value->image->image : '') }}"
                 alt="{{ $value->name }}"
                 loading="{{ $key === 0 ? 'eager' : 'lazy' }}" />
        </a>
        @if($value->sold && $value->sold > 0)
        <span class="bilai-cat-sold-pill">{{ $value->sold }} Sold</span>
        @endif
    </div>
    <div class="bilai-product-meta">
        <h3 class="bilai-product-title">
            <a href="{{ route('product', $value->slug) }}">{{ Str::limit($value->name, 55) }}</a>
        </h3>
        <div class="bilai-product-cat-rating">
            @if($value->category)
            <p class="bilai-product-category">{{ $value->category->name }}</p>
            @endif
            <div class="bilai-product-rating">
                @for($i = 0; $i < $filledStars; $i++)<i class="fas fa-star"></i>@endfor
                @if($hasHalf)<i class="fas fa-star-half-alt"></i>@endif
                @for($i = 0; $i < $emptyStars; $i++)<i class="far fa-star"></i>@endfor
            </div>
        </div>
        <div class="bilai-product-price">
            <div class="bilai-price-row">
                <span class="bilai-price-new">&#2547; {{ $value->new_price }}</span>
                @if($value->old_price)
                <del class="bilai-price-old">&#2547; {{ $value->old_price }}</del>
                @endif
            </div>
            @if($discount > 0)
            <span class="bilai-discount-badge">{{ $discount }}% OFF</span>
            @endif
        </div>
    </div>
    <div class="bilai-product-actions">
        @if(!$value->prosizes->isEmpty() || !$value->procolors->isEmpty())
            <a href="{{ route('product', $value->slug) }}" class="bilai-cart-btn">
                <i class="fa-solid fa-cart-shopping"></i>
            </a>
            <a href="{{ route('product', $value->slug) }}" class="bilai-buy-btn">Buy Now</a>
        @else
            <form action="{{ route('cart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $value->id }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="bilai-cart-btn cart_store" data-id="{{ $value->id }}">
                    <i class="fa-solid fa-cart-shopping"></i>
                </button>
            </form>
            <form action="{{ route('cart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $value->id }}" />
                <input type="hidden" name="qty" value="1" />
                <input type="hidden" name="order_now" value="1">
                <button type="submit" class="bilai-buy-btn">Buy Now</button>
            </form>
        @endif
    </div>
</div>
