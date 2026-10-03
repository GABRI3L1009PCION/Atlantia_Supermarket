<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AprobarVendedorRequest;
use App\Http\Requests\Admin\SuspenderVendedorRequest;
use App\Models\Vendor;
use App\Services\Reportes\VendorReportPdf;
use App\Services\Vendedores\VendorAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
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
    public function __construct(private readonly VendorAdminService $vendorAdminService) {}

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
    public function document(Vendor $vendor, string $document): StreamedResponse
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

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Aprueba un vendedor.
     */
    public function approve(AprobarVendedorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('approve', $vendor);
        $this->vendorAdminService->approve($vendor, $request->validated(), $request->user());

        return back()->with('success', 'Vendedor aprobado correctamente.');
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
