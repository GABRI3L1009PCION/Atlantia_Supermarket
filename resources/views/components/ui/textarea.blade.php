@props([
    'label' => null,
    'name',
    'value' => null,
    'rows' => 4,
])

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</span>
    @endif

    <textarea
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 ' .
                'shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600',
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
