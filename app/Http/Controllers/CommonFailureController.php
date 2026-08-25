<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommonFailureRequest;
use App\Models\CommonFailure;
use App\Repositories\Contracts\CommonFailureRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommonFailureController extends Controller
{
    public function __construct(
        private readonly CommonFailureRepositoryInterface $commonFailures,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('CommonFailures/Index', [
            'commonFailures' => $this->commonFailures->all(),
        ]);
    }

    public function store(StoreCommonFailureRequest $request): RedirectResponse
    {
        $this->commonFailures->create($request->validated());

        return back()->with('success', 'Falla común agregada al catálogo.');
    }

    public function update(StoreCommonFailureRequest $request, CommonFailure $commonFailure): RedirectResponse
    {
        $this->commonFailures->update($commonFailure, $request->validated());

        return back()->with('success', 'Falla común actualizada.');
    }

    public function destroy(CommonFailure $commonFailure): RedirectResponse
    {
        $this->commonFailures->update($commonFailure, ['active' => false]);

        return back()->with('success', 'Falla común desactivada.');
    }
}
