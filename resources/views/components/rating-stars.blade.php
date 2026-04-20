@props([
    'rating' => 0,
])

<div
    {{ $attributes->merge(['class' => 'flex items-center gap-1 text-amber-500']) }}
    aria-label="{{ $rating }} de 5 estrellas"
>
    @for ($star = 1; $star <= 5; $star++)
        <span aria-hidden="true">{{ $star <= $rating ? '*' : '-' }}</span>
    @endfor
</div>
