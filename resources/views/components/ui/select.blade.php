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
        <span class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</span>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        aria-invalid="@error($name) true @else false @enderror"
        @error($name)
            aria-describedby="{{ $errorId }}"
        @enderror
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 ' .
                'shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600',
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
