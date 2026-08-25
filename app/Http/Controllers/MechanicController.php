<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMechanicRequest;
use App\Models\Mechanic;
use App\Repositories\Contracts\MechanicRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MechanicController extends Controller
{
    public function __construct(
        private readonly MechanicRepositoryInterface $mechanics,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Mechanics/Index', [
            'mechanics' => $this->mechanics->all(),
        ]);
    }

    public function store(StoreMechanicRequest $request): RedirectResponse
    {
        $mechanic = $this->mechanics->create($request->validated());

        return back()
            ->with('success', 'Mecánico registrado correctamente.')
            ->with('mechanicId', $mechanic->id);
    }

    public function update(StoreMechanicRequest $request, Mechanic $mechanic): RedirectResponse
    {
        $this->mechanics->update($mechanic, $request->validated());

        return back()->with('success', 'Mecánico actualizado correctamente.');
    }

    public function destroy(Mechanic $mechanic): RedirectResponse
    {
        $this->mechanics->update($mechanic, ['active' => false]);

        return back()->with('success', 'Mecánico desactivado.');
    }
}
