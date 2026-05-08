@props([
    'text' => '',
    'maxLength' => 150,
    'class' => 'text-gray-600 mt-1',
    'buttonClass' => 'text-blue-600 underline text-sm font-medium ml-1'
])

@php
    $isTruncated = strlen($text) > $maxLength;
    $displayText = $isTruncated ? substr($text, 0, $maxLength) : $text;
    // Encode data for safe JSON storage
    $fullTextJson = json_encode($text);
    $truncatedTextJson = json_encode($displayText);
@endphp

<div class="truncated-text-wrapper {{ $attributes->get('wrapper-class') }}">
    <p class="{{ $class }}">
        <span class="text-content" data-full-text="{!! $fullTextJson !!}" data-truncated-text="{!! $truncatedTextJson !!}">{{ $displayText }}{{ $isTruncated ? '...' : '' }}</span>
        @if ($isTruncated)
            <button
                type="button"
                class="toggle-more-info {{ $buttonClass }}"
                aria-expanded="false"
                data-truncated="true"
            >
                Meer info
            </button>
        @endif
    </p>
</div>


