@extends('layouts.guest')

@section('content')
    <x-page-header title="Crear cuenta" subtitle="Compra a vendedores locales de Izabal con una cuenta segura." />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4" data-protect-submit>
        @csrf
        <input type="hidden" name="role" value="cliente">

        <x-ui.input name="name" label="Nombre completo" required />
        <x-ui.input name="email" type="email" label="Correo electronico" required />
        <x-ui.input name="phone" label="Telefono" required placeholder="Ej. 55554669" />
        <x-ui.input name="password" type="password" label="Contrasena" required />
        <p class="-mt-3 text-xs text-atlantia-ink/60">
            Usa minimo 12 caracteres, con letras, numeros y simbolos.
        </p>
        <x-ui.input name="password_confirmation" type="password" label="Confirmar contrasena" required />

        <div class="space-y-3 rounded-lg border border-atlantia-rose/20 bg-atlantia-cream p-4 text-sm">
            <label class="flex gap-3">
                <input
                    type="checkbox"
                    name="acepta_terminos"
                    value="1"
                    class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
                    @checked(old('acepta_terminos'))
                    required
                >
                <span>
                    Acepto los terminos y condiciones de Atlantia Supermarket, incluyendo el uso de la plataforma
                    para comprar a Atlantia y a vendedores locales independientes de Izabal.
                </span>
            </label>
            @error('acepta_terminos')
                <p class="text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror

            <label class="flex gap-3">
                <input
                    type="checkbox"
                    name="acepta_privacidad"
                    value="1"
                    class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
                    @checked(old('acepta_privacidad'))
                    required
                >
                <span>
                    Acepto la politica de privacidad y autorizo el uso de mis datos para gestionar mi cuenta,
                    pedidos, entregas, pagos y notificaciones del servicio.
                </span>
            </label>
            @error('acepta_privacidad')
                <p class="text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror

            <label class="flex gap-3">
                <input
                    type="checkbox"
                    name="acepta_marketing"
                    value="1"
                    class="mt-1 rounded border-atlantia-rose text-atlantia-wine"
                    @checked(old('acepta_marketing'))
                >
                <span>Deseo recibir ofertas, recomendaciones y novedades de Atlantia Supermarket.</span>
            </label>
        </div>

        <x-ui.button type="submit" class="w-full">Crear cuenta</x-ui.button>
    </form>
@endsection
