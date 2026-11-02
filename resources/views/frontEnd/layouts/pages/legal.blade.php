@extends('frontEnd.layouts.master')

@php
    $isPrivacy = $legalPage === 'privacy';
    $pageHeading = $isPrivacy ? 'Privacy Policy' : 'Terms & Conditions';
    $pageTitle = $pageHeading . ' | Bilai Ghor';
    $pageDescription = $isPrivacy
        ? 'Read the Bilai Ghor Privacy Policy to understand how customer information is collected, used and protected.'
        : 'Read the Bilai Ghor Terms & Conditions covering orders, products, pricing, delivery and use of the website.';
    $pageCanonical = 'https://bilaighor.bd/' . ($isPrivacy ? 'privacy-policy' : 'terms');
@endphp

@section('title', $pageTitle)
@section('canonical', $pageCanonical)

@push('seo')
    <meta name="description" content="{{ $pageDescription }}" />
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
                <span class="bilai-cat-breadcrumb-current" aria-current="page">{{ $pageHeading }}</span>
            </nav>

            <h1>{{ $pageHeading }}</h1>
            <p class="bilai-legal__updated">Last updated: 2 November 2026</p>

            @if($isPrivacy)
                <p>At Bilai Ghor, we respect your privacy and are committed to protecting the personal information you provide while using our website and services.</p>

                <h2>Information We Collect</h2>
                <p>We may collect information such as your name, phone number, email address, delivery address, and order information when you place an order, create an account, contact us, or use our services.</p>

                <h2>How We Use Your Information</h2>
                <p>We use your information to:</p>
                <ul>
                    <li>Process and deliver orders</li>
                    <li>Communicate with you about your orders</li>
                    <li>Provide customer support</li>
                    <li>Improve our website and services</li>
                    <li>Prevent fraud and misuse</li>
                    <li>Meet applicable legal or regulatory requirements</li>
                </ul>

                <h2>Payment Information</h2>
                <p>Payment transactions may be processed through third-party payment providers. Bilai Ghor does not intentionally store sensitive payment credentials unless required by the payment system being used.</p>

                <h2>Cookies and Analytics</h2>
                <p>We may use cookies and analytics technologies to understand website usage, improve performance, remember preferences, and support marketing or advertising activities.</p>

                <h2>Third-Party Services</h2>
                <p>We may share necessary information with trusted service providers such as delivery partners, payment providers, hosting providers, analytics services, and other services required to operate Bilai Ghor.</p>
                <p>We do not sell customer personal information.</p>

                <h2>Data Security</h2>
                <p>We take reasonable measures to protect customer information. However, no internet-based system can guarantee complete security.</p>

                <h2>Your Rights</h2>
                <p>You may contact us if you want to update, correct, or request information about the personal data associated with your account or orders.</p>

                <h2>Changes to This Policy</h2>
                <p>We may update this Privacy Policy when necessary. Updated versions will be published on this page.</p>

                <h2>Contact</h2>
                <p>If you have questions about this Privacy Policy, please contact Bilai Ghor through the contact information provided on our <a href="{{ route('contact') }}">website</a>.</p>
            @else
                <p>By using the Bilai Ghor website or placing an order, you agree to these Terms &amp; Conditions.</p>

                <h2>Products and Information</h2>
                <p>We try to provide accurate product descriptions, prices, images, stock information, and other details. Minor differences in packaging, appearance, or manufacturer information may occur.</p>

                <h2>Orders</h2>
                <p>Submitting an order does not guarantee acceptance. Bilai Ghor may contact the customer to verify an order and may cancel or refuse an order when necessary.</p>

                <h2>Prices</h2>
                <p>Product prices are displayed on the website and may change without prior notice. The price applicable to an order is the price shown during the ordering process, subject to any confirmed adjustments.</p>

                <h2>Availability</h2>
                <p>Products are subject to availability. If an ordered product becomes unavailable, we may contact the customer regarding replacement, adjustment, or cancellation.</p>

                <h2>Delivery</h2>
                <p>Delivery time may vary depending on location, courier service, product availability, weather, holidays, or other circumstances outside our direct control.</p>

                <h2>Customer Information</h2>
                <p>Customers are responsible for providing accurate contact and delivery information.</p>

                <h2>Returns, Refunds and Cancellations</h2>
                <p>Returns, refunds, exchanges, and cancellations are handled according to Bilai Ghor's applicable policies and the condition of the order or product.</p>

                <h2>Website Use</h2>
                <p>Users must not misuse the website, attempt unauthorized access, interfere with website operation, or use the service for unlawful activities.</p>

                <h2>Intellectual Property</h2>
                <p>Bilai Ghor's website content, branding, graphics, and original materials may not be copied or reused without permission, except where permitted by law.</p>

                <h2>Limitation</h2>
                <p>Bilai Ghor will make reasonable efforts to provide reliable services but cannot guarantee uninterrupted website availability or events outside its reasonable control.</p>

                <h2>Changes to These Terms</h2>
                <p>We may update these Terms &amp; Conditions when necessary. The latest version will be available on this page.</p>

                <h2>Contact</h2>
                <p>For questions about these Terms &amp; Conditions, please contact Bilai Ghor through the contact information provided on our <a href="{{ route('contact') }}">website</a>.</p>
            @endif
        </article>
    </div>
</section>
@endsection
