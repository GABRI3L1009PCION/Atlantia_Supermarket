{{--
    Seccion de un documento legal.
    - callout: frase destacada con icono. En computadora va a la derecha; en celular, debajo del texto.
    - inline: la frase va como franja debajo del texto en todos los tamanos (datos cortos).
--}}
@props(['id', 'n', 'title', 'icon' => 'doc', 'callout' => null, 'inline' => false])

<section id="{{ $id }}" data-legal-section class="scroll-mt-28 rounded-2xl border border-atlantia-rose/15 bg-white p-4 shadow-[0_8px_22px_rgba(59,6,12,0.05)] sm:p-5 lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:py-5 lg:shadow-none">
    <div @class(['lg:grid lg:grid-cols-[minmax(0,1fr)_220px] lg:gap-6' => $callout && ! $inline])>
        <div class="min-w-0">
            <h2 class="text-lg font-black leading-snug text-atlantia-wine sm:text-xl">{{ $n }}. {{ $title }}</h2>
            <div class="mt-1.5 text-sm leading-6 text-atlantia-ink/80 [&_a]:font-bold [&_a]:text-atlantia-wine [&_a]:underline [&_h3]:mt-3 [&_h3]:font-black [&_h3]:text-atlantia-ink [&_li]:pl-0.5 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:space-y-1 [&_ol]:pl-5 [&_p+p]:mt-2 [&_strong]:text-atlantia-ink [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
                {{ $slot }}
            </div>
        </div>

        @if ($callout)
            <div @class([
                'mt-3 flex items-center gap-3 rounded-xl bg-atlantia-blush/55 px-3 py-2.5 text-sm text-atlantia-ink/85',
                'lg:mt-1 lg:self-start lg:border lg:border-atlantia-rose/15 lg:bg-atlantia-blush/35 lg:p-3.5 lg:text-[13px] lg:leading-5' => ! $inline,
            ])>
                <x-legal.icon :name="$icon" @class(['shrink-0 text-atlantia-wine', 'h-5 w-5', 'lg:h-9 lg:w-9' => ! $inline]) />
                <span>{!! $callout !!}</span>
            </div>
        @endif
    </div>
</section>
