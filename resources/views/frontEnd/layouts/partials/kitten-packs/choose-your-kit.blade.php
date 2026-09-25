@if($packs->count())
<section class="bilai-kp-section bilai-kp-kits">
    <div class="bilai-kp-header">
        <h2 class="bilai-kp-title">Choose Your Kit</h2>
        <p class="bilai-kp-subtitle">Every kit has food, a litter and the basics. Bigger kits add more food, comfort and health care.</p>
    </div>

    <div class="bilai-kp-grid">
        @foreach($packs as $pack)
        <article class="bilai-kp-card {{ $pack->is_dark ? 'is-dark' : '' }}">
            @if($pack->badge)
            <span class="bilai-kp-card-badge">{{ $pack->badge }}</span>
            @endif

            <button class="bilai-kp-wishlist" type="button" aria-label="Add {{ $pack->name }} to wishlist" aria-pressed="false">
                <i class="far fa-heart"></i>
            </button>

            <div class="bilai-kp-card-image">
                <img src="{{ $pack->image ? asset('public/'.$pack->image) : asset('public/no-image.png') }}"
                     alt="{{ $pack->name }}" loading="lazy">
            </div>

            <div class="bilai-kp-card-body">
                <h3 class="bilai-kp-card-title">{{ $pack->name }}</h3>

                <span class="bilai-kp-card-count">{{ $pack->item_count }} Items</span>

                <ul class="bilai-kp-item-list">
                    @foreach($pack->items as $item)
                    <li class="bilai-kp-item {{ $item->is_included ? '' : 'is-excluded' }}">
                        <span class="bilai-kp-item-dot" aria-hidden="true"></span>
                        <span class="bilai-kp-item-name">{{ $item->display_name }} {{ $item->quantity_label }}</span>
                    </li>
                    @endforeach
                </ul>

                <div class="bilai-kp-price-row">
                    <span class="bilai-kp-price">৳{{ number_format($pack->price, 0) }}</span>
                    @if($pack->old_price && $pack->old_price > $pack->price)
                    <del class="bilai-kp-old-price">৳{{ number_format($pack->old_price, 0) }}</del>
                    @endif
                </div>

                @if($pack->savings > 0)
                <p class="bilai-kp-savings">You save ৳{{ number_format($pack->savings, 0) }} vs buying separately</p>
                @endif

                <div class="bilai-kp-actions">
                    @if($pack->is_in_stock)
                        {{-- The pack is sold as itself (one cart line: pack name + pack price), like a normal product. --}}
                        <form action="{{ route('kitten.packs.cart', $pack->id) }}" method="POST" class="bilai-kp-cart-form">
                            @csrf
                            <input type="hidden" name="id" value="{{ \App\Helpers\KittenPackCart::ROW_PREFIX . $pack->id }}">
                            <input type="hidden" name="qty" value="1">
                            <button type="submit" class="bilai-kp-cart-btn cart_store" data-id="{{ \App\Helpers\KittenPackCart::ROW_PREFIX . $pack->id }}"
                                    aria-label="Add {{ $pack->name }} to cart">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </form>
                        {{-- order_now makes the pack endpoint redirect straight to checkout. --}}
                        <form action="{{ route('kitten.packs.cart', $pack->id) }}" method="POST" class="bilai-kp-buy-form">
                            @csrf
                            <input type="hidden" name="qty" value="1">
                            <input type="hidden" name="order_now" value="1">
                            <button type="submit" class="bilai-kp-buy-btn">Buy Now</button>
                        </form>
                    @else
                        {{-- An included product cannot cover one more pack (or an item still needs linking). --}}
                        <button type="button" class="bilai-kp-cart-btn" disabled aria-label="{{ $pack->name }} is out of stock">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                        <button type="button" class="bilai-kp-buy-btn" disabled>Stock Out</button>
                    @endif
                </div>
            </div>
        </article>
        @endforeach
    </div>

    <div class="bilai-kp-note">
        <strong>Note:</strong>
        <ul class="bilai-kp-note-list">
            <li>Inside Dhaka delivery charge upto 2kg is 70 taka then extra 20 taka will be added for each kg.</li>
            <li>Outside Dhaka delivery charge upto 2kg is 150 taka then extra 20 taka will be added for each kg.</li>
        </ul>
    </div>
</section>
@endif
