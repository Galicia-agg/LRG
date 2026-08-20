<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => $this->suppliers->all(),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->suppliers->create($request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->validated());

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->suppliers->delete($supplier);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado.');
    }
}
