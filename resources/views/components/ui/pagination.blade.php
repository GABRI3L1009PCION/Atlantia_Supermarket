@props([
    'paginator',
])

@if ($paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'mt-6']) }} aria-label="Paginacion">
        {{ $paginator->links() }}
    </nav>
@endif
