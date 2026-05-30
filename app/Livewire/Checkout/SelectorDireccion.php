<?php

namespace App\Livewire\Checkout;

use App\DTOs\DireccionDTO;
use App\Models\Cliente\Direccion;
use App\Services\Clientes\DireccionService;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Selector de direccion de entrega para checkout.
 */
class SelectorDireccion extends Component
{
    public ?int $direccionId = null;

    public bool $mostrandoFormulario = false;

    #[Validate('required|string|max:80')]
    public string $formAlias = 'Casa';

    #[Validate('required|string|min:3|max:160', message: 'El nombre de quien recibe es obligatorio.')]
    public string $formNombreContacto = '';

    #[Validate('required|regex:/^(\+502)?[2-7][0-9]{7}$/', message: 'Ingresa un telefono de Guatemala valido (ej. 55551234).')]
    public string $formTelefono = '';

    #[Validate('required|in:Puerto Barrios,Santo Tomas,Morales,Los Amates,Livingston,El Estor')]
    public string $formMunicipio = 'Puerto Barrios';

    #[Validate('required|string|min:8|max:500', message: 'Escribe la direccion completa (minimo 8 caracteres).')]
    public string $formDireccion = '';

    #[Validate('nullable|string|max:160')]
    public string $formZonaBarrio = '';

    #[Validate('nullable|string|max:600')]
    public string $formReferencia = '';

    #[Validate('required|numeric|between:-90,90', message: 'Presiona "Usar mi ubicacion actual" para capturar las coordenadas GPS.')]
    public ?float $formLatitude = null;

    #[Validate('required|numeric|between:-180,180')]
    public ?float $formLongitude = null;

    public function mount(): void
    {
        $coverageService = app(DeliveryCoverageService::class);
        $direccion = $this->direcciones()
            ->first(fn (Direccion $d): bool => $coverageService->coverageStateFor($d)['covered']);

        $this->direccionId = $direccion?->id;

        if ($this->direccionId) {
            $this->dispatch('checkout.direccion-actualizada', direccionId: $this->direccionId);
        }
    }

    public function seleccionarDireccion(int $direccionId): void
    {
        $direccion = $this->direccionDelCliente($direccionId);

        if (! app(DeliveryCoverageService::class)->coverageStateFor($direccion)['covered']) {
            return;
        }

        $this->direccionId = $direccion->id;
        $this->dispatch('checkout.direccion-actualizada', direccionId: $direccion->id);
    }

    public function marcarPrincipal(int $direccionId): void
    {
        $direccion = $this->direccionDelCliente($direccionId);

        DB::transaction(function () use ($direccion): void {
            Direccion::query()
                ->where('user_id', auth()->id())
                ->where('es_principal', true)
                ->update(['es_principal' => false]);

            $direccion->update(['es_principal' => true]);
        });

        $this->seleccionarDireccion($direccion->id);
    }

    public function abrirFormulario(): void
    {
        $this->mostrandoFormulario = true;
        $this->formNombreContacto = auth()->user()?->name ?? '';
        $this->formAlias = 'Casa';
        $this->formTelefono = '';
        $this->formMunicipio = 'Puerto Barrios';
        $this->formDireccion = '';
        $this->formZonaBarrio = '';
        $this->formReferencia = '';
        $this->formLatitude = null;
        $this->formLongitude = null;
        $this->resetValidation();
    }

    public function cerrarFormulario(): void
    {
        $this->mostrandoFormulario = false;
        $this->resetValidation();
    }

    public function setFormAlias(string $alias): void
    {
        $this->formAlias = $alias;

        if ($alias === 'Regalo') {
            $this->formNombreContacto = '';
        } elseif (empty($this->formNombreContacto)) {
            $this->formNombreContacto = auth()->user()?->name ?? '';
        }
    }

    public function setFormCoordinates(float $lat, float $lng): void
    {
        $this->formLatitude = $lat;
        $this->formLongitude = $lng;
    }

    public function guardarYSeleccionar(): void
    {
        $this->validate();

        $dto = new DireccionDTO(
            alias: trim($this->formAlias),
            nombreContacto: trim($this->formNombreContacto),
            telefonoContacto: preg_replace('/[\s\-]/', '', $this->formTelefono),
            municipio: $this->formMunicipio,
            zonaOBarrio: trim($this->formZonaBarrio) ?: null,
            direccionLinea1: trim($this->formDireccion),
            direccionLinea2: null,
            referencia: trim($this->formReferencia) ?: null,
            latitude: $this->formLatitude,
            longitude: $this->formLongitude,
            mapboxPlaceId: null,
            esPrincipal: false,
        );

        $direccion = app(DireccionService::class)->create(auth()->user(), $dto);

        $this->cerrarFormulario();
        $this->seleccionarDireccion($direccion->id);
    }

    public function render(): View
    {
        $direcciones = $this->direcciones();
        $coverageService = app(DeliveryCoverageService::class);
        $coverageStates = $direcciones->mapWithKeys(fn (Direccion $d): array => [
            $d->id => $coverageService->coverageStateFor($d),
        ]);

        return view('livewire.checkout.selector-direccion', [
            'direcciones' => $direcciones,
            'coverageStates' => $coverageStates,
            'municipios' => ['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'],
        ]);
    }

    private function direcciones(): Collection
    {
        return Direccion::query()
            ->where('user_id', auth()->id())
            ->active()
            ->orderByDesc('es_principal')
            ->orderBy('alias')
            ->get();
    }

    private function direccionDelCliente(int $direccionId): Direccion
    {
        return Direccion::query()
            ->where('user_id', auth()->id())
            ->active()
            ->whereKey($direccionId)
            ->firstOrFail();
    }
}
