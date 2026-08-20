<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>@yield('title')</title>
		@if(!empty($seo->search_console_verification))
{!! $seo->search_console_verification ?? '' !!}
@endif
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" alt="Super Ecommerce Favicon" />
        <meta name="author" content="Super Ecommerce" />
        <link rel="canonical" href="" />
        @stack('seo') 
        @stack('css')
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/bootstrap.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/animate.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/all.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.carousel.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/owl.theme.default.min.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/mobile-menu.css')}}" />
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/select2.min.css')}}" />
        <!-- toastr css -->
        <link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets/css/toastr.min.css" />

        <link rel="stylesheet" href="{{asset('public/frontEnd/css/wsit-menu.css')}}" />
<link rel="stylesheet" href="{{ url('/style.css') }}?v=3">
<link rel="stylesheet" href="{{ url('/responsive.css') }}?v=3">
        {{-- BilaiGhor Figma — DM Sans + Mochiy Pop One fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=Mochiy+Pop+One&display=swap">
        {{-- BilaiGhor Figma — header & footer CSS --}}
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/bilai-header-footer.css')}}?v=37">
        <link rel="stylesheet" href="{{asset('public/frontEnd/css/main.css')}}" />
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
        <meta name="facebook-domain-verification" content="38f1w8335btoklo88dyfl63ba3st2e" />
        <style>
            .float{
            	position:fixed;
            	color:white;
            	width:60px;
            	height:60px;
            	bottom:40px;
            	left:40px;
            	background-color:#25d366;
            	color:#FFF;
            	border-radius:50px;
            	text-align:center;
                font-size:30px;
            	box-shadow: 2px 2px 3px #999;
                z-index:100;
            }
            
            .my-float{
            	margin-top:16px;
            }
            /* Media query to hide the .float class on screens 768px and smaller */
            @media (max-width: 767px) {
                .float {
                    display: none;
                }
            }
        </style>
		<style>
/* ========== Footer V2 — 100% Responsive (colors from General Setting) ========== */
.footer-v2 {
    background-color: {{ optional($generalsetting)->footer_color ?? '#222222' }};
    color: #e8e8e8;
    font-family: 'Poppins', sans-serif;
    position: relative;
    overflow: hidden;
}

.footer-v2 p, .footer-v2 a, .footer-v2 h5, .footer-v2 h6, .footer-v2 li, .footer-v2 span {
    color: #e8e8e8 !important;
}

/* Top accent line — Primary Color from setting */
.footer-v2__wave {
    height: 4px;
    width: 100%;
    background: linear-gradient(90deg, transparent 0%, {{ optional($generalsetting)->primary_color ?? '#667eea' }} 20%, {{ optional($generalsetting)->primary_color ?? '#667eea' }} 80%, transparent 100%);
    opacity: 0.9;
}

/* Main content — padding responsive (mobile first) */
.footer-v2__main {
    padding: 2rem 1rem 2rem;
    box-sizing: border-box;
}
@media (min-width: 360px) {
    .footer-v2__main { padding-left: 1.25rem; padding-right: 1.25rem; }
}
@media (min-width: 576px) {
    .footer-v2__main { padding: 3rem 1.5rem 2.5rem; }
}
@media (min-width: 992px) {
    .footer-v2__main { padding: 4rem 2rem 3rem; }
}

/* Grid: 1 col mobile → 2 col → 3 col → 4 col desktop (100% responsive) */
.footer-v2__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}
@media (min-width: 576px) {
    .footer-v2__grid { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
}
@media (min-width: 768px) {
    .footer-v2__grid { grid-template-columns: 1.5fr 1fr 1fr; }
}
@media (min-width: 992px) {
    .footer-v2__grid { grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 3rem; }
}

/* Brand block */
.footer-v2__brand { }
.footer-v2__logo {
    display: inline-block;
    margin-bottom: 1rem;
}
.footer-v2__logo img {
    height: 48px;
    width: auto;
    filter: brightness(0) invert(1);
}
@media (min-width: 768px) {
    .footer-v2__logo img { height: 52px; }
}
.footer-v2__tagline {
    font-size: 0.9375rem;
    line-height: 1.65;
    opacity: 0.9;
    margin-bottom: 1.5rem;
    max-width: 100%;
}
@media (min-width: 400px) {
    .footer-v2__tagline { max-width: 320px; }
}
.footer-v2__apps {
    margin-top: 1.25rem;
}
.footer-v2__apps-title {
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    opacity: 0.95;
}
.footer-v2__app-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.footer-v2__app-badges a {
    display: block;
}
.footer-v2__app-badges img {
    height: 40px;
    width: auto;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: transform 0.2s, box-shadow 0.2s;
}
.footer-v2__app-badges a:hover img {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

/* Link blocks */
.footer-v2__block { }
.footer-v2__title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    padding-bottom: 0.5rem;
    display: inline-block;
}
.footer-v2__title::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 28px;
    height: 2px;
    background-color: {{ optional($generalsetting)->primary_color ?? '#667eea' }};
    border-radius: 2px;
}
.footer-v2__links {
    list-style: none;
    padding: 0;
    margin: 0;
}
.footer-v2__links li {
    margin-bottom: 0.5rem;
}
.footer-v2__links a {
    text-decoration: none;
    font-size: 0.9375rem;
    opacity: 0.85;
    transition: opacity 0.2s, color 0.2s, padding-left 0.2s;
    display: inline-block;
}
.footer-v2__links a:hover {
    opacity: 1;
    color: {{ optional($generalsetting)->primary_color ?? '#667eea' }} !important;
    padding-left: 4px;
}

/* মোবাইলে Useful Link ও Link মেনু কনফ্লিক্ট রোধ — এক কলাম, স্পষ্ট আলাদা */
@media (max-width: 767px) {
    .footer-v2__grid {
        grid-template-columns: 1fr;
        gap: 0;
    }
    .footer-v2__block {
        width: 100%;
        min-width: 0;
        padding: 1rem 0;
        margin: 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .footer-v2__block:last-of-type {
        border-bottom: none;
    }
    .footer-v2__title {
        display: block;
        margin-bottom: 0.75rem;
    }
    .footer-v2__links {
        display: block;
    }
    .footer-v2__links li {
        display: block;
        margin-bottom: 0.5rem;
    }
    .footer-v2__links a {
        display: block;
        padding: 0.35rem 0;
        line-height: 1.4;
        white-space: normal;
        word-break: break-word;
    }
}

/* Newsletter + Social block */
.footer-v2__newsletter { }
.footer-v2__newsletter .footer-v2__title { margin-bottom: 0.75rem; }
.footer-v2__newsletter-desc {
    font-size: 0.8125rem;
    opacity: 0.85;
    margin-bottom: 1rem;
}
.footer-v2__form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    width: 100%;
    max-width: 100%;
}
@media (min-width: 400px) {
    .footer-v2__form { flex-direction: row; }
}
.footer-v2__form input {
    flex: 1;
    min-width: 0;
    padding: 0.65rem 1rem;
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 10px;
    background: rgba(255,255,255,0.08);
    color: #fff !important;
    font-size: 0.9375rem;
}
.footer-v2__form input::placeholder { color: rgba(255,255,255,0.5); }
.footer-v2__form button {
    padding: 0.65rem 1.25rem;
    border-radius: 10px;
    border: none;
    background-color: {{ optional($generalsetting)->primary_color ?? '#667eea' }};
    color: #fff !important;
    font-weight: 600;
    font-size: 0.9375rem;
    white-space: nowrap;
    transition: transform 0.2s, opacity 0.2s;
}
.footer-v2__form button:hover {
    transform: scale(1.02);
    opacity: 0.95;
}
.footer-v2__social-title {
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    opacity: 0.95;
}
.footer-v2__social-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.footer-v2__social-list a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff !important;
    transition: background 0.2s, transform 0.2s;
}
.footer-v2__social-list a:hover {
    background-color: {{ optional($generalsetting)->primary_color ?? '#667eea' }};
    transform: translateY(-2px);
}
.footer-v2__social-list i { font-size: 1.1rem; }

/* Bottom bar — Copyright Color from setting */
.footer-v2__bottom {
    background-color: {{ optional($generalsetting)->copyright_color ?? '#000000' }};
    padding: 1.25rem 1rem;
    border-top: 1px solid rgba(255,255,255,0.08);
}
@media (min-width: 576px) {
    .footer-v2__bottom { padding: 1.25rem 1.5rem; }
}
.footer-v2__copy-wrap {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    text-align: center;
    font-size: 0.875rem;
}
@media (min-width: 768px) {
    .footer-v2__copy-wrap {
        flex-direction: row;
        justify-content: center;
        flex-wrap: wrap;
        text-align: left;
    }
}
.footer-v2__copy-text { margin: 0; }
.footer-v2__copy-sep {
    display: none;
    margin: 0 0.75rem;
    opacity: 0.6;
}
@media (min-width: 768px) {
    .footer-v2__copy-sep { display: inline; }
}
.footer-v2__designer {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.footer-v2__designer-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.12);
    color: #fff !important;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.8125rem;
    transition: background 0.2s, color 0.2s;
}
.footer-v2__designer-link:hover {
    background: #fff;
    color: #1a1a2e !important;
}
.footer-v2__designer-link img {
    height: 18px;
    width: auto;
    display: block;
}

/* Mobile: space above fixed bottom nav + safe area */
@media (max-width: 768px) {
    .footer-v2__bottom { padding-bottom: 95px; }
    .footer-v2__main { padding-left: max(1rem, env(safe-area-inset-left)); padding-right: max(1rem, env(safe-area-inset-right)); }
}

@media (max-width: 480px) {
    .footer-v2__main {
        padding: 22px max(14px, env(safe-area-inset-right)) 18px max(14px, env(safe-area-inset-left));
    }

    .footer-v2__grid {
        gap: 12px;
    }

    .footer-v2__logo {
        margin-bottom: 10px;
    }

    .footer-v2__logo img {
        height: 42px;
    }

    .footer-v2__tagline,
    .footer-v2__newsletter-desc {
        margin-bottom: 12px;
        font-size: 13px;
        line-height: 1.55;
    }

    .footer-v2__block {
        padding: 12px 0;
    }

    .footer-v2__title {
        margin-bottom: 8px;
        font-size: 14px;
    }

    .footer-v2__title::after {
        margin-top: 6px;
    }

    .footer-v2__links {
        gap: 4px;
    }

    .footer-v2__links a {
        padding: 3px 0;
        font-size: 13px;
    }

    .footer-v2__apps,
    .footer-v2__social-title {
        margin-top: 12px;
    }

    .footer-v2__form input,
    .footer-v2__form button {
        height: 42px;
    }

    .footer-v2__bottom {
        padding: 12px 14px calc(92px + env(safe-area-inset-bottom));
    }
}

/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
    .copyright-wrapper {
        flex-direction: column; /* Stack on mobile */
        gap: 15px;
        text-align: center;
    }
    
    .designer-credit {
        justify-content: center;
    }
}
</style>
        <!-- ========== DataLayer Initialization (GTM-এর আগে) ========== -->
        @php
            $dl_page_type = Request::is('/') ? 'home'
                : (Request::is('product/*')  ? 'product_detail'
                : (Request::is('category/*') ? 'category'
                : (Request::is('cart')       ? 'cart'
                : (Request::is('checkout')   ? 'checkout'
                : (Request::is('customer/*') ? 'customer'
                : 'other')))));
        @endphp
        <script>
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                event:     'site_page_data',
                page_type: {{ json_encode($dl_page_type) }},
                page_url:  {{ json_encode(url()->current()) }},
                currency:  'BDT',
                site_name: {{ json_encode(optional($generalsetting)->name ?? '') }}
            });
        </script>
        <!-- ========== Google Tag Manager ========== -->
        @foreach($gtm_code ?? [] as $gtm)
        @php
            $gtm_container_id = preg_match('/^GTM-/i', trim($gtm->code))
                ? trim($gtm->code)
                : 'GTM-' . trim($gtm->code);
        @endphp
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm_container_id }}');</script>
        @endforeach
        <!-- ========== End Google Tag Manager ========== -->

        <!-- ========== Facebook Pixel (single init, multiple pixels support) ========== -->
        @if(isset($pixels) && $pixels->count() > 0)
        <script>
            !(function (f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function () {
                    n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                };
                if (!f._fbq) f._fbq = n;
                n.push = n; n.loaded = !0; n.version = "2.0"; n.queue = [];
                t = b.createElement(e); t.async = !0; t.src = v;
                s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
            })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
            @php
                $fbInitUser = [];
                if (\Illuminate\Support\Facades\Auth::guard('customer')->check()) {
                    $fbInitUser = \App\Support\EcommerceTrackingUser::forBrowserPixel(
                        \App\Support\EcommerceTrackingUser::fromCustomer(\Illuminate\Support\Facades\Auth::guard('customer')->user())
                    );
                }
            @endphp
            @foreach($pixels as $pixel)
            fbq('init', '{{{ $pixel->code }}}', @json($fbInitUser ?: new \stdClass()));
            @endforeach
            fbq('track', 'PageView');
        </script>
        @foreach($pixels as $pixel)
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{{ $pixel->code }}}&ev=PageView&noscript=1" />
        </noscript>
        @endforeach
        @endif
        <!-- ========== End Facebook Pixel ========== -->

        <!-- ========== TikTok Pixel (single init, multiple pixels support) ========== -->
        @if(isset($tiktok_pixels) && $tiktok_pixels->count() > 0)
        <script>
        !function (w, d, t) {
            w.TiktokAnalyticsObject=t;
            var ttq=w[t]=w[t]||[];
            ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];
            ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
            for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
            ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
            ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
                ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};
                var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;
                var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
        }(window, document, 'ttq');
        @foreach($tiktok_pixels as $tiktokP)
        ttq.load('{{ $tiktokP->code }}');
        @endforeach
        ttq.page();
        </script>
        @endif
        <!-- ========== End TikTok Pixel ========== -->
        @include('frontEnd.layouts.partials.ecom-tracking-lib')
        @include('frontEnd.layouts.partials.traffic-attribution')
    </head>
    <body class="gotop">
        @foreach($gtm_code ?? [] as $gtm)
        @php $gtm_noscript_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_noscript_id }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        @endforeach
        @php $subtotal = Cart::instance('shopping')->subtotal(); @endphp

        {{-- ================================================================
             Existing mobile sidebar menu — keep this div for mobile-menu.js
             ================================================================ --}}
        <div class="mobile-menu">
            <div class="mobile-menu-logo">
                <div class="logo-image">
                    <img src="{{asset($generalsetting->dark_logo)}}" alt="{{$generalsetting->name}}" />
                </div>
                <div class="mobile-menu-close">
                    <i class="fa fa-times"></i>
                </div>
            </div>
            <ul class="first-nav">
                @foreach($menucategories as $scategory)
                <li class="parent-category">
                    <a href="{{url('category/'.$scategory->slug)}}" class="menu-category-name">
                        <img src="{{asset($scategory->image)}}" alt="" class="side_cat_img" />
                        {{$scategory->name}}
                    </a>
                    @if($scategory->subcategories->count() > 0)
                    <span class="menu-category-toggle"><i class="fa fa-chevron-down"></i></span>
                    @endif
                    <ul class="second-nav" style="display: none;">
                        @foreach($scategory->subcategories as $subcategory)
                        <li class="parent-subcategory">
                            <a href="{{url('subcategory/'.$subcategory->slug)}}" class="menu-subcategory-name">{{$subcategory->subcategoryName}}</a>
                            @if($subcategory->childcategories->count() > 0)
                            <span class="menu-subcategory-toggle"><i class="fa fa-chevron-down"></i></span>
                            @endif
                            <ul class="third-nav" style="display: none;">
                                @foreach($subcategory->childcategories as $childcat)
                                <li class="childcategory"><a href="{{url('products/'.$childcat->slug)}}" class="menu-childcategory-name">{{$childcat->childcategoryName}}</a></li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- BilaiGhor Figma Header Start --}}
        {{-- === TOP INFO BAR — outside sticky header so it scrolls away naturally === --}}
        <div class="bilai-topbar">
            <div class="bilai-topbar__inner">
                <div class="bilai-topbar__left">
                    @php
                        $topbarPhone = optional($contact)->hotline ?? optional($contact)->phone ?? null;
                        $topbarEmail = optional($contact)->email ?? optional($contact)->mail ?? null;
                    @endphp
                    @if($topbarPhone)
                    <a href="tel:{{$topbarPhone}}">
                        {{-- phone --}}
                        <img src="{{ asset('public/frontEnd/images/topCallIcon.svg') }}" width="24" height="24" alt="">
                        Call Us: {{$topbarPhone}}
                    </a>
                    @endif
                    @if($topbarEmail)
                    <a href="mailto:{{$topbarEmail}}">
                    <img src="{{ asset('public/frontEnd/images/topMailIcon.svg') }}" width="24" height="24" alt="">
                      Email Us: {{$topbarEmail}}
                    </a>
                    @endif
                </div>
                <div class="bilai-topbar__right">
                    @foreach($socialicons as $si)
                    <a href="{{ $si->link }}" target="_blank" rel="noopener" title="{{ $si->title }}" class="bilai-topbar__social-icon">
                        <i class="{{ $si->icon }}"></i>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <header id="navbar_top">

            {{-- === MOBILE HEADER (visible on screens < 992px) === --}}
            <div class="bilai-mobile-header">
                <button class="bilai-mobile-header__icon-btn toggle" aria-label="Open Menu" style="background:transparent;border:none;color:#fff;font-size:22px;cursor:pointer;padding:4px 8px;">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <a href="{{route('home')}}" class="bilai-mobile-header__logo">
                    <img src="{{asset($generalsetting->dark_logo)}}" alt="{{$generalsetting->name}}" />
                </a>
                <a href="{{route('cart.index')}}" class="bilai-mobile-header__icon-btn" style="position:relative;">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="bilai-badge mobilecart-qty" style="position:absolute;top:-4px;right:-4px;font-size:10px;min-width:16px;height:16px;">{{ Cart::instance('shopping')->count() }}</span>
                </a>
            </div>

            {{-- === MOBILE SEARCH (visible on screens < 992px) === --}}
            <div class="bilai-mobile-search">
                <form action="{{route('search')}}">
                    <input type="text" placeholder="Search products..." value="" class="msearch_keyword msearch_click" name="keyword" autocomplete="off" />
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="search_result"></div>
            </div>

            {{-- === MAIN HEADER — logo + search + user/cart (visible ≥ 992px) === --}}
            <div class="bilai-main-header">
                <div class="bilai-main-header__inner">

                    {{-- Logo --}}
                    <a href="{{route('home')}}" class="bilai-main-header__logo">
                        <img src="{{asset($generalsetting->dark_logo)}}" alt="{{$generalsetting->name}}" />
                    </a>

                    {{-- Search --}}
                    <div class="bilai-main-header__search">
                        <form action="{{route('search')}}">
                            <input type="text" placeholder="Search for cat food, accessories and more..." class="search_keyword search_click" name="keyword" autocomplete="off" />
                            <button type="submit"><img src="{{ asset('public/frontEnd/images/searchIcon.svg') }}" width="24" height="24" alt=""> </button>
                        </form>
                        <div class="search_result"></div>
                    </div>

                    {{-- Login / Account (desktop only — cart is in the orange nav bar) --}}
                    <div class="bilai-main-header__actions">
                        @if(Auth::guard('customer')->user())
                        <a href="{{route('customer.account')}}" class="bilai-h-btn bilai-h-btn--user">
                            <i class="far fa-user"></i>
                            <span>{{Str::limit(Auth::guard('customer')->user()->name, 12)}}</span>
                        </a>
                        @elseif(($generalsetting?->vendor_enabled ?? 1) == 1 && Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole('vendor'))
                        <a href="{{route('vendor.dashboard')}}" class="bilai-h-btn bilai-h-btn--user">
                            <i class="fa-solid fa-store"></i><span>Vendor Panel</span>
                        </a>
                        @elseif(($generalsetting?->reseller_enabled ?? 1) == 1 && Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole('reseller') || (isset(Auth::guard('admin')->user()->role) && strtolower(Auth::guard('admin')->user()->role) === 'reseller')))
                        <a href="{{route('reseller.dashboard')}}" class="bilai-h-btn bilai-h-btn--user">
                            <i class="fa-solid fa-handshake"></i><span>Dashboard</span>
                        </a>
                        @else
                        <a href="{{route('customer.login')}}" class="bilai-h-btn bilai-h-btn--user">
                            <img src="{{ asset('public/frontEnd/images/topUserIcon.svg') }}" width="24" height="24" alt=""> Login / Register</span>
                        </a>
                        @endif
                        {{-- Hidden legacy AJAX target — cart_count() updates #cart-qty --}}
                        <span id="cart-qty" style="display:none;" aria-hidden="true"></span>
                    </div>
                </div>
            </div>

            {{-- === NAVIGATION BAR (orange bar — Figma layout) === --}}
            <nav class="bilai-nav" aria-label="Main navigation">
                <div class="bilai-nav__inner">

                    {{-- Mobile hamburger (< 992px only) --}}
                    <button class="bilai-nav__ham toggle" aria-label="Open Menu">
                        <i class="fa-solid fa-bars"></i>
                        <span style="font-size:13px;margin-left:6px;font-weight:600;">Menu</span>
                    </button>

                    {{-- BilaiGhor: LEFT — parent categories; mega panels are siblings of .bilai-nav__inner --}}
                    <ul class="bilai-nav__links">
                        @foreach($menucategories as $cat)
                        <li class="bilai-nav__item{{ $cat->subcategories->count() > 0 ? ' bilai-nav__item--has-mega' : '' }}"
                            @if($cat->subcategories->count() > 0)data-mega="bilai-mega-{{ $cat->id }}"@endif>
                            <a href="{{ route('category', $cat->slug) }}" class="{{ Request::segment(1) === 'category' && Request::segment(2) === $cat->slug ? 'active' : '' }}">
                                {{ $cat->name }}
                                @if($cat->subcategories->count() > 0)<i class="fa-solid fa-chevron-down bilai-nav-chevron"></i>@endif
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    {{-- BilaiGhor: RIGHT — static links + cart pill (≥ 992px) --}}
                    <div class="bilai-nav__right">
                        <ul class="bilai-nav__right-links">
                            <li><a href="{{ route('home') }}" class="{{ Route::is('home') ? 'active' : '' }}">Home</a></li>
                            @if(($generalsetting?->vendor_enabled ?? 1) == 1)
                            <li><a href="{{ route('sellers') }}" class="{{ Route::is('sellers') ? 'active' : '' }}">Sellers</a></li>
                            @endif
                            <li><a href="{{ route('contact') }}" class="{{ Route::is('contact') ? 'active' : '' }}">Contact</a></li>
                            <li><a href="{{ route('customer.order_track') }}" class="{{ Route::is('customer.order_track') ? 'active' : '' }}">Track Order</a></li>
                        </ul>
                        <a href="{{ route('cart.index') }}" class="bilai-nav__cart-pill">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="bilai-nav__cart-count mobilecart-qty">{{ Cart::instance('shopping')->count() }}</span>
                        </a>
                    </div>

                </div>{{-- /bilai-nav__inner --}}

                {{-- Mega panels: direct children of .bilai-nav so position:absolute top:100% works correctly --}}
                @foreach($menucategories as $cat)
                @if($cat->subcategories->count() > 0)
                <div class="bilai-mega-panel" id="bilai-mega-{{ $cat->id }}">
                    <div class="container">
                        <div class="bilai-mega-grid">
                            @foreach($cat->subcategories as $sub)
                            <a href="{{ route('subcategory', $sub->slug) }}" class="bilai-mega-link">{{ $sub->subcategoryName }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                @endforeach

            </nav>

        </header>
        {{-- BilaiGhor Figma Header End --}}
        <div id="content">
            @yield('content')
        </div>
            <!-- content end -->

{{-- BilaiGhor Figma Footer Start --}}
@php
    $footerSubcats = collect();
    foreach ($menucategories as $mc) {
        $footerSubcats = $footerSubcats->merge($mc->subcategories ?? []);
    }
    $footerSubcats = $footerSubcats->filter(fn ($s) => ($s->status ?? 1) == 1)->unique('id');
    $orderedFooterSubcats = collect();
    foreach (['dry food', 'wet food', 'treat', 'litter', 'grooming'] as $term) {
        $match = $footerSubcats->first(
            fn ($sub) => str_contains(strtolower($sub->subcategoryName ?? $sub->name ?? ''), $term)
        );
        if ($match && !$orderedFooterSubcats->contains('id', $match->id)) {
            $orderedFooterSubcats->push($match);
        }
    }
    $footerSubcats = $orderedFooterSubcats
        ->merge($footerSubcats->reject(fn ($sub) => $orderedFooterSubcats->contains('id', $sub->id)))
        ->take(5);

    $footerPages = collect($pages ?? [])->merge($pagesright ?? [])->unique('id');
    $findFooterPage = fn (string $name) => $footerPages->first(
        fn ($page) => strtolower(trim($page->name ?? '')) === strtolower($name)
    );
    $aboutPage = $findFooterPage('About Us');
    $bilaiParaPage = $findFooterPage('Bilai Para');

    $footerSocialAsset = function ($social) {
        $identity = strtolower(($social->title ?? '') . ' ' . ($social->icon ?? ''));
        foreach (['facebook', 'instagram', 'tiktok', 'youtube'] as $platform) {
            if (str_contains($identity, $platform)) {
                return asset("public/frontEnd/images/footer-figma/{$platform}.svg");
            }
        }
        return null;
    };

    $footerPhone = optional($contact)->hotline ?? optional($contact)->phone;
    $footerEmail = optional($contact)->email ?? optional($contact)->hotmail;
    $footerWhatsapp = optional($contact)->whatsapp;
@endphp
<footer class="bilai-footer">
    <div class="bilai-footer__main">
        <div class="bilai-footer__grid">

            {{-- Column 1: Brand / Opening Hours / Social --}}
            <div class="bilai-footer__brand">
                <a href="{{ url('/') }}" class="bilai-footer__logo">
                    <img src="{{ asset('public/frontEnd/images/footer-figma/bilai-ghor-logo.png') }}" alt="{{ optional($generalsetting)->name ?? 'Bilai Ghor' }}">
                </a>

                <div class="bilai-footer__group">
                    <h5 class="bilai-footer__title">Opening Hours</h5>
                    <p class="bilai-footer__hours">
                        {{ optional($generalsetting)->opening_hours ?? 'Saturday to Friday: 8 am to 2 pm' }}
                    </p>
                </div>

                <div class="bilai-footer__group bilai-footer__social-group">
                    <h5 class="bilai-footer__title">Social Links</h5>
                    <ul class="bilai-footer__social">
                        @foreach($socialicons as $si)
                        @php
                            $socialAsset = $footerSocialAsset($si);
                            $socialIdentity = strtolower(($si->title ?? '') . ' ' . ($si->icon ?? ''));
                            $socialHref = str_contains($socialIdentity, 'whatsapp')
                                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $si->link)
                                : $si->link;
                        @endphp
                        <li>
                            <a href="{{ $socialHref }}" target="_blank" rel="noopener" aria-label="{{ $si->title ?? 'Social' }}">
                                @if($socialAsset)
                                <img src="{{ $socialAsset }}" alt="">
                                @else
                                <span style="background-color: {{ $si->color ?: '#3c2a1e' }}"><i class="{{ $si->icon }}"></i></span>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Column 2: Popular Categories --}}
            <div class="bilai-footer__col">
                <h5 class="bilai-footer__title">Popular Categories</h5>
                <ul class="bilai-footer__links">
                    @forelse($footerSubcats as $sub)
                    <li><a href="{{ route('subcategory', $sub->slug) }}">{{ $sub->subcategoryName ?? $sub->name }}</a></li>
                    @empty
                    @foreach($menucategories->take(5) as $cat)
                    <li><a href="{{ route('category', $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endforeach
                    @endforelse
                </ul>
            </div>

            {{-- Column 3: Useful Link --}}
            <div class="bilai-footer__col">
                <h5 class="bilai-footer__title">Useful Link</h5>
                <ul class="bilai-footer__links">
                    <li><a href="{{ $aboutPage ? route('page', ['slug' => $aboutPage->slug]) : url('/') }}">About Us</a></li>
                    <li><a href="{{ route('blogs') }}">Blog</a></li>
                    <li><a href="{{ route('hotdeals') }}">Best Deals</a></li>
                    <li><a href="{{ route('shop') }}">Brands</a></li>
                    <li><a href="{{ $bilaiParaPage ? route('page', ['slug' => $bilaiParaPage->slug]) : route('blogs') }}">Bilai Para</a></li>
                </ul>
            </div>

            {{-- Column 4: Contact + Address --}}
            <div class="bilai-footer__col bilai-footer__contact-col">
                <div class="bilai-footer__group">
                    <h5 class="bilai-footer__title">Contact</h5>
                    <ul class="bilai-footer__contacts">
                        @if($footerPhone)
                        <li>
                            <span class="bilai-footer__ci"><img src="{{ asset('public/frontEnd/images/footer-figma/phone.png') }}" alt=""></span>
                            <a href="tel:{{ $footerPhone }}">{{ $footerPhone }}</a>
                        </li>
                        @endif
                        @if($footerEmail)
                        <li>
                            <span class="bilai-footer__ci"><img src="{{ asset('public/frontEnd/images/footer-figma/email.png') }}" alt=""></span>
                            <a href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a>
                        </li>
                        @endif
                        @if($footerWhatsapp)
                        <li>
                            <span class="bilai-footer__ci bilai-footer__ci--whatsapp"><img src="{{ asset('public/frontEnd/images/footer-figma/whatsapp.png') }}" alt=""></span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $footerWhatsapp) }}" target="_blank" rel="noopener">{{ $footerWhatsapp }}</a>
                        </li>
                        @endif
                    </ul>
                </div>

                @if(optional($contact)->address)
                <div class="bilai-footer__group">
                    <h5 class="bilai-footer__title">Address</h5>
                    <p class="bilai-footer__address">{{ $contact->address }}</p>
                </div>
                @endif
            </div>

        </div>
    </div>

    <div class="bilai-footer__bottom">
        <div class="bilai-footer__bottom-inner">
            <p class="bilai-footer__copy">
                &copy; {{ date('Y') }} {{ optional($generalsetting)->name ?? config('app.name') }}. All rights reserved.
            </p>
            <div class="bilai-footer__payments">
                <span class="bilai-footer__pay-label">Payment:</span>
                @foreach(['bKash', 'Nagad', 'Rocket', 'VISA', 'MasterCard'] as $pm)
                <span class="bilai-footer__pay-pill">{{ $pm }}</span>
                @endforeach
            </div>
        </div>
    </div>

</footer>
{{-- BilaiGhor Figma Footer End --}}

        {{-- Floating Cart - ক্লিক করলে সাইডবার কার্ট ওপেন হবে --}}
        <a href="javascript:void(0)" class="floating-cart-widget" id="floatingCartBtn" title="কার্ট দেখুন">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="floating-cart-badge mobilecart-qty">{{ Cart::instance('shopping')->count() }}</span>
        </a>

        {{-- Sidebar Cart Drawer - ডান দিক থেকে স্লাইড আউট --}}
        <div id="sidebarCartOverlay" class="sidebar-cart-overlay" onclick="closeSidebarCart()"></div>
        <div id="sidebarCartDrawer" class="sidebar-cart-drawer">
            <div id="sidebarCartContent">
                {{-- AJAX দিয়ে লোড হবে --}}
            </div>
        </div>

<div class="mobile_bottom_nav">
    <div class="nav_container">
        <a href="javascript:void(0)" class="nav_item toggle">
            <div class="icon_box">
                <i class="fa-solid fa-bars"></i>
            </div>
            <span class="nav_text">Category</span>
        </a>

        <a href="{{route('customer.order_track')}}" class="nav_item {{ Route::is('customer.order_track') ? 'active' : '' }}">
            <div class="icon_box">
                <i class="fa fa-truck"></i>
            </div>
            <span class="nav_text">Tracking</span>
        </a>

        <div class="nav_item home_wrapper">
            <a href="{{route('home')}}" class="home_fab {{ Route::is('home') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
            </a>
        </div>

        <a href="{{route('cart.index')}}" class="nav_item {{ Route::is('cart.index') ? 'active' : '' }}">
            <div class="icon_box">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart_badge mobilecart-qty">{{Cart::instance('shopping')->count()}}</span>
            </div>
            <span class="nav_text">Cart</span>
        </a>

        @if(Auth::guard('customer')->user())
            <a href="{{route('customer.account')}}" class="nav_item {{ Route::is('customer.account') ? 'active' : '' }}">
                <div class="icon_box">
                    <i class="fa-solid fa-user"></i>
                </div>
                <span class="nav_text">Account</span>
            </a>
        @elseif(($generalsetting?->vendor_enabled ?? 1) == 1 && Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole('vendor'))
            <a href="{{route('vendor.dashboard')}}" class="nav_item">
                <div class="icon_box">
                    <i class="fa-solid fa-store"></i>
                </div>
                <span class="nav_text">Vendor</span>
            </a>
        @elseif(($generalsetting?->reseller_enabled ?? 1) == 1 && Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole('reseller') || (isset(Auth::guard('admin')->user()->role) && strtolower(Auth::guard('admin')->user()->role) === 'reseller')))
            <a href="{{route('reseller.dashboard')}}" class="nav_item">
                <div class="icon_box">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <span class="nav_text">Reseller</span>
            </a>
        @else
            <a href="{{route('customer.login')}}" class="nav_item {{ Route::is('customer.login') ? 'active' : '' }}">
                <div class="icon_box">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <span class="nav_text">Login</span>
            </a>
        @endif
    </div>
</div>
<style>
/* --- Mobile Bottom Navigation Styles --- */
.mobile_bottom_nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    padding: 10px 0;
    border-radius: 20px 20px 0 0; /* উপরের কোনা গুলো একটু গোল হবে */
    display: none; /* ডেস্কটপে হাইড থাকবে */
}

/* শুধুমাত্র মোবাইলে দেখানোর জন্য */
@media (max-width: 768px) {
    .mobile_bottom_nav {
        display: block;
    }
}

.nav_container {
    display: flex;
    justify-content: space-around;
    align-items: flex-end; /* আইটেমগুলো নিচে সমান থাকবে */
    position: relative;
    min-height: 54px;
    padding: 0 8px;
}

/* সাধারণ মেনু আইটেম */
.nav_item {
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #6c757d; /* ডিফল্ট কালার */
    font-size: 12px;
    transition: all 0.3s ease;
    width: 20%;
}

.icon_box {
    position: relative;
    font-size: 20px;
    margin-bottom: 4px;
    transition: transform 0.2s;
}

.nav_text {
    font-weight: 500;
    line-height: 1.2;
}

/* হোভার এবং একটিভ কালার */
.nav_item:hover, .nav_item.active {
    color: #FF6600; /* আপনার ব্র্যান্ড কালার এখানে দিন */
}

.nav_item.active .icon_box {
    transform: translateY(-3px); /* একটিভ হলে একটু উপরে উঠবে */
}

/* --- Center Floating Home Button --- */
.home_wrapper {
    position: relative;
    bottom: 25px; /* স্বাভাবিকের চেয়ে উপরে থাকবে */
}

.home_fab {
    width: 52px;
    height: 52px;
    background: {{$generalsetting->primary_color}}; /* ব্র্যান্ড কালার */
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
    font-size: 21px;
    box-shadow: 0 8px 15px rgba(255, 102, 0, 0.4);
    border: 4px solid #fff; /* সাদা বর্ডার */
    transition: transform 0.3s ease;
}

.home_fab:hover {
    transform: scale(1.1); /* হোভারে বড় হবে */
    color: #fff;
}

/* --- Cart Badge Style --- */
.cart_badge {
    position: absolute;
    top: -8px;
    right: -10px;
    background: #ff0000;
    color: #fff;
    font-size: 10px;
    font-weight: bold;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 2px solid #fff;
}

@media (max-width: 480px) {
    .mobile_bottom_nav {
        min-height: calc(72px + env(safe-area-inset-bottom));
        padding: 7px 0 calc(7px + env(safe-area-inset-bottom));
        border-radius: 16px 16px 0 0;
    }

    .nav_item {
        min-width: 0;
        font-size: 10.5px;
    }

    .icon_box {
        font-size: 18px;
        margin-bottom: 3px;
    }

    .nav_text {
        max-width: 64px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .home_wrapper {
        bottom: 16px;
    }
}
</style>
        
<!-- ЁЯМР Floating Chat Widget -->
<div class="chat-widget">
  <!-- Main Toggle Button -->
  <div class="chat-toggle" id="chatToggle">
    <i class="fas fa-comment-dots"></i>
  </div>

  <!-- Chat Options -->
  <div class="chat-options" id="chatOptions">
          <a href="https://m.me/{{$generalsetting->facebook_page_username}}" target="_blank" class="chat-btn messenger" title="Messenger">
      <i class="fab fa-facebook-messenger"></i>
    </a>
          <a href="https://wa.me/{{ $contact->whatsapp }}" target="_blank" class="chat-btn whatsapp" title="WhatsApp">
      <i class="fab fa-whatsapp"></i>
    </a>
    <a href="tel:{{$contact->hotline}}" class="chat-btn hotline" title="Hotline">
      <i class="fas fa-phone"></i>
    </a>


  </div>
</div>

<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Floating Container */
.chat-widget {
  position: fixed;
  bottom: 60px; /* ⬅ Chat icon এখন 55px উপরে */
  right: 25px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

/* Main Toggle Button */
.chat-toggle {
  background: linear-gradient(135deg, #25D366, #128C7E);
  color: #fff;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.3s ease;
  font-size: 26px;
}
.chat-toggle:hover {
  transform: scale(1.1);
}

/* Chat Options Hidden by Default */
.chat-options {
  display: none;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 10px;
  align-items: flex-end;
}

/* Each Chat Button */
.chat-btn {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}
.chat-btn:hover {
  transform: translateY(-3px);
}

/* Button Colors */
.chat-btn.whatsapp { background: #25D366; }
.chat-btn.messenger { background: #0084FF; }
.chat-btn.instagram { background: #E1306C; }
.chat-btn.hotline { background: #FF3B30; }

/* Animation */
.chat-options.show {
  display: flex;
  animation: fadeInUp 0.3s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Tooltip */
.chat-btn[title]:hover::after {
  content: attr(title);
  position: absolute;
  right: 65px;
  background: #222;
  color: #fff;
  padding: 5px 10px;
  font-size: 13px;
  border-radius: 6px;
  white-space: nowrap;
  opacity: 0.9;
}

@media (max-width: 480px) {
  .chat-widget {
    right: 14px;
    bottom: calc(94px + env(safe-area-inset-bottom));
    z-index: 9997;
  }

  .chat-toggle {
    width: 46px;
    height: 46px;
    font-size: 20px;
  }

  .chat-options {
    gap: 8px;
    margin-bottom: 8px;
  }

  .chat-btn {
    width: 42px;
    height: 42px;
    font-size: 18px;
  }
}

</style>

{{-- Floating Cart + Sidebar Cart Styles --}}
<style>
.floating-cart-widget {
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    width: 52px;
    height: 70px;
    background: {{ optional($generalsetting)->primary_color ?? '#007bff' }};
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px 0 0 12px;
    box-shadow: -3px 0 15px rgba(0,123,255,0.4);
    z-index: 9998;
    text-decoration: none;
    transition: all 0.3s ease;
}
.floating-cart-widget:hover { color: #fff; width: 56px; }
.floating-cart-widget i { font-size: 24px; }
.floating-cart-badge {
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    min-width: 22px;
    height: 22px;
    background: #fff;
    color: {{ optional($generalsetting)->primary_color ?? '#007bff' }};
    font-size: 11px;
    font-weight: bold;
    border-radius: 50%;
    border: 2px solid {{ optional($generalsetting)->primary_color ?? '#007bff' }};
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}
@media (max-width: 768px) {
    .floating-cart-widget { top: 35%; width: 48px; height: 60px; z-index: 9999; }
    .floating-cart-widget i { font-size: 20px; }
    .floating-cart-badge { min-width: 20px; height: 20px; font-size: 10px; }
}
@media (max-width: 480px) {
    .floating-cart-widget {
        display: none;
    }
}
.sidebar-cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 10010;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
}
.sidebar-cart-overlay.active { opacity: 1; visibility: visible; }
.sidebar-cart-drawer {
    position: fixed;
    top: 0; right: 0;
    width: 380px;
    max-width: 95vw;
    height: 100%;
    height: 100dvh;
    background: #fff;
    z-index: 10011;
    transform: translateX(100%);
    transition: transform 0.35s ease;
    box-shadow: -5px 0 25px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.sidebar-cart-drawer.active { transform: translateX(0); }
#sidebarCartContent { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
.sidebar-cart-header {
    background: {{ optional($generalsetting)->primary_color ?? '#007bff' }};
    color: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.sidebar-cart-close { background: transparent; border: none; color: #fff; font-size: 22px; cursor: pointer; padding: 4px; line-height: 1; }
.sidebar-cart-title { font-size: 20px; font-weight: 700; margin: 0; flex: 1; }
.sidebar-cart-body { flex: 0 1 auto; min-height: 0; overflow-y: auto; padding: 16px; background: #f8f9fa; }
.sidebar-cart-item { display: flex; gap: 12px; padding: 12px; background: #fff; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.sidebar-cart-item-img { width: 70px; height: 85px; flex-shrink: 0; border-radius: 6px; overflow: hidden; }
.sidebar-cart-item-img img { width: 100%; height: 100%; object-fit: cover; }
.sidebar-cart-item-details { flex: 1; min-width: 0; position: relative; }
.sidebar-cart-item-title { font-weight: 600; color: #222; text-decoration: none; display: block; margin-bottom: 4px; font-size: 14px; line-height: 1.3; }
.sidebar-cart-item-title:hover { color: {{ optional($generalsetting)->primary_color ?? '#007bff' }}; }
.sidebar-cart-item-price { font-size: 13px; color: #444; margin: 0 0 4px 0; }
.sidebar-cart-item-savings { font-size: 12px; color: #28a745; font-weight: 500; margin: 0 0 8px 0; }
.sidebar-cart-item-remove { position: absolute; bottom: 0; right: 0; background: none; border: none; color: {{ optional($generalsetting)->primary_color ?? '#007bff' }}; cursor: pointer; padding: 4px; font-size: 14px; }
.sidebar-cart-qty { display: flex; align-items: center; margin: 8px 0 6px 0; width: fit-content; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }
.sidebar-qty-btn { width: 28px; height: 28px; border: none; background: #f0f0f0; color: #333; font-size: 18px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.sidebar-qty-btn:hover { background: {{ optional($generalsetting)->primary_color ?? '#007bff' }}; color: #fff; }
.sidebar-qty-num { min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; background: #fff; }
.sidebar-cart-empty { text-align: center; padding: 40px 20px; color: #888; }
.sidebar-cart-empty i { font-size: 48px; margin-bottom: 12px; opacity: 0.5; }
.sidebar-cart-footer { padding: 16px 20px; border-top: 1px solid #eee; background: #fff; flex-shrink: 0; }
.sidebar-cart-total { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
.sidebar-cart-total-label { font-size: 14px; color: #666; }
.sidebar-cart-total-amount { font-size: 20px; font-weight: 700; color: #222; }
.sidebar-cart-checkout-btn {
    display: block; width: 100%; padding: 14px 24px;
    background: {{ optional($generalsetting)->primary_color ?? '#007bff' }};
    color: #fff !important; text-align: center; font-weight: 600; font-size: 16px;
    border-radius: 6px; text-decoration: none; transition: opacity 0.2s;
}
.sidebar-cart-checkout-btn:hover { opacity: 0.9; color: #fff !important; }
@media (max-width: 768px) {
    .sidebar-cart-drawer { width: 100%; max-width: 100%; }
    .sidebar-cart-item-img { width: 60px; height: 72px; }
    .sidebar-qty-btn { width: 36px; height: 36px; }
    .sidebar-qty-num { min-width: 36px; height: 36px; }
}

/* Fly to cart animation */
.fly-to-cart-img {
    position: fixed;
    z-index: 99999;
    pointer-events: none;
    border-radius: 8px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.35);
    object-fit: cover;
    border: 2px solid #fff;
}
@keyframes cartBump {
    0% { transform: scale(1); }
    40% { transform: scale(1.25); }
    70% { transform: scale(0.95); }
    100% { transform: scale(1); }
}
.cart-bump-animate {
    animation: cartBump 0.45s ease-out;
}
.floating-cart-widget, .menu-bag a, .mobile_bottom_nav .nav_item .icon_box {
    transform-origin: center center;
}
</style>

<script>
/* Chat Toggle Open/Close */
document.getElementById("chatToggle").addEventListener("click", function() {
  document.getElementById("chatOptions").classList.toggle("show");
});

/* Sidebar Cart - খোলা/বন্ধ ও রিফ্রেশ */
function openSidebarCart() {
    document.getElementById("sidebarCartOverlay").classList.add("active");
    document.getElementById("sidebarCartDrawer").classList.add("active");
    document.body.style.overflow = "hidden";
    sidebarCartRefresh();
}
function closeSidebarCart() {
    document.getElementById("sidebarCartOverlay").classList.remove("active");
    document.getElementById("sidebarCartDrawer").classList.remove("active");
    document.body.style.overflow = "";
}
function sidebarCartRefresh() {
    $.get("{{ route('cart.sidebar') }}", function(html) {
        $("#sidebarCartContent").html(html);
        if (typeof feather !== "undefined") feather.replace();
    });
}
document.getElementById("floatingCartBtn")?.addEventListener("click", function(e) {
    e.preventDefault();
    openSidebarCart();
});
document.getElementById("sidebarCartOverlay")?.addEventListener("click", closeSidebarCart);

// When browser restores page from bfcache (back/forward), refresh cart count + sidebar
window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
        if (typeof cart_count === 'function') cart_count();
        if (typeof mobile_cart === 'function') mobile_cart();
        if (typeof sidebarCartRefresh === 'function') sidebarCartRefresh();
    }
});
</script>

<script>
/* BilaiGhor Nav Mega Menu — timer-based so gap between item and panel doesn't close menu */
(function () {
    if (window.innerWidth < 992) return;

    var nav    = document.querySelector('.bilai-nav');
    var items  = nav ? nav.querySelectorAll('.bilai-nav__item--has-mega') : [];
    if (!nav || !items.length) return;

    var closeTimer = null;

    function clearTimer() {
        if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
    }

    function closeAll() {
        clearTimer();
        nav.querySelectorAll('.bilai-nav__item--has-mega').forEach(function (i) { i.classList.remove('bilai-mega-active'); });
        nav.querySelectorAll('.bilai-mega-panel').forEach(function (p) { p.classList.remove('bilai-mega-open'); });
    }

    function scheduleClose() {
        clearTimer();
        closeTimer = setTimeout(closeAll, 120);
    }

    items.forEach(function (item) {
        var panelId = item.dataset.mega;
        var panel   = panelId ? document.getElementById(panelId) : null;

        item.addEventListener('mouseenter', function () {
            clearTimer();
            /* Switch to this item: deactivate others, activate current */
            nav.querySelectorAll('.bilai-nav__item--has-mega').forEach(function (i) { i.classList.remove('bilai-mega-active'); });
            nav.querySelectorAll('.bilai-mega-panel').forEach(function (p) { p.classList.remove('bilai-mega-open'); });
            item.classList.add('bilai-mega-active');
            if (panel) panel.classList.add('bilai-mega-open');
        });

        item.addEventListener('mouseleave', scheduleClose);

        if (panel) {
            panel.addEventListener('mouseenter', clearTimer);
            panel.addEventListener('mouseleave', scheduleClose);
        }
    });

    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target)) closeAll();
    });
})();
</script>

        <!-- /. fixed sidebar -->

        <div id="custom-modal"></div>
        <div id="page-overlay"></div>
        <div id="loading"><div class="custom-loader"></div></div>

        <script src="{{asset('public/frontEnd/js/jquery-3.6.3.min.js')}}"></script>
        <script>
            $(function() {
                $("#loading").hide();
                $(window).on("load", function() { $("#loading").hide(); });
                setTimeout(function() { $("#loading").hide(); }, 3000);
            });
        </script>
        <script src="{{asset('public/frontEnd/js/bootstrap.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/owl.carousel.min.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wsit-menu.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/mobile-menu-init.js')}}"></script>
        <script src="{{asset('public/frontEnd/js/wow.min.js')}}"></script>
        <script>
            new WOW().init();
        </script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <!-- feather icon -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
        <script>
            feather.replace();
        </script>
        <script src="{{asset('public/backEnd/')}}/assets/js/toastr.min.js"></script>
        {!! Toastr::message() !!} @stack('script')
		
		
		<script>
    $(document).ready(function() {
        $(".main_slider").owlCarousel({
            items: 1,
            loop: true,
            dots: false,
            autoplay: true,
            nav: true,
            autoplayHoverPause: false,
            margin: 0,
            mouseDrag: true,
            smartSpeed: 700,
            autoplayTimeout: 5000,
            navText: ["<i class='fa-solid fa-angle-left'></i>",
                "<i class='fa-solid fa-angle-right'></i>"
            ],
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".hotdeals-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: true,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: true,
                    loop: false,
                },
            },
        });
    });
</script>
<script>
    $(document).ready(function() {
        $(".category-slider").owlCarousel({
            margin: 15,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 3,
                    nav: true,
                },
                600: {
                    items: 5,
                    nav: false,
                },
                1000: {
                    items: 8,
                    nav: true,
                    loop: false,
                },
            },
        });

        $(".product_slider").owlCarousel({
            margin: 16,
            loop: true,
            dots: false,
            nav: true,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            navText: ["<i class='fa-solid fa-angle-left'></i>", "<i class='fa-solid fa-angle-right'></i>"],
            responsiveClass: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                },
                600: {
                    items: 2,
                    nav: false,
                },
                1000: {
                    items: 4,
                    nav: true,
                },
            },
        });
		$(".customer-review").owlCarousel({
            margin: 8,
            items: 6,
            loop: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 2,
                    nav: false,
                },
                600: {
                    items: 3,
                    nav: false,
                },
                1000: {
                    items: 5,
                    nav: false,
                },
            },
        });
    });
</script>
		
        <script>
            $(".quick_view").on("click", function () {
                var id = $(this).data("id");
                $("#loading").show();
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('quickview')}}",
                        success: function (data) {
                            if (data) {
                                $("#custom-modal").html(data);
                                $("#custom-modal").show();
                                $("#loading").hide();
                                $("#page-overlay").show();
                            }
                        },
                    });
                }
            });
        </script>
        <!-- quick view end -->
        <!-- cart js start -->
        <script>
            function runFlyToCart($sourceEl, onComplete) {
                var $flyImg;
                if ($sourceEl && $sourceEl.closest && $sourceEl.closest('.variant-modal-content').length)
                    $flyImg = $sourceEl.closest('.variant-modal-content').find('.variant-modal-img img').first();
                if (!$flyImg || !$flyImg.length)
                    $flyImg = $sourceEl.closest('.product_item, .wist_item, .search-item, .quick-product, .product-section, .main-details-page').find('.pro_img img, .quick-product-img img, .details_slider img, .block__pic, .dimage_item img').first();
                if (!$flyImg || !$flyImg.length) $flyImg = $('.details_slider img, .block__pic, #details_slider_main img').first();
                if (!$flyImg || !$flyImg.length) { if (typeof onComplete === 'function') onComplete(); return; }
                var rect = $flyImg[0].getBoundingClientRect();
                var $clone = $flyImg.clone().addClass('fly-to-cart-img').css({
                    position: 'fixed', width: 90, height: 110,
                    left: rect.left, top: rect.top, margin: 0, padding: 0, zIndex: 99999
                }).appendTo('body');
                var $target = $('#floatingCartBtn, .floating-cart-widget').first();
                if (!$target.length || !$target.is(':visible')) $target = $('.mobile_bottom_nav .cart_badge').closest('a').first();
                if (!$target.length) $target = $('.menu-bag a').first();
                var destRect = $target.length && $target.is(':visible') ? $target[0].getBoundingClientRect() : { left: $(window).width() - 60, top: $(window).height() / 2 - 40 };
                var endW = 36, endH = 44;
                var endLeft = destRect.left + ($target.length ? (destRect.width || 0) / 2 - endW / 2 : 0);
                var endTop = destRect.top + ($target.length ? (destRect.height || 0) / 2 - endH / 2 : 0);
                var midLeft = (rect.left + endLeft) / 2 - 20;
                var midTop = Math.min(rect.top, endTop) - 100;
                $clone.animate({ left: midLeft, top: midTop, width: 70, height: 85, opacity: 1 }, 300, 'swing', function() {
                    $(this).animate({ left: endLeft, top: endTop, width: endW, height: endH, opacity: 0.6 }, 350, 'swing', function() {
                        $clone.remove();
                        if ($target && $target.length) { $target.addClass('cart-bump-animate'); setTimeout(function() { $target.removeClass('cart-bump-animate'); }, 450); }
                        if (typeof onComplete === 'function') onComplete();
                    });
                });
            }
            $(document).on("click", ".addcartbutton", function (e) {
                var $btn = $(this);
                var id = $btn.data("id");
                var checkout = $btn.data("checkout");
                var qty = 1;
                if (id) {
                    e.preventDefault();
                    $.ajax({
                        cache: "false",
                        type: "GET",
                        url: "{{url('add-to-cart')}}/" + id + "/" + qty,
                        dataType: "json",
                        success: function (data) {
                            if (data) {
                                toastr.success('Success', 'Product add to cart successfully');
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                                runFlyToCart($btn, function() { if (typeof openSidebarCart === "function") openSidebarCart(); });
                            }
                        },
                    });
                }
                if(checkout){
                    window.location.href = '{{route('customer.checkout')}}'; 
                }
            });
            $(document).on("click", ".cart_store", function (e) {
                var $btn = $(this);
                var $form = $btn.closest('form');
                if (!$form.length) return;
                var id = $btn.data("id") || $form.find("input[name=id]").val();
                if (!id) return;
                e.preventDefault();
                $form.addClass('cart-ajax-submit');
                $.ajax({
                    type: "POST",
                    data: $form.serialize(),
                    url: $form.attr('action'),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    dataType: "json",
                    success: function (data) {
                        if (data && data.success) {
                            toastr.success('Success', 'Product add to cart successfully');
                            cart_count();
                            mobile_cart();
                            if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            var isOrderNow = $btn.is('[name="order_now"]') || $btn.hasClass('order_now_btn');
                            if (isOrderNow) {
                                window.location.href = '{{ route('customer.checkout') }}';
                                return;
                            }
                            runFlyToCart($btn, function() { if (typeof openSidebarCart === "function") openSidebarCart(); });
                        } else {
                            toastr.error(data && data.message ? data.message : 'Failed');
                        }
                    },
                    error: function(xhr) {
                        try {
                            var d = xhr.responseJSON;
                            if (d && !d.success) {
                                toastr.error(d.message || 'Failed');
                                return;
                            }
                        } catch(e) {}
                        $form.submit();
                    },
                    complete: function() { $form.removeClass('cart-ajax-submit'); }
                });
            });

            $(document).on("click", ".cart_remove", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.remove')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                cart_summary();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            $(document).on("click", ".cart_increment", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.increment')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            $(document).on("click", ".cart_decrement", function () {
                var id = $(this).data("id");
                if (id) {
                    $.ajax({
                        type: "GET",
                        data: { id: id },
                        url: "{{route('cart.decrement')}}",
                        success: function (data) {
                            if (data) {
                                $(".cartlist").html(data);
                                cart_count();
                                mobile_cart();
                                if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                            }
                        },
                    });
                }
            });

            function cart_count() {
                $.ajax({
                    type: "GET",
                    url: "{{route('cart.count')}}",
                    success: function (data) {
                        if (data) {
                            $("#cart-qty").html(data);
                        } else {
                            $("#cart-qty").empty();
                        }
                    },
                });
            }
            function mobile_cart() {
                $.ajax({
                    type: "GET",
                    url: "{{route('mobile.cart.count')}}",
                    success: function (data) {
                        if (data) {
                            $(".mobilecart-qty").html(data);
                        } else {
                            $(".mobilecart-qty").empty();
                        }
                    },
                });
            }
            function cart_summary() {
                $.ajax({
                    type: "GET",
                    url: "{{route('shipping.charge')}}",
                    dataType: "html",
                    success: function (response) {
                        $(".cart-summary").html(response);
                    },
                });
            }
        </script>
        <!-- cart js end -->
        <script>
            $(".search_click").on("keyup change", function () {
                var keyword = $(".search_keyword").val();
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function (products) {
                        if (products) {
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            });
            $(".msearch_click").on("keyup change", function () {
                var keyword = $(".msearch_keyword").val();
                $.ajax({
                    type: "GET",
                    data: { keyword: keyword },
                    url: "{{route('livesearch')}}",
                    success: function (products) {
                        if (products) {
                            $("#loading").hide();
                            $(".search_result").html(products);
                        } else {
                            $(".search_result").empty();
                        }
                    },
                });
            });
        </script>
        <!-- search js start -->
        <script></script>
        <script></script>
        <script>
            $(".district").on("change", function () {
                var id = $(this).val();
                $.ajax({
                    type: "GET",
                    data: { id: id },
                    url: "{{route('districts')}}",
                    success: function (res) {
                        if (res) {
                            $(".area").empty();
                            $(".area").append('<option value="">Select..</option>');
                            $.each(res, function (key, value) {
                                $(".area").append('<option value="' + key + '" >' + value + "</option>");
                            });
                        } else {
                            $(".area").empty();
                        }
                    },
                });
            });
        </script>
        <script>
            $(".toggle").on("click", function () {
                $("#page-overlay").show();
                $(".mobile-menu").addClass("active");
            });

            $("#page-overlay").on("click", function () {
                $("#page-overlay").hide();
                $(".mobile-menu").removeClass("active");
                $(".feature-products").removeClass("active");
            });

            $(".mobile-menu-close").on("click", function () {
                $("#page-overlay").hide();
                $(".mobile-menu").removeClass("active");
            });

            $(".mobile-filter-toggle").on("click", function () {
                $("#page-overlay").show();
                $(".feature-products").addClass("active");
            });
        </script>
        <script>
            $(document).ready(function () {
                $(".parent-category").each(function () {
                    const menuCatToggle = $(this).find(".menu-category-toggle");
                    const secondNav = $(this).find(".second-nav");

                    menuCatToggle.on("click", function () {
                        menuCatToggle.toggleClass("active");
                        secondNav.slideToggle("fast");
                        $(this).closest(".parent-category").toggleClass("active");
                    });
                });
                $(".parent-subcategory").each(function () {
                    const menuSubcatToggle = $(this).find(".menu-subcategory-toggle");
                    const thirdNav = $(this).find(".third-nav");

                    menuSubcatToggle.on("click", function () {
                        menuSubcatToggle.toggleClass("active");
                        thirdNav.slideToggle("fast");
                        $(this).closest(".parent-subcategory").toggleClass("active");
                    });
                });
            });
        </script>

        <script>
            var menu = new MmenuLight(document.querySelector("#menu"), "all");

            var navigator = menu.navigation({
                selectedClass: "Selected",
                slidingSubmenus: true,
                // theme: 'dark',
                title: "ক্যাটাগরি",
            });

            var drawer = menu.offcanvas({
                // position: 'left'
            });

            //  Open the menu.
            document.querySelector('a[href="#menu"]').addEventListener("click", (evnt) => {
                evnt.preventDefault();
                drawer.open();
            });
        </script>

        <script>
            /* BilaiGhor — scroll-compact header */
            (function () {
                var header = document.getElementById('navbar_top');
                if (!header) return;
                var topbarHeight = 50; // approx topbar height; compact kicks in after this
                window.addEventListener('scroll', function () {
                    if (window.pageYOffset > topbarHeight) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                }, { passive: true });
            })();

            $(window).scroll(function () {
                if ($(this).scrollTop() > 50) {
                    $(".scrolltop:hidden").stop(true, true).fadeIn();
                } else {
                    $(".scrolltop").stop(true, true).fadeOut();
                }
            });
            $(function () {
                $(".scroll").click(function () {
                    $("html,body").animate({ scrollTop: $(".gotop").offset().top }, "1000");
                    return false;
                });
            });
        </script>
        <script>
            $(".filter_btn").click(function(){
               $(".filter_sidebar").addClass('active');
               $("body").css("overflow-y", "hidden");
            })
            $(".filter_close").click(function(){
               $(".filter_sidebar").removeClass('active');
               $("body").css("overflow-y", "auto");
            })
        </script>
        
        
        @php
    $popup = App\Models\Popup::where('status', 1)->latest()->first();
@endphp

@if($popup)
@php
    $isSimpleImagePopup = empty(trim($popup->description ?? '')) && empty(trim($popup->btn_text ?? '')) && empty(trim($popup->offer_end_text ?? ''));
@endphp
<div class="modal fade" id="popShopModal" tabindex="-1" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog modal-dialog-centered popup-modal-compact {{ $isSimpleImagePopup ? 'popup-simple-fit' : '' }}">
        <div class="modal-content ps-content {{ $isSimpleImagePopup ? 'popup-simple-image' : '' }}">
            <button type="button" class="ps-close" data-bs-dismiss="modal" aria-label="বন্ধ">&times;</button>
            
            @if($isSimpleImagePopup)
                {{-- শুধু ইমেজ পপআপ (FABRILIFE/bKash স্টাইল) --}}
                <a href="{{ !empty(trim($popup->link ?? '')) ? $popup->link : 'javascript:void(0)' }}" {{ !empty(trim($popup->link ?? '')) ? 'target="_blank"' : '' }} class="popup-simple-link">
                    <img src="{{ url('public/'.$popup->image) }}" alt="{{ $popup->title }}" class="popup-simple-img">
                </a>
            @else
                {{-- পুরনো লেআউট (টেক্সট + ইমেজ) --}}
                <div class="ps-layout">
                    <div class="ps-text-section">
                        <h3 class="ps-brand">{{ $popup->title }}</h3>
                        <div class="ps-headline">
                            <p>{!! nl2br(e($popup->description)) !!}</p>
                        </div>
                        @if($popup->offer_end_text)
                        <p class="ps-deadline">{{ $popup->offer_end_text }}</p>
                        @endif
                        <a href="{{ $popup->link ?? '#' }}" class="ps-btn">
                            {{ $popup->btn_text ?? 'Shop the Sale' }}
                        </a>
                        <div class="ps-footer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                              <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
                            </svg>
                            <span>POWERED BY <strong>{{ $generalsetting->name ?? 'CommerceGurus' }}</strong></span>
                        </div>
                    </div>
                    <div class="ps-image-section">
                        <img src="{{ url('public/'.$popup->image) }}" alt="Offer Image">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    #popShopModal .modal-dialog.popup-modal-compact:not(.popup-simple-fit) {
        max-width: min(620px, 92vw) !important;
        margin-left: auto;
        margin-right: auto;
    }
    /* শুধু ইমেজ পপআপ: বক্স টানা না রেখে ছবির আসল লেআউটে ফিট — সাদা স্ট্রাইপ কমে */
    #popShopModal .modal-dialog.popup-modal-compact.popup-simple-fit {
        width: fit-content !important;
        max-width: min(560px, calc(100vw - 24px)) !important;
        margin-left: auto;
        margin-right: auto;
    }

    #popShopModal .modal-content.ps-content.popup-simple-image {
        background-color: transparent !important;
        box-shadow: none !important;
    }
    /* শুধু ইমেজ পপআপে হাল্কা ড্রপ ছায়া ছবিটিতে যেন কোণে কোণে রাউন্ড দেখায় */
    #popShopModal .modal-content.popup-simple-image .popup-simple-link {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
        border-radius: 14px;
        overflow: hidden;
    }

    #popShopModal .modal-content.ps-content {
        border: none !important;
        outline: none !important;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.14);
    }
    #popShopModal .modal-content.ps-content:focus,
    #popShopModal .modal-content.ps-content:focus-visible {
        outline: none !important;
    }

    .ps-content {
        border: none !important;
        border-radius: 12px;
        overflow: hidden;
        background-color: #fff;
        max-width: 100%;
        margin: 0 auto;
    }

    .ps-layout {
        display: flex;
        flex-direction: row;
        min-height: 340px;
    }

    .ps-text-section {
        width: 50%;
        padding: 34px 28px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: left;
        position: relative;
    }

    .ps-brand {
        color: #b93a3a;
        font-family: Georgia, 'Times New Roman', serif;
        font-weight: 700;
        font-size: 32px;
        margin-bottom: 12px;
        line-height: 1;
    }

    .ps-headline {
        color: #222;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.35;
        margin-bottom: 12px;
    }

    .ps-headline span, .ps-headline p {
        font-weight: 400;
        font-size: 14px;
        color: #555;
        margin-top: 10px;
    }

    .ps-deadline {
        color: #888;
        font-size: 13px;
        margin-bottom: 22px;
    }

    .ps-btn {
        background-color: #2c3e50;
        color: #fff !important;
        text-decoration: none;
        padding: 12px 22px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        border-radius: 2px;
        display: block;
        width: 100%;
        transition: 0.3s;
        border: none;
    }
    .ps-btn:hover {
        background-color: #000;
    }

    .ps-footer {
        margin-top: 28px;
        font-size: 9px;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
    }
    .ps-footer strong { color: #333; }

    .ps-image-section {
        width: 50%;
        position: relative;
        background: #f0f0f0;
    }
    .ps-image-section img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .popup-simple-image {
        padding: 0;
        width: fit-content;
        max-width: min(560px, calc(100vw - 24px));
        margin: 0 auto;
        border-radius: 14px !important;
        overflow: hidden;
        border: none !important;
        background: transparent;
    }
    .popup-simple-link {
        display: block;
        line-height: 0;
        text-decoration: none;
        border: none;
        outline: none;
        border-radius: inherit;
    }
    .popup-simple-link:focus,
    .popup-simple-link:focus-visible {
        outline: none !important;
    }
    .popup-simple-link[href="javascript:void(0)"] {
        cursor: default;
    }
    .popup-simple-img {
        width: auto;
        max-width: min(560px, calc(100vw - 24px));
        height: auto;
        max-height: min(80vh, 600px);
        object-fit: contain;
        display: block;
        border-radius: 14px !important;
        vertical-align: top;
        border: none;
        outline: none;
    }

    .ps-close {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #fff;
        border: none !important;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        font-size: 22px;
        line-height: 30px;
        color: #333;
        cursor: pointer;
        z-index: 1050;
        box-shadow: none;
        transition: 0.2s;
        outline: none !important;
    }
    .ps-close:hover {
        color: #b93a3a;
        transform: scale(1.06);
        background: rgba(255, 255, 255, 0.95);
    }
    .ps-close:focus-visible {
        outline: 2px solid rgba(185, 58, 58, 0.35) !important;
        outline-offset: 2px;
    }

    @media (max-width: 768px) {
        #popShopModal .modal-dialog.popup-modal-compact:not(.popup-simple-fit) {
            max-width: min(540px, 94vw) !important;
            margin: 10px auto;
        }
        #popShopModal .modal-dialog.popup-simple-fit {
            max-width: calc(100vw - 20px) !important;
        }
        .popup-simple-image {
            max-width: calc(100vw - 20px);
        }
        .popup-simple-img {
            max-width: calc(100vw - 20px);
            max-height: min(78vh, 520px);
        }
        .ps-layout {
            flex-direction: column-reverse;
            min-height: 0;
        }
        .ps-text-section { width: 100%; padding: 24px 20px; }
        .ps-image-section { width: 100%; height: 200px; }
        .ps-brand { font-size: 26px; }
        .popup-simple-img {
            max-height: min(72vh, 440px);
        }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ৩ ঘন্টা পর পর দেখাবে
        const hoursToWait = 3;  
        // সাইটে ঢোকার ২ সেকেন্ড পর দেখাবে
        const delayInSeconds = 2; 

        const timeLimit = hoursToWait * 60 * 60 * 1000;
        const lastShown = localStorage.getItem('popupLastShown');
        const now = new Date().getTime();

        if (!lastShown || (now - lastShown > timeLimit)) {
            setTimeout(function() {
                // Try opening with jQuery (Standard for Laravel themes)
                if (typeof jQuery != 'undefined') {
                    $('#popShopModal').modal('show');
                } 
                // Try opening with Bootstrap 5
                else if (typeof bootstrap != 'undefined') {
                    var myModal = new bootstrap.Modal(document.getElementById('popShopModal'));
                    myModal.show();
                }

                localStorage.setItem('popupLastShown', now);
            }, delayInSeconds * 1000);
        }
    });
</script>
@endif

        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('show_order_limit_modal'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // ডাইনামিক হোয়াটসঅ্যাপ নাম্বার (ডাটাবেস থেকে)
        var whatsappNumber = "{{ $contact->whatsapp ?? $contact->hotline ?? '8801700000000' }}"; 
        
        Swal.fire({
            title: '', 
            html: `
                <div class="custom-modal-content">
                    <div class="modal-header-custom">
                        <div class="header-left">
                            <i class="fas fa-exclamation-triangle header-icon"></i>
                            <span>Duplicate Order Detective Alert</span>
                        </div>
                        <i class="fas fa-times close-icon" onclick="Swal.close()"></i>
                    </div>

                    <div class="modal-body-custom">
                        <p>
                            <img src="https://img.icons8.com/emoji/48/000000/warning-emoji.png" style="width: 20px; vertical-align: text-bottom;"> 
                            <b>সতর্কতা!</b> আপনি ইতিমধ্যে এই পণ্যটির জন্য অর্ডার দিয়েছেন। নির্দিষ্ট সময়ের মধ্যে একই পণ্যের পুনরায় অর্ডার দেওয়া অনুমোদিত নয়। 
                            👉 আপনি যদি সত্যিই আবার অর্ডার করতে চান, তাহলে নিচে দেওয়া WhatsApp নম্বরে যোগাযোগ করুন:
                        </p>
                    </div>

                    <div class="modal-footer-custom">
                        <a href="https://wa.me/${whatsappNumber}?text=আমি একই পণ্য পুনরায় অর্ডার করতে চাই, অনুগ্রহ করে সাহায্য করুন।" target="_blank" class="btn-whatsapp-custom">
                            <i class="fab fa-whatsapp"></i> CONTACT ON WHATSAPP
                        </a>
                        <button onclick="Swal.close()" class="btn-close-custom">Close</button>
                    </div>
                </div>
            `,
            showConfirmButton: false, // ডিফল্ট বাটন বন্ধ রাখা হয়েছে
            background: 'transparent', // ডিফল্ট ব্যাকগ্রাউন্ড রিমুভ
            customClass: {
                popup: 'swal-no-padding'
            },
            allowOutsideClick: false
        });
    });
</script>

<style>
    /* পপ-আপ কন্টেইনার রিসেট */
    .swal-no-padding {
        padding: 0 !important;
        background: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    /* মেইন বক্স ডিজাইন */
    .custom-modal-content {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        font-family: 'Arial', sans-serif;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        max-width: 500px;
        margin: 0 auto;
    }

    /* লাল হেডার (ছবির মতো হুবহু) */
    .modal-header-custom {
        background-color: #b91c1c; /* গাঢ় লাল */
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        font-size: 18px;
        font-weight: bold;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-icon {
        color: #facc15; /* হলুদ আইকন */
        font-size: 20px;
    }

    .close-icon {
        cursor: pointer;
        opacity: 0.8;
        font-size: 20px;
        transition: 0.2s;
    }
    .close-icon:hover {
        opacity: 1;
    }

    /* বডি টেক্সট */
    .modal-body-custom {
        padding: 30px 25px;
        text-align: left;
        font-size: 15px;
        line-height: 1.6;
        color: #4b5563;
    }

    /* ফুটার এবং বাটন */
    .modal-footer-custom {
        padding: 0 25px 30px 25px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    /* হোয়াটসঅ্যাপ বাটন (সবুজ) */
    .btn-whatsapp-custom {
        background-color: #10b981;
        color: white !important;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: background 0.3s;
        border: none;
    }
    .btn-whatsapp-custom:hover {
        background-color: #059669;
    }

    /* ক্লোজ বাটন (লাল) */
    .btn-close-custom {
        background-color: #dc2626;
        color: white;
        padding: 10px 30px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 14px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: background 0.3s;
    }
    .btn-close-custom:hover {
        background-color: #b91c1c;
    }

    /* রেস্পন্সিভ ডিজাইন */
    @media (max-width: 450px) {
        .modal-footer-custom {
            flex-direction: column;
        }
        .btn-whatsapp-custom, .btn-close-custom {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endif
        @if(request()->is('customer/account', 'customer/orders', 'customer/order-details/*', 'customer/invoice*', 'customer/profile-edit', 'customer/change-password', 'customer/refunds*'))
        {{-- Authenticated account pages: if restored from back/forward cache (Safari ignores
             no-store for bfcache), force a fresh request so logged-out users are redirected. --}}
        <script>
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>
        @endif

        {{-- ═══════ Wishlist toggle (global: every product card heart) ═══════ --}}
        <style>
        /* BilaiGhor Wishlist Toggle Start */
        .bilai-wishlist-btn.active i,
        .bilai-na-wishlist.active i,
        .bpd-wishlist.active i { color: var(--bilai-primary, #F28C00); }
        /* BilaiGhor Wishlist Toggle End */
        </style>
        @php
            // One indexed query per page for the logged-in customer; hearts are
            // filled client-side from this set, so per-card Blade queries (N+1)
            // are never needed anywhere.
            $__wishlistIds = Auth::guard('customer')->check()
                ? \App\Models\Wishlist::where('customer_id', Auth::guard('customer')->id())->pluck('product_id')
                : collect();
        @endphp
        <script>
        /* BilaiGhor Wishlist Toggle */
        (function () {
            var ids = new Set(@json($__wishlistIds));
            var toggleUrl = '{{ route('customer.wishlist.toggle') }}';
            var csrf = '{{ csrf_token() }}';
            var busy = false;

            function paint(btn, active) {
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                var icon = btn.querySelector('i.fa-heart');
                if (icon) {
                    icon.classList.toggle('fas', active);
                    icon.classList.toggle('far', !active);
                }
                var label = btn.querySelector('.bpd-wishlist-label');
                if (label) { label.textContent = active ? 'In Wishlist' : 'Add to Wishlist'; }
            }

            // Every heart for the same product paints together (duplicate cards stay in sync).
            function paintAll(productId, active) {
                document.querySelectorAll('[data-product-id="' + productId + '"]').forEach(function (el) {
                    if (el.matches('.bilai-wishlist-btn, .bilai-na-wishlist, .bpd-wishlist')) { paint(el, active); }
                });
            }

            function fillInitial() {
                ids.forEach(function (id) { paintAll(id, true); });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fillInitial);
            } else {
                fillInitial();
            }

            // Delegated: works inside Owl Carousel clones and dynamically added cards.
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.bilai-wishlist-btn[data-product-id], .bilai-na-wishlist[data-product-id], .bpd-wishlist[data-product-id]');
                if (!btn) { return; }
                e.preventDefault();
                if (busy) { return; }
                busy = true;

                var productId = btn.dataset.productId;
                fetch(toggleUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(function (res) { return res.json().then(function (j) { return { status: res.status, body: j }; }); })
                .then(function (r) {
                    if (r.status === 401 && r.body.login_required) {
                        if (window.toastr) { toastr.info(r.body.message); }
                        else { alert(r.body.message); }
                        return;
                    }
                    if (!r.body.success) {
                        if (window.toastr) { toastr.error(r.body.message || 'Something went wrong.'); }
                        return;
                    }
                    if (r.body.in_wishlist) { ids.add(Number(productId)); } else { ids.delete(Number(productId)); }
                    paintAll(productId, r.body.in_wishlist);
                    if (window.toastr) { toastr.success(r.body.message); }
                })
                .catch(function () {
                    if (window.toastr) { toastr.error('Could not update wishlist. Please try again.'); }
                })
                .finally(function () { busy = false; });
            });
        }());
        </script>
    </body>
</html>


