@props([
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
])

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</span>
    @endif

    <select
        name="{{ $name }}"
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
        <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
