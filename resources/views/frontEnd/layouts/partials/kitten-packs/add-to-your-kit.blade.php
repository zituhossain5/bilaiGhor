@if($addons->count())
<section class="bilai-kp-section bilai-kp-addons">
    <div class="bilai-kp-addons-header">
        <h2 class="bilai-kp-title">Add to Your Kit</h2>
        <a href="{{ route('hotdeals') }}" class="bilai-kp-viewall-btn">View All Deals</a>
    </div>

    <div class="bilai-kp-addons-grid">
        @foreach($addons as $key => $value)
            @include('frontEnd.layouts.partials.product-card', ['value' => $value, 'key' => $key])
        @endforeach
    </div>
</section>
@endif
