{{-- Icono de linea para una categoria segun su slug. Recibe $clave (slug en minusculas) y $clase. --}}
@php($k = $clave)
@if (str_contains($k, 'bebida'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6M10 3v4l-2 3v11h8V10l-2-3V3M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'abarrote') || str_contains($k, 'despensa') || str_contains($k, 'grano') || str_contains($k, 'enlatado') || str_contains($k, 'bebe'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7M10 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM19 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'fruta') || str_contains($k, 'verdura'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8c-4-3-8 0-8 5 0 4 3 8 6 8 1 0 1.4-.7 2-.7s1 .7 2 .7c3 0 6-4 6-8 0-5-4-8-8-5Zm0 0c0-3 2-5 5-5M12 7c-2-2-4-2-6-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'marisco') || str_contains($k, 'pescado'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12c3-5 8-7 13-4l3-3v6l-3-3c-5 7-10 5-13 4Zm0 0-2-3v6l2-3Zm9-2h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'carne') || str_contains($k, 'ave'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15.5 4C19 4 21 6.5 21 9.5c0 4.5-5 9.5-10.5 9.5C6.4 19 3 17 3 13.5 3 10 6 9 8.5 8 11 7 12 4 15.5 4Z M7 13.5c1.5 1.5 4.5 1 7-1.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'lacteo') || str_contains($k, 'huevo'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 3h5v4l2 3v10H6V10l2-3V3Zm0 5h5M18 8c-2 3-3 5-3 7a3 3 0 0 0 6 0c0-2-1-4-3-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'congelado') || str_contains($k, 'refrigerado'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v20M4.2 6.5l15.6 11M4.2 17.5l15.6-11M9 4l3 3 3-3M9 20l3-3 3 3M4 10l4 2-4 2M20 10l-4 2 4 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'desayuno') || str_contains($k, 'cafe'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Zm13 2h2a3 3 0 0 1 0 6h-3M7 3v2m4-2v2m4-2v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'gourmet') || str_contains($k, 'aceite') || str_contains($k, 'condimento'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 13a6 6 0 0 1 12 0H6Zm-2 0h16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2Zm8-6V5m-2 0h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'panaderia') || str_contains($k, 'pasteleria'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 20c-2 0-3-1.5-3-3.5S3.5 13 5.5 13c0-2.3 1.7-4 4-4 .8-3 5-4 7-1.5 3 0 5.5 2.2 5.5 5.3 0 1.8-.8 3.2-2 4.2v3H5Zm4-7v3m4-4v4m4-5v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'papel') || str_contains($k, 'desechable'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h9a3 3 0 0 1 3 3v15H9a3 3 0 0 1-3-3V3Zm3 15a3 3 0 0 0 3 3M9 7h6M9 11h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'snack') || str_contains($k, 'dulce'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 8-4-3v5l3 2-3 2v5l4-3m10-8 4-3v5l-3 2 3 2v5l-4-3M7 7h10v10H7V7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'evento') || str_contains($k, 'fiesta') || str_contains($k, 'temporada') || str_contains($k, 'promocion'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10h16v11H4V10Zm-1-5h18v5H3V5Zm9 0v16M7 5c-2-2-1-4 1-4 2.5 0 4 4 4 4m5 0c2-2 1-4-1-4-2.5 0-4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'limpieza'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3ZM5 13l.8 2.2L8 16l-2.2.8L5 19l-.8-2.2L2 16l2.2-.8L5 13Zm13 1 1 2.9 3 1.1-3 1-1 3-1-3-3-1 3-1.1 1-2.9Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'higiene') || str_contains($k, 'cuidado'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6v4l2 2v12H7V9l2-2V3Zm0 4h6M10 12h4M10 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'farmacia') || str_contains($k, 'salud') || str_contains($k, 'auxilio'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'mascota'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 11c-2 0-3-2-3-4 0-1.7 1-3 2.3-3C9 4 10 6 10 7.5 10 9.5 9 11 8 11Zm8 0c2 0 3-2 3-4 0-1.7-1-3-2.3-3C15 4 14 6 14 7.5c0 2 1 3.5 2 3.5Zm-4 0c-3 0-6 3-6 6 0 2 1.5 3 3 3 1.2 0 2-.7 3-.7s1.8.7 3 .7c1.5 0 3-1 3-3 0-3-3-6-6-6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
@elseif (str_contains($k, 'local'))
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 1 0 5 9c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
@else
    <svg class="{{ $clase }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h7v7H4V4ZM13 4h7v7h-7V4ZM4 13h7v7H4v-7ZM13 13h7v7h-7v-7Z" stroke="currentColor" stroke-width="1.7"/></svg>
@endif
