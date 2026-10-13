@extends('frontEnd.layouts.master')
@section('title','Contact Us')
@php
    $generalsetting = \App\Models\GeneralSetting::first();

    // Mascot for the left panel (PNG; a WebP copy is preferred when present). Nothing renders if missing.
    $contactCatPath = 'frontEnd/images/bilai-cat.png';
    $contactCatWebp = 'frontEnd/images/bilai-cat.webp';
    $contactCatExists = file_exists(public_path($contactCatPath));
    $contactCatWebpExists = file_exists(public_path($contactCatWebp));

    // wa.me needs the number in international format (01XXXXXXXXX → 8801XXXXXXXXX).
    $toWhatsAppUrl = function (?string $number) {
        $digits = preg_replace('/\D+/', '', (string) $number);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            $digits = '88' . $digits;
        }

        return 'https://wa.me/' . $digits;
    };

    // Social buttons: the same links as the footer ($socialicons), plus the contact WhatsApp
    // number when the social list doesn't already include WhatsApp.
    $contactSocials = collect($socialicons ?? [])
        ->filter(fn ($si) => !empty($si->icon) && !empty($si->link))
        ->map(function ($si) use ($toWhatsAppUrl) {
            $isWhatsApp = str_contains(strtolower(($si->title ?? '') . ' ' . $si->icon), 'whatsapp');

            return [
                'label' => $si->title ?: 'Social',
                'icon'  => $si->icon,
                'href'  => $isWhatsApp ? $toWhatsAppUrl($si->link) : $si->link,
                'whatsapp' => $isWhatsApp,
            ];
        })
        ->filter(fn ($s) => !empty($s['href']))
        ->values();

    if (!$contactSocials->contains('whatsapp', true) && $toWhatsAppUrl(optional($contact)->whatsapp)) {
        $contactSocials->prepend([
            'label' => 'WhatsApp',
            'icon'  => 'fa-brands fa-whatsapp',
            'href'  => $toWhatsAppUrl($contact->whatsapp),
            'whatsapp' => true,
        ]);
    }
@endphp

@push('css_after')
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bilai-contact.css') }}?v=2" />
@endpush

@section('content')
<section class="bilai-contact">
    <div class="container">

        {{-- BREADCRUMB --}}
        <nav class="bilai-cat-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="bilai-cat-breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="bilai-cat-breadcrumb-current">Contact Us</span>
        </nav>

        <div class="bilai-contact__card">

            {{-- LEFT: contact details --}}
            <aside class="bilai-contact__aside" aria-labelledby="contact-title">
                <div>
                    <h1 class="bilai-contact__title bn" id="contact-title">
                        <i class="fa-solid fa-paw" aria-hidden="true"></i>
                        আমাদের সাথে যোগাযোগ করুন
                    </h1>
                    <p class="bilai-contact__intro bn">আপনার যেকোনো প্রশ্ন বা মতামতের জন্য সরাসরি মেসেজ দিন। আমরা দ্রুত উত্তর দেব।</p>
                </div>

                <ul class="bilai-contact__info">
                    @if(optional($contact)->address)
                    <li class="bilai-contact__info-item">
                        <span class="bilai-contact__badge" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                        <span>
                            <span class="bilai-contact__info-label bn">ঠিকানা</span>
                            @if($contact->maplink)
                                <a class="bilai-contact__info-value" href="{{ $contact->maplink }}" target="_blank" rel="noopener noreferrer">{{ $contact->address }}</a>
                            @else
                                <span class="bilai-contact__info-value">{{ $contact->address }}</span>
                            @endif
                        </span>
                    </li>
                    @endif

                    @if(optional($contact)->hotline)
                    <li class="bilai-contact__info-item">
                        <span class="bilai-contact__badge" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                        <span>
                            <span class="bilai-contact__info-label bn">হটলাইন</span>
                            <a class="bilai-contact__info-value" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->hotline) }}">{{ $contact->hotline }}</a>
                        </span>
                    </li>
                    @endif

                    @if(optional($contact)->email)
                    <li class="bilai-contact__info-item">
                        <span class="bilai-contact__badge" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                        <span>
                            <span class="bilai-contact__info-label bn">ইমেইল</span>
                            <a class="bilai-contact__info-value" href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                        </span>
                    </li>
                    @endif
                </ul>

                @if($contactSocials->isNotEmpty())
                <ul class="bilai-contact__social" aria-label="Social links">
                    @foreach($contactSocials as $social)
                    <li>
                        <a href="{{ $social['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}">
                            <i class="{{ $social['icon'] }}" aria-hidden="true"></i>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif

                @if($contactCatExists)
                    <div class="bilai-contact__cat">
                        <picture>
                            @if($contactCatWebpExists)
                                <source srcset="{{ asset('public/' . $contactCatWebp) }}" type="image/webp">
                            @endif
                            <img src="{{ asset('public/' . $contactCatPath) }}" alt="BilaiGhor mascot"
                                 width="1055" height="973" loading="lazy">
                        </picture>
                    </div>
                @endif
            </aside>

            {{-- RIGHT: form --}}
            <div class="bilai-contact__main">
                <h2 class="bilai-contact__form-title bn">একটি মেসেজ পাঠান</h2>
                <p class="bilai-contact__form-sub bn"><span class="bilai-contact__req">*</span> চিহ্নিত ঘরগুলো পূরণ করা আবশ্যক</p>

                @if(session('success'))
                    <div class="bilai-contact__alert" role="status">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form class="bilai-contact__form" action="{{ route('frontend.contact.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="0">

                    {{-- Honeypot: off-screen (not display:none, which some bots skip). Must stay empty. --}}
                    <div class="bilai-contact__hp" aria-hidden="true">
                        <label for="contact_website">Website</label>
                        <input type="text" name="website" id="contact_website" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="contact_full_name">সম্পূর্ণ নাম <span class="bilai-contact__req" aria-hidden="true">*</span></label>
                            <input type="text" name="full_name" id="contact_full_name"
                                   class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name') }}" placeholder="আপনার নাম"
                                   autocomplete="name" maxlength="255" required
                                   @error('full_name') aria-invalid="true" aria-describedby="contact_full_name_error" @enderror>
                            @error('full_name')
                                <div class="invalid-feedback" id="contact_full_name_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="contact_mobile">মোবাইল নাম্বার <span class="bilai-contact__req" aria-hidden="true">*</span></label>
                            <input type="tel" name="mobile" id="contact_mobile" inputmode="numeric"
                                   class="form-control @error('mobile') is-invalid @enderror"
                                   value="{{ old('mobile') }}" placeholder="০১xxx-xxxxxx"
                                   autocomplete="tel" maxlength="20" required
                                   @error('mobile') aria-invalid="true" aria-describedby="contact_mobile_error" @enderror>
                            @error('mobile')
                                <div class="invalid-feedback" id="contact_mobile_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="contact_email">ইমেইল এড্রেস</label>
                            <input type="email" name="email" id="contact_email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="example@mail.com"
                                   autocomplete="email"
                                   @error('email') aria-invalid="true" aria-describedby="contact_email_error" @enderror>
                            @error('email')
                                <div class="invalid-feedback" id="contact_email_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="contact_subject">বিষয়</label>
                            <input type="text" name="subject" id="contact_subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" placeholder="কি বিষয়ে জানতে চান?"
                                   autocomplete="off" maxlength="255"
                                   @error('subject') aria-invalid="true" aria-describedby="contact_subject_error" @enderror>
                            @error('subject')
                                <div class="invalid-feedback" id="contact_subject_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="contact_details">মেসেজ লিখুন <span class="bilai-contact__req" aria-hidden="true">*</span></label>
                            <textarea name="details" id="contact_details" rows="5"
                                      class="form-control @error('details') is-invalid @enderror"
                                      placeholder="আপনার মেসেজ লিখুন..." required
                                      @error('details') aria-invalid="true" aria-describedby="contact_details_error" @enderror>{{ old('details') }}</textarea>
                            @error('details')
                                <div class="invalid-feedback" id="contact_details_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="bilai-contact__submit">
                                মেসেজ পাঠান <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>
@endsection
