{{-- Campos del cupón (crear o editar). $cupon es null al crear. --}}
@php
    $esEste = $cupon === null ? old('_cupon') === null : old('_cupon') === $cupon->uuid;
    $valor = fn (string $campo, $porDefecto = '') => $esEste && old($campo) !== null ? old($campo) : ($cupon?->{$campo} ?? $porDefecto);
    $campo = 'mt-1 h-10 w-full rounded-lg border border-atlantia-rose/30 bg-white px-3 text-sm text-atlantia-ink outline-none focus:border-atlantia-wine focus:ring-2 focus:ring-atlantia-blush';
    $etiqueta = 'text-xs font-bold text-atlantia-ink/70';
    $error = fn (string $nombre) => $esEste && $errors->has($nombre) ? $errors->first($nombre) : null;
@endphp

<div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <label class="block">
        <span class="{{ $etiqueta }}">Código</span>
        <input name="codigo" value="{{ $valor('codigo') }}" required minlength="4" maxlength="30" placeholder="Ej. PUERTO10" class="{{ $campo }} font-mono font-black uppercase tracking-wide">
        @if ($e = $error('codigo'))<span class="mt-1 block text-xs font-bold text-red-600">{{ $e }}</span>@endif
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">Tipo de descuento</span>
        <select name="tipo" class="{{ $campo }}" data-tipo-cupon>
            <option value="porcentaje" @selected($valor('tipo', 'porcentaje') === 'porcentaje')>Porcentaje (%)</option>
            <option value="monto_fijo" @selected($valor('tipo') === 'monto_fijo')>Monto fijo (Q)</option>
        </select>
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">Descuento</span>
        <span class="relative mt-1 block">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-bold text-atlantia-ink/45" data-prefijo-valor>%</span>
            <input name="valor" type="number" step="0.01" min="1" value="{{ $valor('valor') !== '' ? (float) $valor('valor') : '' }}" required placeholder="10" class="{{ $campo }} !mt-0 pl-8">
        </span>
        @if ($e = $error('valor'))<span class="mt-1 block text-xs font-bold text-red-600">{{ $e }}</span>@endif
    </label>
    <label class="block" data-campo-maximo>
        <span class="{{ $etiqueta }}">Descuento máximo <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
        <input name="maximo_descuento" type="number" step="0.01" min="1" value="{{ $valor('maximo_descuento') }}" placeholder="Q sin tope" class="{{ $campo }}">
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">Compra mínima en tus productos <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
        <input name="minimo_compra" type="number" step="0.01" min="0" value="{{ (float) $valor('minimo_compra', 0) > 0 ? (float) $valor('minimo_compra') : '' }}" placeholder="Q0" class="{{ $campo }}">
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">¿Cuántas veces se puede usar? <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
        <input name="usos_maximos" type="number" min="1" value="{{ $valor('usos_maximos') }}" placeholder="Sin límite" class="{{ $campo }}">
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">Empieza <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
        <input name="fecha_inicio" type="date" value="{{ $cupon?->fecha_inicio && ! ($esEste && old('fecha_inicio') !== null) ? $cupon->fecha_inicio->format('Y-m-d') : $valor('fecha_inicio') }}" class="{{ $campo }}">
    </label>
    <label class="block">
        <span class="{{ $etiqueta }}">Termina <span class="font-normal text-atlantia-ink/45">(opcional)</span></span>
        <input name="fecha_fin" type="date" min="{{ now()->toDateString() }}" value="{{ $cupon?->fecha_fin && ! ($esEste && old('fecha_fin') !== null) ? $cupon->fecha_fin->format('Y-m-d') : $valor('fecha_fin') }}" class="{{ $campo }}">
        @if ($e = $error('fecha_fin'))<span class="mt-1 block text-xs font-bold text-red-600">{{ $e }}</span>@endif
    </label>
</div>
<div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
    <label class="inline-flex items-center gap-2 text-atlantia-ink/75">
        <input type="hidden" name="un_uso_por_cliente" value="0">
        <input type="checkbox" name="un_uso_por_cliente" value="1" @checked((bool) $valor('un_uso_por_cliente', false)) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-wine">
        Una vez por cliente
    </label>
    <label class="inline-flex items-center gap-2 text-atlantia-ink/75">
        <input type="hidden" name="solo_primera_compra" value="0">
        <input type="checkbox" name="solo_primera_compra" value="1" @checked((bool) $valor('solo_primera_compra', false)) class="h-4 w-4 rounded border-atlantia-rose/40 text-atlantia-wine focus:ring-atlantia-wine">
        Solo clientes que nunca te han comprado
    </label>
    <label class="block min-w-[14rem] flex-1">
        <span class="sr-only">Nota para ti</span>
        <input name="descripcion" value="{{ $valor('descripcion') }}" maxlength="200" placeholder="Nota para ti (opcional), ej. Campaña de Facebook" class="h-9 w-full rounded-lg border border-atlantia-rose/25 bg-white px-3 text-sm outline-none focus:border-atlantia-wine">
    </label>
</div>
