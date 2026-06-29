@extends('frontEnd.layouts.master')
@section('title', $category->name)
@push('css')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/jquery-ui.css') }}" />
@endpush
@push('seo')
    <meta name="app-url" content="{{ route('category', $category->slug) }}" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="{{ $category->meta_description }}" />
    <meta name="keywords" content="{{ $category->slug }}" />
    <meta name="twitter:card" content="product" />
    <meta name="twitter:site" content="{{ $category->name }}" />
    <meta name="twitter:title" content="{{ $category->name }}" />
    <meta name="twitter:description" content="{{ $category->meta_description }}" />
    <meta name="twitter:creator" content="bilaighor.bd" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta name="twitter:image" content="{{ asset($category->image) }}" />
    <meta property="og:title" content="{{ $category->name }}" />
    <meta property="og:type" content="product" />
    <meta property="og:url" content="{{ route('category', $category->slug) }}" />
    <meta property="og:image" content="{{ asset($category->image) }}" />
    <meta property="og:description" content="{{ $category->meta_description }}" />
    <meta property="og:site_name" content="{{ $category->name }}" />
@endpush

@section('content')
<div class="bilai-cat-page">
    <div class="container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">{{ $category->name }}</span>
        </nav>

        {{-- SUBCATEGORY CARDS — act as filters --}}
        @if($subcategories->count() > 0)
        <div class="bilai-cat-sub-row">
            @foreach($subcategories as $subcat)
            @php
                $isActiveSub = $activeSubcatSlug === $subcat->slug;
                // Build the filter URL: keep all current params except subcategory and page
                $otherParams = request()->except(['subcategory', 'page']);
                $subCardUrl  = $isActiveSub
                    ? route('category', $category->slug) . ($otherParams ? '?' . http_build_query($otherParams) : '')
                    : route('category', $category->slug) . '?' . http_build_query(array_merge($otherParams, ['subcategory' => $subcat->slug]));
            @endphp
            <a href="{{ $subCardUrl }}" class="bilai-cat-sub-card {{ $isActiveSub ? 'active' : '' }}">
                <div class="bilai-cat-sub-img-box">
                    @if($subcat->image)
                    <img src="{{ asset($subcat->image) }}" alt="{{ $subcat->subcategoryName }}" loading="lazy">
                    @else
                    <i class="fas fa-tag"></i>
                    @endif
                </div>
                <span>{{ $subcat->subcategoryName }}</span>
            </a>
            @endforeach
        </div>
        @endif

        {{-- MAIN LAYOUT --}}
        <div class="bilai-cat-layout">

            {{-- LEFT SIDEBAR --}}
            <aside class="bilai-cat-sidebar">
                <form action="" method="GET" class="bilai-cat-filter-form" id="bilaiCatFilterForm">
                    {{-- preserve sort, subcategory, and brand link filter --}}
                    @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if($activeSubcatSlug)
                    <input type="hidden" name="subcategory" value="{{ $activeSubcatSlug }}">
                    @endif
                    @if($activeBrandId)
                    <input type="hidden" name="brand" value="{{ $activeBrandId }}">
                    @endif

                    {{-- FILTER BY PRICE --}}
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title">Filter by Price</div>
                        <div class="bilai-cat-price-display">
                            Price: <strong>&#2547;<span id="bilai-min-val">{{ request('min_price', $min_price) }}</span></strong>
                            &nbsp;—&nbsp;
                            <strong>&#2547;<span id="bilai-max-val">{{ request('max_price', $max_price) }}</span></strong>
                        </div>
                        <div id="bilai-price-range" class="bilai-price-slider"></div>
                        <input type="hidden" name="min_price" id="bilai_min_price" value="{{ request('min_price', $min_price) }}">
                        <input type="hidden" name="max_price" id="bilai_max_price" value="{{ request('max_price', $max_price) }}">
                    </div>

                    {{-- BRAND (link-based, with count, scrollable) --}}
                    @if($brands->count() > 0)
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-brand-list">
                            Brand <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-brand-list">
                            <ul class="bilai-cat-attr-link-list">
                                @foreach($brands as $brand)
                                @php
                                    $isBrandActive = (string)$activeBrandId === (string)$brand->id;
                                    $brandParams    = array_merge(request()->except(['brand', 'page']), $isBrandActive ? [] : ['brand' => $brand->id]);
                                    $brandUrl       = route('category', $category->slug) . '?' . http_build_query($brandParams);
                                @endphp
                                <li>
                                    <a href="{{ $brandUrl }}" class="bilai-cat-attr-link {{ $isBrandActive ? 'active' : '' }}">
                                        <span class="bilai-cat-attr-name">{{ $brand->name }}</span>
                                        <span class="bilai-cat-count-badge">{{ str_pad($brandCountMap[$brand->id] ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    {{-- WEIGHT --}}
                    @if($weights->count() > 0)
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-weight-list">
                            Weight <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-weight-list">
                            <ul class="bilai-cat-check-list">
                                @foreach($weights as $w)
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="weight[]" value="{{ $w->id }}"
                                            class="bilai-cat-auto-submit"
                                            @if(in_array($w->id, $selectedWeights)) checked @endif>
                                        <span class="bilai-cat-check-name">{{ $w->name }}</span>
                                        <span class="bilai-cat-count-badge">{{ str_pad($weightCountMap[$w->id] ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    {{-- LIFE STAGE --}}
                    @if($lifeStages->count() > 0)
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-lifestage-list">
                            Life Stage <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-lifestage-list">
                            <ul class="bilai-cat-check-list">
                                @foreach($lifeStages as $ls)
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="life_stage[]" value="{{ $ls->id }}"
                                            class="bilai-cat-auto-submit"
                                            @if(in_array($ls->id, $selectedLifeStages)) checked @endif>
                                        <span class="bilai-cat-check-name">{{ $ls->name }}</span>
                                        <span class="bilai-cat-count-badge">{{ str_pad($lifeStageCountMap[$ls->id] ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    {{-- FLAVOR --}}
                    @if($flavors->count() > 0)
                    <div class="bilai-cat-filter-block">
                        <div class="bilai-cat-filter-title bilai-cat-filter-toggle" data-target="bilai-flavor-list">
                            Flavor <i class="fas fa-chevron-up bilai-cat-toggle-icon"></i>
                        </div>
                        <div class="bilai-cat-filter-body" id="bilai-flavor-list">
                            <ul class="bilai-cat-check-list">
                                @foreach($flavors as $fl)
                                <li>
                                    <label class="bilai-cat-check-label">
                                        <input type="checkbox" name="flavor[]" value="{{ $fl->id }}"
                                            class="bilai-cat-auto-submit"
                                            @if(in_array($fl->id, $selectedFlavors)) checked @endif>
                                        <span class="bilai-cat-check-name">{{ $fl->name }}</span>
                                        <span class="bilai-cat-count-badge">{{ str_pad($flavorCountMap[$fl->id] ?? 0, 2, '0', STR_PAD_LEFT) }}</span>
                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                </form>
            </aside>

            {{-- RIGHT PRODUCT AREA --}}
            <div class="bilai-cat-main">

                {{-- TOP BAR --}}
                <div class="bilai-cat-topbar">
                    <p class="bilai-cat-count">
                        @if($products->total() > 0)
                            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} results
                        @else
                            No products found
                        @endif
                    </p>
                    <form action="" method="GET" id="bilaiSortForm">
                        {{-- preserve all active filters in sort form --}}
                        @foreach(request()->except(['sort', 'page']) as $key => $val)
                            @if(is_array($val))
                                @foreach($val as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <select name="sort" class="bilai-cat-sort-select" id="bilaiSortSelect">
                            <option value="1" @if(request('sort')==1) selected @endif>Sort by Latest</option>
                            <option value="2" @if(request('sort')==2) selected @endif>Oldest First</option>
                            <option value="3" @if(request('sort')==3) selected @endif>Price: High to Low</option>
                            <option value="4" @if(request('sort')==4) selected @endif>Price: Low to High</option>
                            <option value="5" @if(request('sort')==5) selected @endif>Name: A–Z</option>
                            <option value="6" @if(request('sort')==6) selected @endif>Name: Z–A</option>
                        </select>
                    </form>
                </div>

                {{-- PRODUCT GRID --}}
                @if($products->count() > 0)
                <div class="bilai-cat-grid">
                    @foreach($products as $key => $value)
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
                            @if($discount > 0)
                            <span class="bilai-stock-badge">{{ $discount }}% OFF</span>
                            @else
                            <span></span>
                            @endif
                            <button class="bilai-wishlist-btn" type="button" aria-label="Add to wishlist">
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

{{-- BOTTOM DESCRIPTION ACCORDION --}}
@if($category->meta_description)
<div class="bilai-cat-desc-accordion">
    <div class="container">
        <button class="bilai-cat-desc-toggle" id="bilaiDescToggle" aria-expanded="false">
            View Full Description
            <i class="fas fa-chevron-down bilai-cat-desc-icon"></i>
        </button>
        <div class="bilai-cat-desc-body" id="bilaiDescBody" style="display:none;">
            <div class="bilai-cat-desc-content">
                {!! $category->meta_description !!}
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
    <script>
    $(function () {
        var minP   = {{ $min_price ?? 0 }};
        var maxP   = {{ $max_price ?? 10000 }};
        var curMin = {{ request('min_price') ?: ($min_price ?? 0) }};
        var curMax = {{ request('max_price') ?: ($max_price ?? 10000) }};

        $("#bilai-price-range").slider({
            range: true, step: 5, min: minP, max: maxP,
            values: [curMin, curMax],
            slide: function (event, ui) {
                $("#bilai-min-val").text(ui.values[0]);
                $("#bilai-max-val").text(ui.values[1]);
                $("#bilai_min_price").val(ui.values[0]);
                $("#bilai_max_price").val(ui.values[1]);
            },
            stop: function () { $("#bilaiCatFilterForm").submit(); }
        });
        $("#bilai-min-val").text(curMin);
        $("#bilai-max-val").text(curMax);

        $(".bilai-cat-auto-submit").on("change", function () {
            $("#bilaiCatFilterForm").submit();
        });

        $("#bilaiSortSelect").on("change", function () {
            $("#bilaiSortForm").submit();
        });

        $(".bilai-cat-filter-toggle").on("click", function () {
            var target = $(this).data("target");
            $("#" + target).slideToggle(200);
            $(this).find(".bilai-cat-toggle-icon").toggleClass("fa-chevron-up fa-chevron-down");
        });

        $("#bilaiDescToggle").on("click", function () {
            var expanded = $(this).attr("aria-expanded") === "true";
            $(this).attr("aria-expanded", String(!expanded));
            $(this).find(".bilai-cat-desc-icon").toggleClass("fa-chevron-down fa-chevron-up");
            $("#bilaiDescBody").slideToggle(250);
        });
    });
    </script>

    {{-- GA4 + Facebook Pixel --}}
    <script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        (function () {
            var categoryName = @json($category->name);
            var categorySlug = @json($category->slug);
            var categoryItems = [
                @foreach($products as $index => $value)
                {
                    item_id: "{{ $value->id }}",
                    item_name: @json($value->name),
                    price: {{ (float) $value->new_price }},
                    item_brand: @json(optional($value->brand)->name),
                    item_category: @json(optional($value->category)->name ?? $category->name),
                    item_list_id: categorySlug,
                    item_list_name: categoryName,
                    index: {{ $loop->iteration }},
                    slug: @json($value->slug),
                    currency: "BDT"
                }@if(!$loop->last),@endif
                @endforeach
            ];
            if (categoryItems.length) {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: "view_item_list",
                    ecommerce: {
                        item_list_id: categorySlug, item_list_name: categoryName,
                        items: categoryItems.map(function (item) {
                            return { item_id: item.item_id, item_name: item.item_name, index: item.index,
                                price: item.price, item_brand: item.item_brand, item_category: item.item_category,
                                item_list_id: item.item_list_id, item_list_name: item.item_list_name, currency: item.currency };
                        })
                    }
                });
            }
            if (typeof fbq === "function") {
                fbq("trackCustom", "ViewCategory", {
                    content_category: categoryName,
                    content_ids: categoryItems.map(function (i) { return i.item_id; }),
                    currency: "BDT"
                });
            }
        })();
    </script>
@endpush
