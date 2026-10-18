{{-- Brand page SEO (same fields as categories). Empty fields fall back to generated text on /brand/{slug}. --}}
@php $b = $brand ?? null; @endphp
<div class="form-group mt-3">
    <label for="seo_title" class="form-label">SEO Title</label>
    <input type="text" class="form-control @error('seo_title') is-invalid @enderror" name="seo_title" id="seo_title" maxlength="255"
           value="{{ old('seo_title', $b->seo_title ?? '') }}" placeholder="e.g. Whiskas Cat Food Price in Bangladesh | Bilai Ghor">
    <small class="text-muted">খালি রাখলে: "{Brand} Price in Bangladesh | Bilai Ghor"</small>
    @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group mt-3">
    <label for="seo_h1" class="form-label">SEO H1</label>
    <input type="text" class="form-control @error('seo_h1') is-invalid @enderror" name="seo_h1" id="seo_h1" maxlength="255"
           value="{{ old('seo_h1', $b->seo_h1 ?? '') }}" placeholder="e.g. Whiskas Price in Bangladesh">
    @error('seo_h1')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group mt-3">
    <label for="meta_description" class="form-label">Meta Description</label>
    <textarea class="form-control @error('meta_description') is-invalid @enderror" name="meta_description" id="meta_description" rows="3"
              maxlength="500" placeholder="Short search-result description.">{{ old('meta_description', \App\Support\SeoText::plain($b->meta_description ?? null)) }}</textarea>
    @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group mt-3 mb-3">
    <label for="seo_content" class="form-label">Full Description / SEO Content</label>
    <textarea class="summernote form-control @error('seo_content') is-invalid @enderror" name="seo_content" id="seo_content">{!! old('seo_content', $b->seo_content ?? '') !!}</textarea>
    <small class="text-muted">Long useful brand content shown in the accordion below the product grid.</small>
    @error('seo_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
