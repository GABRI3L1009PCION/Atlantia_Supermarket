@props([
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
])

@php
    $selectId = $attributes->get('id', $name);
    $errorId = $selectId . '-error';
@endphp

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-atlantia-deep">{{ $label }}</span>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        aria-invalid="@error($name) true @else false @enderror"
        @error($name)
            aria-describedby="{{ $errorId }}"
        @enderror
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-atlantia-cyan/30 bg-white/90 px-3 py-2 text-sm text-atlantia-deep ' .
                'shadow-sm focus:border-atlantia-cyan-700 focus:outline-none focus:ring-2 focus:ring-atlantia-cyan',
        ]) }}
    >
        {{ $slot }}

        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(old($name, $selected) == $value)>{{ $text }}</option>
        @endforeach
    </select>

    @error($name)
        <span id="{{ $errorId }}" class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
