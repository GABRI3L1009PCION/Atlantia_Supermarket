@php
    $numeroSoporte = \App\Services\Notificaciones\WhatsAppService::normalizarTelefono(config('atlantia.support.whatsapp'));
    $mensajeSoporte = 'Hola Atlantia Store, necesito ayuda'.(request()->route('pedido')?->numero_pedido ? ' con mi pedido '.request()->route('pedido')->numero_pedido : '').'.';
@endphp

@if ($numeroSoporte && ! request()->routeIs('cliente.checkout.*'))
    <a
        href="https://wa.me/{{ $numeroSoporte }}?text={{ rawurlencode($mensajeSoporte) }}"
        target="_blank"
        rel="noopener"
        class="fixed bottom-24 right-4 z-[60] inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-900/25 transition hover:brightness-95 lg:bottom-6 lg:right-6"
        aria-label="Escríbenos por WhatsApp"
        data-whatsapp-soporte
    >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.9 9.9 0 0 0 12.04 2Zm5.8 14.13c-.24.68-1.42 1.3-1.95 1.35-.5.05-1.13.07-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.29.57-.36.76-.36h.55c.18 0 .41-.07.64.49.24.58.81 2 .88 2.14.07.15.12.31.02.5-.1.19-.14.31-.29.48-.14.17-.3.37-.43.5-.14.14-.29.3-.13.59.17.29.74 1.22 1.59 1.97 1.09.97 2.01 1.27 2.3 1.42.29.14.46.12.62-.07.17-.19.72-.84.91-1.13.19-.29.38-.24.64-.14.27.1 1.68.79 1.97.94.29.14.48.21.55.33.07.12.07.68-.17 1.36Z"/>
        </svg>
        <span class="hidden sm:inline">¿Necesitas ayuda?</span>
    </a>
@endif
