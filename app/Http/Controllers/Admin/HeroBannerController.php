<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroBanner\StoreHeroBannerRequest;
use App\Http\Requests\Admin\HeroBanner\UpdateHeroBannerRequest;
use App\Models\HeroBanner;
use App\Services\Storefront\HeroBannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HeroBannerController extends Controller
{
    public function __construct(private readonly HeroBannerService $heroBannerService)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', HeroBanner::class);

        return view('admin.hero-banners.index', [
            'banners' => $this->heroBannerService->all(),
        ]);
    }

    public function store(StoreHeroBannerRequest $request): RedirectResponse
    {
        $this->authorize('create', HeroBanner::class);
        $this->heroBannerService->create($request->validated());

        return back()->with('success', 'Banner hero creado correctamente.');
    }

    public function update(UpdateHeroBannerRequest $request, HeroBanner $heroBanner): RedirectResponse
    {
        $this->authorize('update', $heroBanner);
        $this->heroBannerService->update($heroBanner, $request->validated());

        return back()->with('success', 'Banner hero actualizado correctamente.');
    }

    public function destroy(HeroBanner $heroBanner): RedirectResponse
    {
        $this->authorize('delete', $heroBanner);
        $this->heroBannerService->delete($heroBanner);

        return back()->with('success', 'Banner hero eliminado correctamente.');
    }
}
