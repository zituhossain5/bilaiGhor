@extends('frontEnd.layouts.master')

@section('title', 'Return &amp; Refund Policy | Bilai Ghor')
@section('canonical', 'https://bilaighor.bd/return-refund-policy')

@push('seo')
    <meta name="description" content="Read Bilai Ghor's return, replacement and refund policy for damaged, defective, expired, incorrect and unopened products, including proof requirements and refund timelines." />
@endpush

@push('css_after')
    <link rel="stylesheet" href="{{ asset_v('public/frontEnd/css/bilai-legal.css') }}" />
@endpush

@section('content')
<section class="bilai-legal">
    <div class="container">
        <article class="bilai-legal__article" lang="en">
            <nav class="bilai-cat-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span class="bilai-cat-breadcrumb-sep" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                <span class="bilai-cat-breadcrumb-current" aria-current="page">Return &amp; Refund Policy</span>
            </nav>

            <h1>Return &amp; Refund Policy</h1>
            <p class="bilai-legal__updated">Last updated: 10 November 2026</p>

            <h2>1. Overview</h2>
            <p>At Bilai Ghor, we want every customer to receive the correct product in good condition. If an item arrives damaged, broken, leaking, expired, incorrect, incomplete, or has another genuine product-related issue, please contact us within the applicable period.</p>
            <p>This policy explains when a product may be returned, replaced or refunded, what evidence may be needed, and who pays for return delivery. Requests are reviewed before a return, replacement or refund is approved. Applicable consumer rights remain unaffected.</p>

            <h2>2. Return Eligibility</h2>
            <p>You may request a return, replacement or refund if a product:</p>
            <ul>
                <li>Arrives broken, physically damaged, leaking or unusable because of damage present at delivery.</li>
                <li>Has seriously damaged packaging that affects the product.</li>
                <li>Is expired, or abnormally close to expiry when this was not disclosed before purchase.</li>
                <li>Is the wrong product, size or variant, or is materially different from what you ordered.</li>
                <li>Is missing from the parcel or has a genuine manufacturing defect.</li>
            </ul>
            <p>Eligibility depends on the circumstances and reasonable verification. For a change-of-mind return, the product must be new, unused and in its original condition. For a damage or defect claim, it may have been opened only as reasonably needed to discover the problem.</p>

            <h2>3. Return Window</h2>
            <ul>
                <li><strong>Product problems:</strong> Contact Bilai Ghor within <strong>3 calendar days of receiving the order</strong> to report damage, a defect, expiry, a wrong item or an incomplete order. This is the notification period for these issues.</li>
                <li><strong>Eligible unopened products:</strong> Request a change-of-mind return within <strong>7 calendar days of delivery</strong>, subject to the conditions below.</li>
            </ul>

            <h2>4. Damaged, Broken or Defective Products</h2>
            <p>Please contact us within 3 calendar days of receiving a product that is broken, leaking, damaged or affected by a manufacturing defect. Explain the issue and provide reasonable evidence. We may ask for the item or its packaging to be inspected before deciding on a replacement or refund.</p>
            <p>If a parcel looks severely damaged, opened or tampered with at delivery, inform the courier where possible and contact Bilai Ghor as soon as possible. If practical, photograph or record the parcel before opening it.</p>

            <h2>5. Expired Products</h2>
            <p>If a product is already expired when delivered, contact us within 3 calendar days and provide clear photos or video showing the product and its expiry date. After verification, the product is eligible for a replacement or refund. Do not use or feed an expired product to a pet.</p>
            <p>If a product is unusually close to its expiry date and this was not disclosed before purchase, contact us with the expiry information so we can review the claim.</p>

            <h2>6. Wrong or Missing Items</h2>
            <p>If we send the wrong product or variant, or an item is missing, contact us within 3 calendar days of delivery. After verification, Bilai Ghor may send the correct or missing item, arrange a replacement, or provide an appropriate refund.</p>

            <h2>7. Unboxing Video &amp; Proof</h2>
            <p>For delivery-related claims involving transit damage, breakage, leakage, missing items, incorrect products, expiry or packaging problems, please make a clear, continuous unboxing video immediately after receiving the parcel and before fully opening or using the products. The video should show:</p>
            <ul>
                <li>The unopened parcel and its shipping label or order information, where visible.</li>
                <li>The parcel being opened and all items received.</li>
                <li>The damaged, broken, leaking, expired, incorrect or missing-item issue.</li>
            </ul>
            <p>We may also request photos. This evidence helps verify whether a problem existed at delivery. If a video is unavailable, we may request other reasonable evidence and review the claim using the information available. Lack of reasonable evidence can make a delivery-damage or missing-item claim difficult to verify and may affect eligibility; a missing video alone does not automatically reject every claim.</p>

            <h2>8. Change-of-Mind Returns</h2>
            <p>You may request a return of an eligible product within 7 calendar days of delivery if you change your mind, provided that:</p>
            <ul>
                <li>The product is unused and unopened where sealed packaging applies.</li>
                <li>Original packaging, labels, accessories and any free items are intact.</li>
                <li>The product remains in resalable condition and is not listed as non-returnable.</li>
            </ul>
            <p>You are responsible for return delivery costs for a change-of-mind return. Original delivery charges are normally non-refundable in this case, unless applicable law requires otherwise.</p>

            <h2>9. Non-Returnable Products</h2>
            <p>For hygiene and safety reasons, change-of-mind returns are normally unavailable after opening or use for cat food (wet or dry), treats, litter, medicines, supplements, hygiene products, used grooming products, and other consumables with broken seals.</p>
            <p>These restrictions do not prevent a valid claim for an item delivered damaged, defective, expired, incorrect or otherwise eligible under this policy.</p>

            <h2>10. How to Request a Return</h2>
            <ol>
                <li>Contact Bilai Ghor within the applicable period.</li>
                <li>Provide your order or invoice number, name and phone number, product name, reason for the request, photos, and an unboxing video where relevant.</li>
                <li>We review the request and may ask for further reasonable information.</li>
                <li>If the request is approved, we provide return or replacement instructions.</li>
                <li>After receiving and checking the returned product where required, we arrange the approved replacement or refund.</li>
            </ol>
            <p><strong>Please do not send a product back before receiving return instructions from Bilai Ghor.</strong></p>

            <h2>11. Return Shipping Costs</h2>
            <p>Bilai Ghor will bear reasonable return or replacement delivery charges when a verified return is caused by a damaged, defective or expired product, a wrong product or variant, or a missing-item or fulfilment error attributable to us.</p>
            <p>For an eligible change-of-mind return, the customer pays the return delivery cost. An approved refund for a Bilai Ghor error or damaged, defective or expired item may include the product amount and applicable delivery or return costs, depending on the circumstances. Original and return delivery costs are normally non-refundable for change-of-mind returns unless required by law.</p>

            <h2>12. Replacement Policy</h2>
            <p>Where appropriate, we may offer a replacement instead of a refund, subject to stock availability. If the same product is unavailable, we may offer an alternative product with your agreement, or a refund.</p>

            <h2>13. Refund Method</h2>
            <p>Approved refunds will normally be returned through the original payment method where possible. For Cash on Delivery orders, we may request the bank or mobile financial service details necessary to issue the refund. We will never ask for your OTP, card PIN, mobile banking PIN or password.</p>

            <h2>14. Refund Processing Time</h2>
            <p>After an approved returned item has been received and inspected, Bilai Ghor will normally initiate the refund within <strong>5–7 business days</strong>. If we confirm that a return shipment is unnecessary, the period starts after approval. The time for the amount to appear in your account may vary by bank, payment provider or mobile financial service.</p>

            <h2>15. Order Cancellation</h2>
            <p>You may request cancellation before the order has been handed over to the courier. Once dispatched, cancellation may no longer be possible; this return policy applies where relevant.</p>

            <h2>16. Sale or Discounted Products</h2>
            <p>Sale and discounted products follow the same eligibility rules unless a specific exception was clearly disclosed before purchase.</p>

            <h2>17. Return Inspection</h2>
            <p>Returned products may be inspected before a refund or replacement is approved. We may decline a request if the item does not meet the stated eligibility requirements, and we will explain the reason. Examples include customer-caused damage after delivery, incorrect use or storage, an opened consumable without a qualifying defect, a food flavour disliked after opening, or a request outside the applicable period without a valid exception. Applicable consumer rights remain unaffected.</p>

            <h2>18. Contact Us</h2>
            <p>For return or refund assistance, use our official contact details or <a href="{{ route('contact') }}">Contact page</a>.</p>
            @php
                $returnPhone = optional($contact)->hotline ?: optional($contact)->phone;
                $returnEmail = optional($contact)->email ?: optional($contact)->hotmail;
            @endphp
            <ul>
                @if($returnPhone)
                    <li>Phone: <a href="tel:{{ preg_replace('/[^+0-9]/', '', $returnPhone) }}">{{ $returnPhone }}</a></li>
                @endif
                @if($returnEmail)
                    <li>Email: <a href="mailto:{{ $returnEmail }}">{{ $returnEmail }}</a></li>
                @endif
            </ul>
        </article>
    </div>
</section>
@endsection
