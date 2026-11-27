@extends('layouts.app')

@section('content')
    @php
        $vendor = auth()->user()?->vendor;
        $field = fn (string $name, mixed $fallback = '') => old($name, $perfil?->{$name} ?? $fallback);
        $campo = 'mt-1 h-10 w-full rounded-lg border bg-white px-3 text-sm text-atlantia-ink outline-none placeholder:text-atlantia-ink/35 focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
        $borde = fn (string $name): string => $errors->has($name) ? 'border-rose-500 bg-rose-50' : 'border-atlantia-rose/30';
        $error = 'mt-1 text-xs font-semibold text-rose-700';
        $ayuda = 'mt-1 block text-[11px] text-atlantia-ink/50';

        $completo = [
            'banco' => filled($perfil?->banco_nombre) && filled($perfil?->cuenta_bancaria),
            'fiscal' => filled($perfil?->nit) && filled($perfil?->razon_social) && filled($perfil?->direccion_fiscal) && filled($perfil?->codigo_establecimiento) && ! str_starts_with((string) $perfil?->codigo_establecimiento, 'PEND-'),
            'fel' => (bool) ($perfil?->fel_activo ?? false) || ($perfil?->modoFacturacion() ?? 'manual_sat') === 'manual_sat',
        ];
        $pestanas = [
            'banco' => ['Cuenta para tus pagos', 'Donde te depositamos lo que vendes'],
            'fiscal' => ['Datos para facturar', 'Tu NIT y datos de SAT'],
            'fel' => ['Factura electrónica', 'Cómo facturas tus ventas'],
        ];
        $activa = old('seccion', request('seccion'));
        if (! array_key_exists((string) $activa, $pestanas)) {
            $activa = ! $completo['banco'] ? 'banco' : (! $completo['fiscal'] ? 'fiscal' : 'fel');
        }
        $bancos = ['Banrural', 'Banco Industrial', 'BAM', 'BAC Credomatic', 'G&T Continental', 'Banco Promerica', 'Banco Azteca', 'Bantrab', 'Banco Inmobiliario', 'Interbanco', 'Vivibanco', 'Otro'];
        $bancoActual = (string) $field('banco_nombre');
        $cuentaGuardada = (string) ($perfil?->cuenta_bancaria ?? '');
    @endphp

    <section class="mx-auto max-w-[1000px] space-y-4 pb-10">
        <div>
            <h1 class="text-2xl font-black text-atlantia-ink">Datos de facturación y pagos</h1>
            <p class="mt-0.5 text-sm text-atlantia-ink/60">Cada parte se guarda por separado. Para cobrar tus ventas solo necesitas la cuenta bancaria.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-atlantia-rose/15 bg-white shadow-[0_12px_32px_rgba(42,16,24,0.05)]">
            <nav class="grid border-b border-atlantia-rose/10 sm:grid-cols-3" aria-label="Secciones">
                @foreach ($pestanas as $clave => [$titulo, $sub])
                    <a href="{{ route('vendedor.perfil-fiscal.edit', ['seccion' => $clave]) }}" @class([
                        'flex items-center gap-3 border-b-2 px-4 py-3 transition sm:border-b-2',
                        'border-atlantia-wine bg-atlantia-blush/30' => $activa === $clave,
                        'border-transparent hover:bg-[#fdfafa]' => $activa !== $clave,
                    ]) @if ($activa === $clave) aria-current="page" @endif data-seccion="{{ $clave }}">
                        <span @class([
                            'grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-black',
                            'bg-emerald-100 text-emerald-700' => $completo[$clave],
                            'bg-amber-100 text-amber-800' => ! $completo[$clave],
                        ])>
                            @if ($completo[$clave])
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            @else
                                !
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span @class(['block text-sm font-black', 'text-atlantia-wine' => $activa === $clave, 'text-atlantia-ink' => $activa !== $clave])>{{ $titulo }}</span>
                            <span class="block truncate text-xs text-atlantia-ink/50">{{ $completo[$clave] ? ($clave === 'fel' ? 'Activa' : 'Completo') : $sub }}</span>
                        </span>
                    </a>
                @endforeach
            </nav>

            @if ($activa === 'banco')
                <form method="POST" action="{{ route('vendedor.perfil-fiscal.update') }}" class="p-5" data-seccion-form="banco">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="seccion" value="banco">
                    <p class="text-sm text-atlantia-ink/65">Aquí te depositamos lo que vendes cuando solicitas tu pago en <a href="{{ route('vendedor.pagos.index') }}" class="font-bold text-atlantia-wine hover:underline">Ventas y pagos</a>.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Banco</span>
                            <select name="banco_nombre" class="{{ $campo }} {{ $borde('banco_nombre') }}" required>
                                <option value="">Elige tu banco</option>
                                @foreach ($bancos as $banco)
                                    <option value="{{ $banco }}" @selected($bancoActual === $banco)>{{ $banco }}</option>
                                @endforeach
                                @if ($bancoActual !== '' && ! in_array($bancoActual, $bancos, true))
                                    <option value="{{ $bancoActual }}" selected>{{ $bancoActual }}</option>
                                @endif
                            </select>
                            @error('banco_nombre') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <fieldset>
                            <legend class="text-xs font-bold text-atlantia-ink">Tipo de cuenta</legend>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                @foreach (['monetaria' => 'Monetaria', 'ahorro' => 'De ahorro'] as $valor => $texto)
                                    <label class="flex h-10 cursor-pointer items-center justify-center rounded-lg border border-atlantia-rose/30 text-sm font-bold text-atlantia-ink has-[:checked]:border-atlantia-wine has-[:checked]:bg-atlantia-wine has-[:checked]:text-white">
                                        <input type="radio" name="cuenta_bancaria_tipo" value="{{ $valor }}" class="sr-only" @checked($field('cuenta_bancaria_tipo') === $valor) required>
                                        {{ $texto }}
                                    </label>
                                @endforeach
                            </div>
                            @error('cuenta_bancaria_tipo') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </fieldset>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Número de cuenta</span>
                            <input name="cuenta_bancaria" value="{{ old('cuenta_bancaria', $cuentaGuardada) }}" inputmode="numeric" autocomplete="off" placeholder="Ej. 3045123456" class="{{ $campo }} {{ $borde('cuenta_bancaria') }} font-mono" required>
                            <span class="{{ $ayuda }}">Revísalo bien: aquí llega tu dinero.</span>
                            @error('cuenta_bancaria') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">A nombre de</span>
                            <input name="cuenta_bancaria_titular" value="{{ $field('cuenta_bancaria_titular', auth()->user()?->name) }}" class="{{ $campo }} {{ $borde('cuenta_bancaria_titular') }}" required>
                            <span class="{{ $ayuda }}">Como aparece en tu cuenta del banco.</span>
                            @error('cuenta_bancaria_titular') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar cuenta</button>
                    </div>
                </form>
            @elseif ($activa === 'fiscal')
                <form method="POST" action="{{ route('vendedor.perfil-fiscal.update') }}" class="p-5" data-seccion-form="fiscal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="seccion" value="fiscal">
                    <p class="text-sm text-atlantia-ink/65">Solo si tienes NIT y quieres que tus ventas se facturen a tu nombre. Copia los datos tal como aparecen en tu RTU de SAT.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">NIT</span>
                            <input name="nit" value="{{ $field('nit') }}" placeholder="Ej. 1234567-8" class="{{ $campo }} {{ $borde('nit') }} font-mono uppercase" required>
                            @error('nit') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-xs font-bold text-atlantia-ink">Nombre o razón social</span>
                            <input name="razon_social" value="{{ $field('razon_social', auth()->user()?->name) }}" class="{{ $campo }} {{ $borde('razon_social') }}" required>
                            @error('razon_social') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-xs font-bold text-atlantia-ink">Dirección fiscal</span>
                            <input name="direccion_fiscal" value="{{ $field('direccion_fiscal') }}" placeholder="Como aparece en tu RTU" class="{{ $campo }} {{ $borde('direccion_fiscal') }}" required>
                            @error('direccion_fiscal') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Nombre comercial <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
                            <input name="nombre_comercial_sat" value="{{ $field('nombre_comercial_sat', $vendor?->business_name) }}" class="{{ $campo }} {{ $borde('nombre_comercial_sat') }}">
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Régimen</span>
                            <select name="regimen_sat" class="{{ $campo }} {{ $borde('regimen_sat') }}" required>
                                @foreach (['pequeno_contribuyente' => 'Pequeño contribuyente', 'general' => 'General (IVA)', 'exento' => 'Exento'] as $valor => $texto)
                                    <option value="{{ $valor }}" @selected($field('regimen_sat', 'pequeno_contribuyente') === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                            @error('regimen_sat') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">N.º de establecimiento</span>
                            <input name="codigo_establecimiento" value="{{ str_starts_with((string) $field('codigo_establecimiento'), 'PEND-') ? '' : $field('codigo_establecimiento') }}" placeholder="Ej. 1" class="{{ $campo }} {{ $borde('codigo_establecimiento') }}" required>
                            <span class="{{ $ayuda }}">Aparece en tu RTU junto a cada negocio.</span>
                            @error('codigo_establecimiento') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Afiliación de IVA</span>
                            <input name="afiliacion_iva" value="{{ $field('afiliacion_iva', 'GEN') }}" placeholder="GEN o PEQ" class="{{ $campo }} {{ $borde('afiliacion_iva') }} uppercase" required>
                            <span class="{{ $ayuda }}">GEN si es régimen general, PEQ si eres pequeño contribuyente.</span>
                            @error('afiliacion_iva') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar datos para facturar</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('vendedor.perfil-fiscal.update') }}" class="border-b border-atlantia-rose/10 p-5" data-modo-facturacion>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="seccion" value="modo">
                    @php $modo = old('modo_facturacion', $perfil?->modoFacturacion() ?? 'manual_sat'); @endphp
                    <h2 class="text-base font-black text-atlantia-ink">¿Cómo facturas tus ventas?</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label @class(['flex cursor-pointer gap-3 rounded-lg border p-4', 'border-atlantia-wine bg-atlantia-blush/40' => $modo === 'manual_sat', 'border-atlantia-rose/20' => $modo !== 'manual_sat'])>
                            <input type="radio" name="modo_facturacion" value="manual_sat" @checked($modo === 'manual_sat') class="mt-1 h-4 w-4 text-atlantia-wine">
                            <span>
                                <span class="block text-sm font-black text-atlantia-ink">Facturo yo en la SAT</span>
                                <span class="mt-0.5 block text-xs text-atlantia-ink/65">Emites cada factura en la Agencia Virtual de la SAT (FEL gratuito) o con tu sistema. En el pedido copias los datos del cliente y registras el número de autorización y el PDF; tu cliente la ve en su pedido.</span>
                            </span>
                        </label>
                        <label @class(['flex cursor-pointer gap-3 rounded-lg border p-4', 'border-atlantia-wine bg-atlantia-blush/40' => $modo === 'certificador', 'border-atlantia-rose/20' => $modo !== 'certificador'])>
                            <input type="radio" name="modo_facturacion" value="certificador" @checked($modo === 'certificador') class="mt-1 h-4 w-4 text-atlantia-wine">
                            <span>
                                <span class="block text-sm font-black text-atlantia-ink">Con mi certificador (automático)</span>
                                <span class="mt-0.5 block text-xs text-atlantia-ink/65">Atlantia emite la factura sola al vender, con las credenciales de tu certificador FEL. Atlantia revisa y activa tus credenciales.</span>
                            </span>
                        </label>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar</button>
                    </div>
                </form>
                @if (($perfil?->modoFacturacion() ?? 'manual_sat') === 'manual_sat')
                    <div class="p-5 text-sm text-atlantia-ink/70" data-facturacion-manual-info>
                        <p><strong class="text-atlantia-ink">Así funciona:</strong> en cada pedido, en la sección <em>Factura</em>, tienes los datos del cliente listos para copiar en la SAT. Cuando la emitas, escribe la serie, el número y la autorización, y sube el PDF. Todas tus facturas quedan en <a href="{{ route('vendedor.dte.index') }}" class="font-bold text-atlantia-wine hover:underline">Facturas</a>, con un resumen por mes para tu declaración.</p>
                    </div>
                @else
                <form method="POST" action="{{ route('vendedor.perfil-fiscal.update') }}" class="p-5" data-seccion-form="fel">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="seccion" value="fel">
                    <input type="hidden" name="certificador_fel" value="infile">

                    <div @class([
                        'flex items-start gap-3 rounded-lg px-4 py-3 text-sm',
                        'bg-emerald-50 text-emerald-900' => $completo['fel'],
                        'bg-amber-50 text-amber-900' => ! $completo['fel'],
                    ])>
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $completo['fel'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <p>
                            @if ($completo['fel'])
                                <strong>Factura electrónica activa</strong>{{ $perfil?->fel_validado_at ? ' desde el '.$perfil->fel_validado_at->format('d/m/Y') : '' }}. Tus ventas se facturan automáticamente a tu nombre.
                            @else
                                <strong>Aún no está activa.</strong> Llena tus datos para facturar y las credenciales de tu certificador. Atlantia las revisa y la activa; te avisaremos.
                            @endif
                        </p>
                    </div>

                    @unless ($completo['fiscal'])
                        <p class="mt-3 text-sm text-atlantia-ink/65">Primero completa <a href="{{ route('vendedor.perfil-fiscal.edit', ['seccion' => 'fiscal']) }}" class="font-bold text-atlantia-wine hover:underline">Datos para facturar</a>.</p>
                    @endunless

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-bold text-atlantia-ink">Certificador</span>
                            <input value="INFILE" disabled class="{{ $campo }} border-atlantia-rose/20 bg-[#f8f5f4] text-atlantia-ink/60">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-xs font-bold text-atlantia-ink">Usuario de INFILE</span>
                            <input name="fel_usuario" value="{{ old('fel_usuario', $perfil?->fel_usuario) }}" autocomplete="off" class="{{ $campo }} {{ $borde('fel_usuario') }}">
                            @error('fel_usuario') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block sm:col-span-3 lg:col-span-1">
                            <span class="text-xs font-bold text-atlantia-ink">Llave de firma</span>
                            <input name="fel_llave_firma" type="password" autocomplete="new-password" placeholder="{{ filled($perfil?->fel_llave_firma) ? 'Guardada · escribe otra para cambiarla' : 'Pégala aquí' }}" class="{{ $campo }} {{ $borde('fel_llave_firma') }}">
                            @error('fel_llave_firma') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                        <label class="block sm:col-span-3 lg:col-span-2">
                            <span class="text-xs font-bold text-atlantia-ink">Llave del certificador</span>
                            <input name="fel_llave_certificador" type="password" autocomplete="new-password" placeholder="{{ filled($perfil?->fel_llave_certificador) ? 'Guardada · escribe otra para cambiarla' : 'Pégala aquí' }}" class="{{ $campo }} {{ $borde('fel_llave_certificador') }}">
                            @error('fel_llave_certificador') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-atlantia-ink/50">Las llaves se guardan cifradas y nunca se muestran de nuevo. INFILE te las entrega al contratar la factura electrónica.</p>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="h-10 rounded-lg bg-atlantia-wine px-5 text-sm font-bold text-white hover:bg-atlantia-wine-700">Guardar credenciales</button>
                    </div>
                </form>
                @endif
            @endif
        </div>
    </section>
@endsection
