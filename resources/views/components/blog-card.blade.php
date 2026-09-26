{{-- Blog card shared by the homepage blog section and the /blogs page. --}}
@props(['blog'])

<div class="bilai-blog-card">
    <a href="{{ route('blog.details', $blog->slug) }}" class="bilai-blog-img-wrap">
        <img
            src="{{ $blog->image ? url('public/'.$blog->image) : url('public/no-image.png') }}"
            alt="{{ $blog->image_alt ?: $blog->title }}"
            loading="lazy"
        >
        <div class="bilai-blog-img-overlay">
            <div class="bilai-blog-brand">
                <img src="{{ asset(optional($generalsetting)->dark_logo ?? 'public/logo.png') }}" alt="{{ optional($generalsetting)->name ?? 'Bilai Ghor' }}">
                <span>{{ optional($generalsetting)->name ?? 'Bilai Ghor' }}</span>
            </div>
            <div class="bilai-blog-img-actions">
                <span><i class="fas fa-share-alt"></i></span>
                <span><i class="far fa-comment"></i> {{ $blog->views ?? 0 }}</span>
            </div>
        </div>
    </a>
    <div class="bilai-blog-body">
        <div class="bilai-blog-meta">
            <span class="bilai-blog-cat">Blog</span>
            <span class="bilai-blog-date">{{ $blog->created_at->format('jS F, Y') }}</span>
        </div>
        <h5 class="bilai-blog-title">
            <a href="{{ route('blog.details', $blog->slug) }}">{{ Str::limit($blog->title, 55) }}</a>
        </h5>
        <p class="bilai-blog-desc">{{ Str::limit($blog->short_description, 100) }}</p>
        <a href="{{ route('blog.details', $blog->slug) }}" class="bilai-blog-read-btn">Continue Reading</a>
    </div>
</div>
