@extends('frontEnd.layouts.master')
@section('title', $blog->title)

@push('seo')
    <meta name="robots" content="index,follow" />
    @if($seo['description'])
    <meta name="description" content="{{ $seo['description'] }}" />
    @endif
    <link rel="canonical" href="{{ $seo['canonical'] }}" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="{{ $seo['title'] }}" />
    @if($seo['description'])
    <meta property="og:description" content="{{ $seo['description'] }}" />
    @endif
    <meta property="og:url" content="{{ $seo['canonical'] }}" />
    <meta property="og:site_name" content="{{ optional($generalsetting)->name ?? 'Bilai Ghor' }}" />
    @if($seo['image'])
    <meta property="og:image" content="{{ $seo['image'] }}" />
    <meta property="og:image:alt" content="{{ $seo['image_alt'] }}" />
    @endif
    <meta property="article:published_time" content="{{ $blog->created_at->toIso8601String() }}" />
    <meta property="article:modified_time" content="{{ $blog->updated_at->toIso8601String() }}" />
    <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}" />
    <meta name="twitter:title" content="{{ $seo['title'] }}" />
    @if($seo['description'])
    <meta name="twitter:description" content="{{ $seo['description'] }}" />
    @endif
    @if($seo['image'])
    <meta name="twitter:image" content="{{ $seo['image'] }}" />
    @endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => Str::limit($blog->title, 110, ''),
        'description' => $seo['description'],
        'image' => $seo['image'] ? [$seo['image']] : null,
        'datePublished' => $blog->created_at->toIso8601String(),
        'dateModified' => $blog->updated_at->toIso8601String(),
        'mainEntityOfPage' => $seo['canonical'],
        'url' => $seo['canonical'],
        'commentCount' => $commentCount,
        'publisher' => ['@type' => 'Organization', 'name' => optional($generalsetting)->name ?? 'Bilai Ghor'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blogs')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $blog->title, 'item' => $seo['canonical']],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@push('css')
<style>
.blog-details img {
    max-width: 100%;
    border-radius: 6px;
}

.blog-meta {
    font-size: 14px;
    color: #777;
    margin-bottom: 15px;
}

.sidebar-blog {
    padding: 10px;
}

.sidebar-blog img {
    width: 80px;
    height: 65px;
    object-fit: cover;
    border-radius: 6px;
}

.sidebar-blog-title {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    color: #222;
    text-decoration: none;
}

.sidebar-blog-title:hover {
    color: #0d6efd;
}

.sidebar-blog-meta {
    font-size: 12px;
    color: #777;
}

/* ── Share row ── */
.blog-share {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px;
}
.blog-share-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--bilai-body-title, #2a1505);
    margin-right: 4px;
}
.blog-share-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    border: 1px solid var(--bilai-border, #dccab2);
    background: var(--bilai-light, #fffdf8);
    color: var(--bilai-brown, #3a1f0f);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.blog-share-btn:hover,
.blog-share-btn:focus-visible {
    background: var(--bilai-primary, #e8861a);
    border-color: var(--bilai-primary, #e8861a);
    color: #fff;
}
.blog-share-btn i { font-size: 15px; }

/* ── Comments ── */
.blog-comments {
    margin-top: 40px;
    padding-top: 28px;
    border-top: 1px solid var(--bilai-border, #dccab2);
}
.blog-comments-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--bilai-body-title, #2a1505);
    margin-bottom: 18px;
}
.blog-comment-list,
.blog-comment-replies {
    list-style: none;
    margin: 0;
    padding: 0;
}
.blog-comment {
    display: flex;
    gap: 12px;
    padding: 16px 0;
    border-bottom: 1px solid #efe4d4;
}
.blog-comment-replies .blog-comment {
    border-bottom: none;
    padding: 14px 16px;
    margin-top: 12px;
    background: var(--bilai-cream, #fdfcf8);
    border: 1px solid #efe4d4;
    border-radius: var(--bilai-radius-sm, 8px);
}
.blog-comment-avatar {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #fff3df;
    color: var(--bilai-primary-dark, #c96f00);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
}
.blog-comment-avatar.is-admin {
    background: var(--bilai-brown, #3a1f0f);
    color: #fff;
}
.blog-comment-main { flex: 1; min-width: 0; }
.blog-comment-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.blog-comment-name {
    font-weight: 700;
    color: var(--bilai-body-title, #2a1505);
}
.blog-comment-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--bilai-primary, #e8861a);
    color: var(--bilai-body-title, #2a1505);
}
.blog-comment-date {
    font-size: 12px;
    color: var(--bilai-muted, #7a6a5e);
}
.blog-comment-body {
    color: var(--bilai-text, #4f4f4f);
    font-size: 15px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}
.blog-comment-body a { color: var(--bilai-primary-dark, #c96f00); text-decoration: underline; }
.blog-comments-empty { color: var(--bilai-muted, #7a6a5e); }
.blog-comment-notice {
    padding: 12px 16px;
    border-radius: var(--bilai-radius-sm, 8px);
    background: #e4f4e5;
    color: #0f6b3d;
    margin-bottom: 16px;
    font-size: 14px;
}
.blog-comment-form {
    margin-top: 28px;
    padding: 22px;
    background: var(--bilai-cream, #fdfcf8);
    border: 1px solid var(--bilai-border, #dccab2);
    border-radius: var(--bilai-radius-md, 14px);
}
.blog-comment-form h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--bilai-body-title, #2a1505);
    margin-bottom: 4px;
}
.blog-comment-form .form-text { color: var(--bilai-muted, #7a6a5e); }
.blog-comment-form .form-control {
    border-color: var(--bilai-border, #dccab2);
    background: #fff;
}
.blog-comment-form .form-control:focus {
    border-color: var(--bilai-primary, #e8861a);
    box-shadow: 0 0 0 .15rem rgba(232, 134, 26, .18);
}
.blog-comment-submit {
    background: var(--bilai-primary, #e8861a);
    border: 1px solid var(--bilai-primary, #e8861a);
    color: #fff;
    font-weight: 700;
    padding: 9px 24px;
    border-radius: 999px;
}
.blog-comment-submit:hover {
    background: var(--bilai-primary-dark, #c96f00);
    border-color: var(--bilai-primary-dark, #c96f00);
    color: #fff;
}
/* Honeypot: off-screen, not display:none (some bots skip hidden fields). */
.blog-comment-hp {
    position: absolute !important;
    left: -10000px !important;
    width: 1px;
    height: 1px;
    overflow: hidden;
}
</style>
@endpush

@section('content')
<section class="blog-details product-section">
    <div class="container">

        {{-- 🔹 Breadcrumb --}}
        <div class="sorting-section mb-4">
            <div class="row">
                <div class="col-sm-12">
                    <div class="category-breadcrumb d-flex align-items-center">
                        <a href="{{ route('home') }}">Home</a>
                        <span>/</span>
                        <a href="{{ route('blogs') }}">Blog</a>
                        <span>/</span>
                        <strong>{{ Str::limit($blog->title, 40) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            {{-- 🔹 Main Blog Content --}}
            <div class="col-md-8">

                <h1 class="h3 mb-2">{{ $blog->title }}</h1>

                <div class="blog-meta">
                {{ $blog->created_at->format('d M Y') }}
                    &nbsp; | &nbsp;
                    👁 {{ $blog->views }} Views
                    &nbsp; | &nbsp;
                    <a href="#comments" class="text-reset">💬 {{ $commentCount }} {{ Str::plural('Comment', $commentCount) }}</a>
                </div>

                {{-- 🔹 Share --}}
                @php
                    $shareUrl = $seo['canonical'];
                    $shareTitle = $blog->title;
                @endphp
                <div class="blog-share" role="group" aria-label="Share this post"
                     data-share-url="{{ $shareUrl }}" data-share-title="{{ $shareTitle }}">
                    <span class="blog-share-label">Share this post</span>
                    <a class="blog-share-btn" target="_blank" rel="noopener noreferrer"
                       href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}"
                       aria-label="Share on Facebook">
                        <i class="fab fa-facebook-f" aria-hidden="true"></i><span>Facebook</span>
                    </a>
                    <a class="blog-share-btn" target="_blank" rel="noopener noreferrer"
                       href="https://wa.me/?text={{ rawurlencode($shareTitle . ' ' . $shareUrl) }}"
                       aria-label="Share on WhatsApp">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span>
                    </a>
                    <button type="button" class="blog-share-btn js-blog-native-share" data-platform="Instagram" aria-label="Share on Instagram">
                        <i class="fab fa-instagram" aria-hidden="true"></i><span>Instagram</span>
                    </button>
                    <button type="button" class="blog-share-btn js-blog-native-share" data-platform="TikTok" aria-label="Share on TikTok">
                        <i class="fab fa-tiktok" aria-hidden="true"></i><span>TikTok</span>
                    </button>
                    <button type="button" class="blog-share-btn js-blog-copy-link" aria-label="Copy link">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Copy Link</span>
                    </button>
                </div>

                {{-- Blog Image --}}
                @if($blog->image)
                    <img src="{{ url('public/'.$blog->image) }}"
                         class="img-fluid mb-4"
                         alt="{{ $blog->image_alt ?: $blog->title }}">
                @else
                    <img src="{{ url('public/no-image.png') }}"
                         class="img-fluid mb-4"
                         alt="No Image">
                @endif

                {{-- Blog Description --}}
                <div class="blog-content">
                    {!! $blog->description !!}
                </div>

                {{-- 🔹 Comments --}}
                <section class="blog-comments" id="comments" aria-labelledby="blog-comments-title">
                    <h2 class="blog-comments-title" id="blog-comments-title">
                        {{ $commentCount }} {{ Str::plural('Comment', $commentCount) }}
                    </h2>

                    @if(session('blog_comment_status') === 'pending')
                        <div class="blog-comment-notice" role="status">
                            Thanks! Your comment has been received and will appear after it is approved.
                        </div>
                    @endif

                    @if($comments->isEmpty())
                        <p class="blog-comments-empty">No comments yet. Be the first to share your thoughts.</p>
                    @else
                        <ol class="blog-comment-list">
                            @foreach($comments as $comment)
                                <li>
                                    @include('frontEnd.layouts.pages.blog.partials.comment', ['comment' => $comment])
                                    @if($comment->replies->isNotEmpty())
                                        <ol class="blog-comment-replies ms-5">
                                            @foreach($comment->replies as $reply)
                                                <li>@include('frontEnd.layouts.pages.blog.partials.comment', ['comment' => $reply])</li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <form class="blog-comment-form" method="POST" action="{{ route('blog.comments.store', $blog->slug) }}" novalidate>
                        @csrf
                        <h3>Leave a comment</h3>
                        <p class="form-text mt-0 mb-3">Your email address will not be published. Comments appear after approval.</p>

                        {{-- Spam protection: honeypot + render-time token (see BlogController::storeComment) --}}
                        <div class="blog-comment-hp" aria-hidden="true">
                            <label for="blog_comment_website">Website</label>
                            <input type="text" name="website" id="blog_comment_website" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="form_token" value="{{ encrypt(now()->timestamp) }}">

                        @php $cErrors = $errors->getBag('comment'); @endphp
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="blog_comment_name">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="blog_comment_name" maxlength="100" required
                                       class="form-control @if($cErrors->has('name')) is-invalid @endif"
                                       value="{{ old('name', $customer->name ?? '') }}" autocomplete="name">
                                @if($cErrors->has('name'))<div class="invalid-feedback">{{ $cErrors->first('name') }}</div>@endif
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="blog_comment_email">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="blog_comment_email" maxlength="191" required
                                       class="form-control @if($cErrors->has('email')) is-invalid @endif"
                                       value="{{ old('email', $customer->email ?? '') }}" autocomplete="email">
                                @if($cErrors->has('email'))<div class="invalid-feedback">{{ $cErrors->first('email') }}</div>@endif
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="blog_comment_body">Comment <span class="text-danger">*</span></label>
                                <textarea name="body" id="blog_comment_body" rows="5" required
                                          maxlength="{{ \App\Models\BlogComment::MAX_LENGTH }}"
                                          class="form-control @if($cErrors->has('body')) is-invalid @endif">{{ old('body') }}</textarea>
                                @if($cErrors->has('body'))<div class="invalid-feedback">{{ $cErrors->first('body') }}</div>@endif
                                <div class="form-text text-end"><span id="blog_comment_count">0</span> / {{ \App\Models\BlogComment::MAX_LENGTH }}</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn blog-comment-submit">Post Comment</button>
                            </div>
                        </div>
                    </form>
                </section>

            </div>

            {{-- 🔹 Sidebar --}}
            <div class="col-md-4">

                <div class="card">
                    <div class="card-header">
                        Latest Blogs
                    </div>

                    <ul class="list-group list-group-flush">

                        @foreach($recentBlogs as $rblog)
                        <li class="list-group-item sidebar-blog">

                            <div class="d-flex">

                                {{-- Sidebar Image --}}
                                <div class="me-2">
                                    @if($rblog->image)
                                        <img src="{{ url('public/'.$rblog->image) }}"
                                             alt="{{ $rblog->image_alt ?: $rblog->title }}">
                                    @else
                                        <img src="{{ url('public/no-image.png') }}"
                                             alt="No Image">
                                    @endif
                                </div>

                                {{-- Sidebar Content --}}
                                <div>
                                    <a href="{{ route('blog.details', $rblog->slug) }}"
                                       class="sidebar-blog-title">
                                        {{ Str::limit($rblog->title, 45) }}
                                    </a>

                                    <div class="sidebar-blog-meta mt-1">
                                       {{ $rblog->created_at->format('d M Y') }}
                                       |
                                      👁 {{ $rblog->views }} Views
                                    </div>
                                </div>

                            </div>

                        </li>
                        @endforeach

                    </ul>
                </div>

            </div>

        </div>
    </div>
</section>
@endsection

@push('script')
<script>
(function () {
    var box = document.querySelector('.blog-share');
    if (box) {
        var url = box.getAttribute('data-share-url');
        var title = box.getAttribute('data-share-title');

        function notify(msg) {
            if (window.toastr) { toastr.success(msg); } else { alert(msg); }
        }

        function copyLink(message) {
            var done = function () { notify(message || 'Link copied!'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done, function () { legacyCopy() && done(); });
            } else if (legacyCopy()) {
                done();
            }
        }

        // Clipboard API needs HTTPS; this keeps Copy Link working everywhere else.
        function legacyCopy() {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            if (!ok) window.prompt('Copy this link:', url);
            return ok;
        }

        box.querySelectorAll('.js-blog-copy-link').forEach(function (btn) {
            btn.addEventListener('click', function () { copyLink('Link copied!'); });
        });

        // Instagram / TikTok have no web share URL: use the device share sheet (mobile), else copy.
        box.querySelectorAll('.js-blog-native-share').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: title, text: title, url: url }).catch(function () {});
                } else {
                    copyLink('Link copied! Paste it into ' + btn.getAttribute('data-platform') + '.');
                }
            });
        });
    }

    var body = document.getElementById('blog_comment_body');
    var count = document.getElementById('blog_comment_count');
    if (body && count) {
        var update = function () { count.textContent = body.value.length; };
        body.addEventListener('input', update);
        update();
    }
})();
</script>
@endpush
