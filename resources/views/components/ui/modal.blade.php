@props([
    'id',
    'title',
])

<div
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'hidden']) }}
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="fixed inset-0 z-40 bg-slate-950/50"></div>

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <section class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
            <header class="mb-4">
                <h2 id="{{ $id }}-title" class="text-lg font-semibold text-slate-950">{{ $title }}</h2>
            </header>

            {{ $slot }}
        </section>
    </div>
</div>
