@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
])

<label class="block">
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</span>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 ' .
                'shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600',
        ]) }}
    >

    @error($name)
        <span class="mt-1 block text-sm text-red-700">{{ $message }}</span>
    @enderror
</label>
