<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommonServiceRequest;
use App\Models\CommonService;
use App\Repositories\Contracts\CommonServiceRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommonServiceController extends Controller
{
    public function __construct(
        private readonly CommonServiceRepositoryInterface $commonServices,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('CommonServices/Index', [
            'commonServices' => $this->commonServices->all(),
        ]);
    }

    public function store(StoreCommonServiceRequest $request): RedirectResponse
    {
        $this->commonServices->create($request->validated());

        return back()->with('success', 'Tarea de servicio agregada al catálogo.');
    }

    public function update(StoreCommonServiceRequest $request, CommonService $commonService): RedirectResponse
    {
        $this->commonServices->update($commonService, $request->validated());

        return back()->with('success', 'Tarea de servicio actualizada.');
    }

    public function destroy(CommonService $commonService): RedirectResponse
    {
        $this->commonServices->delete($commonService);

        return back()->with('success', 'Tarea de servicio eliminada del catálogo.');
    }
}
