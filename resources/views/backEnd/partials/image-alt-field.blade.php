{{--
    "Image Alt Text" input shown under an image upload.

    All options go in ONE array, `altField`, so nothing leaks in from the including page
    (@include shares the parent's variables, e.g. a loop's $value or $label):

    @include('backEnd.partials.image-alt-field', ['altField' => [
        'name'         => 'image_alt',        // input name (required)
        'value'        => $model->image_alt,  // current value; old() is applied here
        'required'     => false,              // required when the image itself is required
        'label'        => 'Image Alt Text',
        'id'           => null,
        'class'        => '',                 // extra input classes to match the form (e.g. input-clean)
        'labelClass'   => 'form-label',       // to match the form's labels
        'wrapperClass' => 'mt-3',
        'errorKey'     => null,               // dotted key for array inputs (e.g. image_alt.0)
    ]])
--}}
@php
    $altOpts     = (array) ($altField ?? []);
    $altName     = $altOpts['name'] ?? 'image_alt';
    $altErrorKey = $altOpts['errorKey'] ?? trim(str_replace(['[', ']'], ['.', ''], $altName), '.');
    $altRequired = !empty($altOpts['required']);
    $altId       = $altOpts['id'] ?? null;
    $altValue    = $altOpts['value'] ?? '';
@endphp
<div class="{{ $altOpts['wrapperClass'] ?? 'mt-3' }} image-alt-field">
    <label class="{{ $altOpts['labelClass'] ?? 'form-label' }}" @if($altId) for="{{ $altId }}" @endif>
        {{ $altOpts['label'] ?? 'Image Alt Text' }} @if($altRequired)<span class="text-danger">*</span>@endif
    </label>
    <input type="text"
           name="{{ $altName }}"
           @if($altId) id="{{ $altId }}" @endif
           value="{{ old($altErrorKey, $altValue) }}"
           maxlength="255"
           class="form-control {{ $altOpts['class'] ?? '' }} @error($altErrorKey) is-invalid @enderror"
           placeholder="Describe this image for SEO (e.g. 'Orange tabby kitten eating from a bowl')"
           @if($altRequired) required @endif>
    @error($altErrorKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted d-block mt-1">Improves SEO and accessibility.</small>
</div>
