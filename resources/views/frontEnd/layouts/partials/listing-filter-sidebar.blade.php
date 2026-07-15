{{-- Shared listing filter sidebar (price / brand / weight / life stage / flavor).
     Expects: $filterBaseUrl (brand links), $min_price, $max_price,
              $brands + $brandCountMap + $activeBrandId,
              $weights + $weightCountMap + $selectedWeights,
              $lifeStages + $lifeStageCountMap + $selectedLifeStages,
              $flavors + $flavorCountMap + $selectedFlavors,
              optional $activeSubcatSlug (category page only). --}}
<form action="" method="GET" class="bilai-cat-filter-form" id="bilaiCatFilterForm">
    {{-- preserve sort, subcategory, and brand link filter --}}
    @if(request('sort'))
    <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif
    @if(!empty($activeSubcatSlug))
    <input type="hidden" name="subcategory" value="{{ $activeSubcatSlug }}">
    @endif
    @if($activeBrandId)
    <input type="hidden" name="brand" value="{{ $activeBrandId }}">
    @endif

    {{-- FILTER BY PRICE (Figma: title → slider → price label below) --}}
    <div class="bilai-cat-filter-block">
        <div class="bilai-cat-filter-title">Filter by Price</div>
        <div id="bilai-price-range" class="bilai-price-slider"></div>
        <div class="bilai-cat-price-display">
            Price: <strong>&#2547;<span id="bilai-min-val">{{ request('min_price', $min_price) }}</span></strong>
            &nbsp;–&nbsp;
            <strong>&#2547;<span id="bilai-max-val">{{ request('max_price', $max_price) }}</span></strong>
        </div>
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
            @if($brands->count() > 1)
            <div class="bilai-cat-filter-search">
                <input type="text" class="bilai-brand-search-input" id="bilaiBrandSearch"
                       placeholder="Find a Brand" autocomplete="off" aria-label="Search brands">
                <i class="fas fa-search"></i>
            </div>
            @endif
            <ul class="bilai-cat-attr-link-list" id="bilaiBrandList">
                @foreach($brands as $brand)
                @php
                    $isBrandActive = (string)$activeBrandId === (string)$brand->id;
                    $brandParams    = array_merge(request()->except(['brand', 'page']), $isBrandActive ? [] : ['brand' => $brand->id]);
                    $brandUrl       = $filterBaseUrl . '?' . http_build_query($brandParams);
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
