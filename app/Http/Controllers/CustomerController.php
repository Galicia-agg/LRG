<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Customers/Index', [
            'customers' => $this->customers->all(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->customers->create($request->validated());

        return redirect()->route('customers.index')->with('success', 'Cliente creado correctamente.');
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return redirect()->route('customers.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customers->delete($customer);

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado.');
    }
}
