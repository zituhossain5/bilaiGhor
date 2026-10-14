{{-- Blog card shared by the homepage blog section and the /blogs page. --}}
{{-- headingLevel: 3 under a section h2 (homepage), 2 directly under the page h1 (/blogs) --}}
@props(['blog', 'headingLevel' => 3])

<div class="bilai-blog-card">
    <a href="{{ route('blog.details', $blog->slug) }}" class="bilai-blog-img-wrap">
        <x-picture
            :src="$blog->image ? 'public/'.$blog->image : 'public/no-image.png'"
            alt="{{ $blog->image_alt ?: $blog->title }}"
            width="420" height="220" decoding="async"
            loading="lazy"
        />
        <div class="bilai-blog-img-overlay">
            <div class="bilai-blog-brand">
                <img src="{{ asset(optional($generalsetting)->dark_logo ?? 'public/logo.png') }}" alt="" aria-hidden="true" width="22" height="22" loading="lazy">
                <span>{{ optional($generalsetting)->name ?? 'Bilai Ghor' }}</span>
            </div>
            <div class="bilai-blog-img-actions">
                <span><i class="fas fa-share-alt"></i></span>
                <span title="Views"><i class="far fa-eye"></i> {{ $blog->views ?? 0 }}</span>
                <span title="Comments"><i class="far fa-comment"></i> {{ $blog->approved_comments_count ?? $blog->approvedComments()->count() }}</span>
            </div>
        </div>
    </a>
    <div class="bilai-blog-body">
        <div class="bilai-blog-meta">
            <span class="bilai-blog-cat">Blog</span>
            <span class="bilai-blog-date">{{ $blog->created_at->format('jS F, Y') }}</span>
        </div>
        <h{{ $headingLevel }} class="bilai-blog-title">
            <a href="{{ route('blog.details', $blog->slug) }}">{{ Str::limit($blog->title, 55) }}</a>
        </h{{ $headingLevel }}>
        <p class="bilai-blog-desc">{{ Str::limit($blog->short_description, 100) }}</p>
        <a href="{{ route('blog.details', $blog->slug) }}" class="bilai-blog-read-btn">Continue Reading</a>
    </div>
</div>
