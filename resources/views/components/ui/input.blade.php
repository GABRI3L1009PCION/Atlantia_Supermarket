@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
])

@php
    $inputId = $attributes->get('id', $name);
    $errorId = $inputId . '-error';
@endphp

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-atlantia-ink" id="{{ $inputId }}-label">{{ $label }}</span>
    @endif

    <input
        id="{{ $inputId }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        aria-invalid="@error($name) true @else false @enderror"
        @error($name)
            aria-describedby="{{ $errorId }}"
        @enderror
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-atlantia-rose/40 bg-white px-3 py-2 text-sm ' .
                'text-atlantia-ink shadow-sm focus:border-atlantia-wine focus:outline-none ' .
                'focus:ring-2 focus:ring-atlantia-rose',
        ]) }}
    >

    @error($name)
        <span id="{{ $errorId }}" class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
