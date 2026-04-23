@props([
    'label' => null,
    'name',
    'value' => null,
    'rows' => 4,
])

@php
    $textareaId = $attributes->get('id', $name);
    $errorId = $textareaId . '-error';
@endphp

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</span>
    @endif

    <textarea
        id="{{ $textareaId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        aria-invalid="@error($name) true @else false @enderror"
        @error($name)
            aria-describedby="{{ $errorId }}"
        @enderror
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 ' .
                'shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600',
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <span id="{{ $errorId }}" class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
