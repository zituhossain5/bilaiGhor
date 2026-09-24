@php
    $kpBenefits = [
        [
            'icon'  => 'why-icon-1.svg',
            'title' => 'Made for Heat and Humidity',
            'body'  => 'Odour-locking clumping litter, stainless bowls that stay clean, pouches you finish in one meal, and a cooling mat in Premium.',
        ],
        [
            'icon'  => 'why-icon-2.svg',
            'title' => 'Worms and Fleas Come First',
            'body'  => 'Many kittens arrive with worms or fleas. Affordable and Premium include a dewormer and a flea comb. Ask your vet for the right dose.',
        ],
        [
            'icon'  => 'why-icon-1.svg',
            'title' => 'Kitten Food, Not Table Scraps',
            'body'  => 'Growing kittens need the protein and taurine in kitten food. Rice with fish, or cow’s milk, can leave them short of nutrients or upset their stomach.',
        ],
        [
            'icon'  => 'why-icon-3.svg',
            'title' => 'Easy to Restock, Made for Flats',
            'body'  => 'Every food here is a brand we stock all year, so the next bag is one click away. Compact trays and mats suit small flats and tiled floors.',
        ],
    ];
@endphp

<section class="bilai-kp-section bilai-kp-why">
    <div class="bilai-kp-why-inner">
        <div class="bilai-kp-why-header">
            <h2 class="bilai-kp-why-title">Why These Kits work in <span>Bangladesh?</span></h2>
            <p class="bilai-kp-why-subtitle">Built around hot summers, humid monsoons and small flats, using only brands you can restock here.</p>
        </div>

        <div class="bilai-kp-why-grid">
            @foreach($kpBenefits as $benefit)
            <div class="bilai-kp-why-card">
                <span class="bilai-kp-why-icon">
                    <img src="{{ asset('public/frontEnd/images/kitten-packs/'.$benefit['icon']) }}"
                         alt="" width="24" height="24" loading="lazy" aria-hidden="true">
                </span>
                <div class="bilai-kp-why-text">
                    <h3 class="bilai-kp-why-card-title">{{ $benefit['title'] }}</h3>
                    <p class="bilai-kp-why-card-body">{{ $benefit['body'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
