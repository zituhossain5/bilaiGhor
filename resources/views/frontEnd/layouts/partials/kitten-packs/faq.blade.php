@php
    // Questions and answers transcribed from the Figma frame (node 1593-20670).
    $kpFaqs = [
        [
            'q' => 'Which kit is right for my kitten?',
            'a' => 'Just adopted and need the basics today? Pick Starter. Want one purchase that covers the first month, including health and comfort? Affordable is the most popular. Want higher-grade food, a hooded litter box and a carrier? Go Premium.',
        ],
        [
            'q' => 'My kitten is under 8 weeks old.',
            'a' => 'Very young kittens need milk replacer and bottle feeding, and these kits are not set up for that. Message us on WhatsApp and see a vet as soon as you can.',
        ],
        [
            'q' => 'Can I swap the food or the litter?',
            'a' => 'Yes. Message us on WhatsApp before you order and we will suggest swaps at a similar price.',
        ],
        [
            'q' => 'It is a street kitten. Is the kit still right?',
            'a' => 'Yes. Start with the dewormer and flea comb (already in Affordable and Premium, or add them below), and keep the kitten apart from other pets until a vet has checked it.',
        ],
        [
            'q' => 'How long will the food last?',
            'a' => 'For one kitten, about a week in Starter, four weeks in Affordable and five in Premium. It depends on age and appetite.',
        ],
        [
            'q' => 'How fast is delivery, and how do I pay?',
            'a' => 'Delivery takes 2–3 days in Dhaka and 3–5 days across Bangladesh. You can pay with bKash, Nagad, or Rocket. Outside of Dhaka, delivery charge has to be paid in advance.',
        ],
        [
            'q' => 'Can I return a kit?',
            'a' => 'Easy returns are available, terms apply. Message us if anything arrives damaged or wrong.',
        ],
    ];
@endphp

<section class="bilai-kp-section bilai-kp-faq-section">
    <div class="bilai-kp-faq-layout">
        <div class="bilai-kp-faq-main">
            <h2 class="bilai-kp-title">Questions New Kitten Parents Ask</h2>

            <div class="accordion bilai-kp-accordion" id="kittenPackFaq">
                @foreach($kpFaqs as $i => $faq)
                <div class="accordion-item bilai-kp-faq-item">
                    <h3 class="accordion-header" id="kpFaqHeading{{ $i }}">
                        <button class="accordion-button collapsed bilai-kp-faq-btn" type="button"
                                data-bs-toggle="collapse" data-bs-target="#kpFaqBody{{ $i }}"
                                aria-expanded="false" aria-controls="kpFaqBody{{ $i }}">
                            {{ $faq['q'] }}
                        </button>
                    </h3>
                    <div id="kpFaqBody{{ $i }}" class="accordion-collapse collapse"
                         aria-labelledby="kpFaqHeading{{ $i }}" data-bs-parent="#kittenPackFaq">
                        <div class="accordion-body bilai-kp-faq-body">{{ $faq['a'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <aside class="bilai-kp-help-card">
            <h3 class="bilai-kp-help-title">Not Sure Which Kit?</h3>
            <p class="bilai-kp-help-desc">Tell us your kitten's age and your budget. Our team replies in simple Bangla.</p>
            <a href="https://wa.me/8801997900505?text={{ rawurlencode('Hi! I need help choosing a kitten pack.') }}"
               target="_blank" rel="noopener" class="bilai-kp-whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
        </aside>
    </div>
</section>
