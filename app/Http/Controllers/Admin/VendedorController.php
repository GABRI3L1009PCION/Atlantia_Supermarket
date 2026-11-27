<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Vendedor\OperacionTiendaController;
use App\Http\Requests\Admin\AprobarVendedorRequest;
use App\Http\Requests\Admin\SuspenderVendedorRequest;
use App\Models\Vendor;
use App\Services\Auth\TwoFactorService;
use App\Notifications\PruebaPlanNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\PruebaAperturaService;
use App\Services\Reportes\VendorReportPdf;
use App\Services\Vendedores\AccesoVendedorService;
use App\Services\Vendedores\VendorAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador administrativo de vendedores.
 */
class VendedorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(
        private readonly VendorAdminService $vendorAdminService,
        private readonly AccesoVendedorService $acceso,
    ) {}

    /**
     * Lista solicitudes y vendedores registrados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        return view('admin.vendedores.index', ['vendors' => $this->vendorAdminService->paginate($request->all())]);
    }

    /**
     * Muestra el detalle de un vendedor.
     */
    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        return view('admin.vendedores.show', ['vendor' => $this->vendorAdminService->detail($vendor)]);
    }

    /**
     * Descarga el reporte administrativo de vendedores en PDF.
     */
    public function reportPdf(Request $request, VendorReportPdf $reportPdf): Response
    {
        $this->authorize('viewAny', Vendor::class);

        $filename = 'reporte-vendedores-'.now()->format('Y-m-d-His').'.pdf';

        return response($reportPdf->make($this->vendorAdminService->report()), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Descarga un documento privado de solicitud de vendedor.
     */
    public function document(Request $request, Vendor $vendor, string $document): StreamedResponse
    {
        $this->authorize('view', $vendor);

        $allowedDocuments = [
            'document_front' => 'documento-frente',
            'document_back' => 'documento-reverso',
            'business_logo' => 'logo-negocio',
            'bank_proof' => 'comprobante-bancario',
            'nit_file' => 'nit-rit',
        ];

        abort_unless(array_key_exists($document, $allowedDocuments), 404);

        $path = (string) data_get($vendor->documents ?? [], $document);

        abort_if($path === '', 404);

        $disk = $document === 'business_logo'
            ? 'public'
            : (string) config('filesystems.private_disk', 'local');

        if (! Storage::disk($disk)->exists($path) && $disk !== 'public' && Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = $allowedDocuments[$document]
            .'-'
            .($vendor->application_code ?: $vendor->uuid)
            .($extension !== '' ? '.'.$extension : '');

        /* Con ?ver=1 se muestra dentro del visor del panel; sin él se descarga. */
        if ($request->boolean('ver')) {
            return Storage::disk($disk)->response($path, $filename, [
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Aprueba un vendedor.
     */
    public function approve(AprobarVendedorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        $vendor = $this->vendorAdminService->approve($vendor, $request->validated(), $request->user());

        /* Promoción de apertura: la tienda arranca con el plan que eligió, gratis por unos días. */
        $prueba = '';
        $apertura = app(PruebaAperturaService::class);
        if ($apertura->disponiblePara($vendor)) {
            $plan = array_key_exists((string) $vendor->plan, PlanesVendedorService::contratables()) ? (string) $vendor->plan : PlanesVendedorService::base();
            $vendor = $apertura->iniciar($vendor, $plan);
            $vendor->user?->notify(new PruebaPlanNotification($vendor, 'iniciada'));
            $prueba = ' Empieza con '.PruebaAperturaService::dias().' días gratis del plan '.PlanesVendedorService::datos($plan)['nombre'].'.';
        }

        $correo = AccesoVendedorService::correoEnmascarado($vendor->user?->email);

        return $this->acceso->enviarBienvenida($vendor)
            ? back()->with('success', "Vendedor aprobado. Le enviamos a {$correo} el enlace para crear su contraseña.".$prueba)
            : back()->with('warning', 'Vendedor aprobado, pero no se pudo enviar el correo de acceso. Usa «Reenviar acceso» cuando el correo esté configurado.');
    }

    /**
     * Rechaza una solicitud pendiente y avisa al solicitante con el motivo.
     */
    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        abort_unless($vendor->status === 'pending', 422, 'Solo se pueden rechazar solicitudes pendientes.');

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'motivo.required' => 'Escribe el motivo del rechazo: se le enviará al solicitante.',
            'motivo.min' => 'Explica el motivo con al menos 10 caracteres.',
        ]);

        $vendor = $this->vendorAdminService->reject($vendor, $datos['motivo'], $request->user());

        return $this->acceso->enviarRechazo($vendor)
            ? back()->with('success', 'Solicitud rechazada. Le enviamos el motivo por correo.')
            : back()->with('warning', 'Solicitud rechazada, pero no se pudo enviar el correo con el motivo.');
    }

    /**
     * Envía de nuevo el enlace para crear contraseña a un vendedor aprobado.
     */
    public function reenviarAcceso(Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        abort_unless($vendor->status === 'approved', 422, 'El vendedor no está aprobado.');

        return $this->acceso->enviarBienvenida($vendor)
            ? back()->with('success', 'Enlace de acceso reenviado a '.AccesoVendedorService::correoEnmascarado($vendor->user?->email).'.')
            : back()->with('warning', 'No se pudo enviar el correo. Revisa la configuración de correo.');
    }

    /**
     * Reinicia el doble factor del vendedor (por ejemplo, si perdió su teléfono).
     * En su próximo ingreso deberá escanear un QR nuevo.
     */
    public function reiniciarDosFactores(Vendor $vendor, TwoFactorService $dosFactores): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        abort_if($vendor->user === null, 404);

        $dosFactores->disable($vendor->user);

        return back()->with('success', 'Doble factor reiniciado. En su próximo ingreso configurará Google Authenticator de nuevo.');
    }

    /**
     * Suspende un vendedor.
     */
    public function suspend(SuspenderVendedorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $this->vendorAdminService->suspend($vendor, $request->validated(), $request->user());

        return back()->with('success', 'Vendedor suspendido correctamente.');
    }

    /**
     * Pausa la recepcion de pedidos de una tienda (por saturacion o cierre del proveedor).
     */
    public function pausar(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $data = $request->validate([
            'duracion' => ['required', 'in:'.implode(',', array_keys(OperacionTiendaController::PAUSAS))],
        ]);

        OperacionTiendaController::aplicarPausa($vendor, $data['duracion'], 'Pausa desde administracion');

        return back()->with('success', 'Tienda en pausa.');
    }

    /**
     * Asigna un plan y registra el pago del mes (efectivo, transferencia o acuerdo).
     * Para el Personalizado se indican el precio, la comision y el limite de productos acordados.
     */
    public function asignarPlan(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(PlanesVendedorService::todos()))],
            'precio' => ['required_if:plan,personalizado', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'comision' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'limite_productos' => ['required_if:plan,personalizado', 'nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        app(PlanesVendedorService::class)->aplicar(
            $vendor,
            $data['plan'],
            null,
            isset($data['precio']) ? (float) $data['precio'] : null,
            isset($data['comision']) ? (float) $data['comision'] : null,
            isset($data['limite_productos']) ? (int) $data['limite_productos'] : null,
        );

        return back()->with('success', 'Plan asignado y mes registrado como pagado.');
    }

    /**
     * Reanuda la recepcion de pedidos.
     */
    public function reanudar(Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $vendor->update(['pausado_hasta' => null, 'pausa_motivo' => null]);

        return back()->with('success', 'La tienda vuelve a recibir pedidos.');
    }

    /**
     * Reactiva un vendedor suspendido.
     */
    public function reactivate(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('reactivate', $vendor);
        $this->vendorAdminService->reactivate($vendor, $request->user());

        return back()->with('success', 'Vendedor reactivado correctamente.');
    }

    /**
     * Elimina logicamente un vendedor.
     */
    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);
        $this->vendorAdminService->delete($vendor);

        return redirect()->route('admin.vendedores.index')->with('success', 'Vendedor eliminado correctamente.');
    }
}
