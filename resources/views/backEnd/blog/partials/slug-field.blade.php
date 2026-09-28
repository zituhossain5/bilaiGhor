{{--
    Blog "Slug (URL)" field with a live URL preview.
    - Create: auto-suggested from the title until the slug is typed into by hand.
    - Edit:   keeps the current slug; the URL only changes if this field is edited, and the
              previous URL keeps 301-redirecting.
    The preview asks the server, so it shows exactly what saving produces (incl. -2 on collisions
    and transliteration of Bangla titles).
    Expects: $blog (optional, edit mode)
--}}
@php
    $editing = isset($blog) && $blog;
    $currentSlug = $editing ? $blog->slug : '';
@endphp
<div class="form-group mb-4">
    <label class="form-label" for="blog_slug">Slug (URL)</label>
    <input type="text" name="slug" id="blog_slug" maxlength="191"
           class="form-control @error('slug') is-invalid @enderror"
           value="{{ old('slug', $currentSlug) }}"
           placeholder="e.g. cat-panleukopenia-vaccination"
           autocomplete="off" spellcheck="false">
    @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="text-muted d-block mt-2">
        Short English words work best. Lowercase letters, numbers and hyphens only (max {{ \App\Support\BlogSlug::MAX_LENGTH }} characters).
        @unless($editing) Leave empty to generate it from the title. @endunless
    </small>
    <div class="mt-2 font-size-13" id="blog_slug_preview" aria-live="polite">
        <span class="text-muted">URL:</span>
        <code id="blog_slug_url">{{ $currentSlug ? route('blog.details', $currentSlug) : '—' }}</code>
    </div>
    <div class="font-size-12 mt-1 text-warning" id="blog_slug_note" style="display:none;"></div>
</div>

<script>
(function () {
    var input = document.getElementById('blog_slug');
    var title = document.querySelector('input[name="title"]');
    var urlEl = document.getElementById('blog_slug_url');
    var noteEl = document.getElementById('blog_slug_note');
    if (!input || !title) return;

    var previewUrl = @json(route('admin.blog.slug_preview'));
    var blogId = @json($editing ? $blog->id : null);
    var original = @json($currentSlug);
    // On create the slug follows the title until the admin types in it; on edit it never does.
    var manual = blogId !== null || input.value.trim() !== '';
    var timer = null, seq = 0;

    function note(text) {
        noteEl.textContent = text || '';
        noteEl.style.display = text ? 'block' : 'none';
    }

    function refresh() {
        var mine = ++seq;
        // Editing with an empty field keeps the current URL (the server ignores a blank slug).
        if (blogId && input.value.trim() === '') {
            urlEl.textContent = @json($currentSlug ? route('blog.details', $currentSlug) : '—');
            note('Empty — the current URL will be kept.');
            return;
        }
        var params = new URLSearchParams({ slug: input.value.trim(), title: title.value.trim() });
        if (blogId) params.set('id', blogId);
        fetch(previewUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || mine !== seq) return;
                if (!manual) input.value = d.slug;
                urlEl.textContent = d.url || '—';
                var notes = [];
                if (manual && d.slug && d.slug !== input.value.trim()) notes.push('Will be saved as “' + d.slug + '”.');
                if (d.collision) notes.push('That slug is already used by another post, so a number was added.');
                if (blogId && original && d.slug && d.slug !== original) notes.push('The old URL (…/' + original + ') will keep redirecting here.');
                note(notes.join(' '));
            })
            .catch(function () {});
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);
    }

    input.addEventListener('input', function () {
        manual = input.value.trim() !== '' || blogId !== null;
        schedule();
    });
    title.addEventListener('input', function () {
        if (!manual) schedule();
    });
    if (!blogId) schedule();
})();
</script>
