@props(['name', 'label', 'help' => null])

<div data-field>
    <label class="block text-sm font-black text-atlantia-ink">{{ $label }}</label>
    <div class="mt-2">
        {{ $slot }}
    </div>
    @if ($help)
        <p class="mt-1 text-xs font-semibold text-atlantia-ink/55">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>
    @enderror
    <p class="mt-1 text-xs font-bold text-red-700" data-field-error hidden></p>
</div>
