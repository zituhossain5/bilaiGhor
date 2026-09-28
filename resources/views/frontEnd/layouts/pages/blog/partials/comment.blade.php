{{-- One approved blog comment or admin reply. Body is escaped (see BlogComment::body_html). --}}
<article class="blog-comment" id="comment-{{ $comment->id }}">
    <div class="blog-comment-avatar {{ $comment->is_admin ? 'is-admin' : '' }}" aria-hidden="true">
        {{ mb_substr($comment->name, 0, 1) }}
    </div>
    <div class="blog-comment-main">
        <div class="blog-comment-head">
            <span class="blog-comment-name">{{ $comment->name }}</span>
            @if($comment->is_admin)
                <span class="blog-comment-badge">Admin</span>
            @endif
            <time class="blog-comment-date" datetime="{{ $comment->created_at->toIso8601String() }}">
                {{ $comment->created_at->format('d M Y') }}
            </time>
        </div>
        <div class="blog-comment-body">{!! $comment->body_html !!}</div>
    </div>
</article>
