{{-- ⓘ with a tooltip (tap or hover). The text is also the button's accessible name. --}}
@props(['text'])
<button type="button" {{ $attributes->class('fc-tip') }} data-bs-toggle="tooltip" title="{{ $text }}" aria-label="{{ $text }}">ⓘ</button>
