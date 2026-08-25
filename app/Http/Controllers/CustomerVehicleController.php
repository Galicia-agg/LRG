<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerVehicleRequest;
use App\Models\CustomerVehicle;
use App\Models\WorkOrder;
use App\Repositories\Contracts\CustomerVehicleRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerVehicleController extends Controller
{
    public function __construct(
        private readonly CustomerVehicleRepositoryInterface $vehicles,
    ) {
    }

    public function index(Request $request): Response
    {
        $term = $request->string('q')->toString();

        $vehicles = CustomerVehicle::query()
            ->with('customer')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('plate', 'ilike', "%{$term}%")
                        ->orWhere('brand', 'ilike', "%{$term}%")
                        ->orWhere('model', 'ilike', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%{$term}%"));
                });
            })
            ->orderBy('brand')
            ->get();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles->map(fn (CustomerVehicle $vehicle) => [
                'id' => $vehicle->id,
                'label' => $vehicle->label(),
                'plate' => $vehicle->plate,
                'customer_name' => $vehicle->customer->name,
            ])->values(),
            'filters' => ['q' => $term],
        ]);
    }

    public function show(Request $request, CustomerVehicle $vehicle): Response
    {
        $vehicle->load('customer');

        $type = $request->filled('type') ? $request->string('type')->toString() : null;
        $serviceScope = $request->filled('service_scope') ? $request->string('service_scope')->toString() : null;
        $status = $request->filled('status') ? $request->string('status')->toString() : null;

        $allOrders = WorkOrder::query()
            ->where('customer_vehicle_id', $vehicle->id)
            ->with(['mechanic', 'services'])
            ->latest('created_at')
            ->get();

        $workOrders = $allOrders
            ->when($type, fn ($orders) => $orders->where('type', $type))
            ->when($serviceScope, fn ($orders) => $orders->where('service_scope', $serviceScope))
            ->when($status, fn ($orders) => $orders->where('status', $status));

        return Inertia::render('Vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'plate' => $vehicle->plate,
                'vin' => $vehicle->vin,
                'color' => $vehicle->color,
                'mileage' => (float) ($vehicle->mileage ?? 0),
                'notes' => $vehicle->notes,
                'customer' => [
                    'id' => $vehicle->customer->id,
                    'name' => $vehicle->customer->name,
                    'phone' => $vehicle->customer->phone,
                ],
            ],
            'workOrders' => $workOrders->map(fn (WorkOrder $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'type' => $order->type,
                'service_scope' => $order->service_scope,
                'created_at' => $order->created_at->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'mileage_in' => (float) ($order->mileage_in ?? 0),
                'reported_issue' => $order->reported_issue,
                'diagnosis' => $order->diagnosis,
                'mechanic' => $order->mechanic?->name,
                'total' => (float) $order->total,
                'services' => $order->services->pluck('description')->values(),
            ])->values(),
            'maintenanceSummary' => [
                'services_count' => $allOrders->where('type', 'servicio')->count(),
                'repairs_count' => $allOrders->where('type', 'reparacion')->count(),
                'minor_services_count' => $allOrders->where('type', 'servicio')->where('service_scope', 'menor')->count(),
                'major_services_count' => $allOrders->where('type', 'servicio')->where('service_scope', 'mayor')->count(),
                'last_service_at' => $allOrders->where('type', 'servicio')->first()?->created_at?->toIso8601String(),
                'last_repair_at' => $allOrders->where('type', 'reparacion')->first()?->created_at?->toIso8601String(),
            ],
            'filters' => ['type' => $type, 'service_scope' => $serviceScope, 'status' => $status],
        ]);
    }

    public function store(StoreCustomerVehicleRequest $request): RedirectResponse
    {
        $vehicle = $this->vehicles->create($request->validated());

        return back()
            ->with('success', 'Vehículo registrado correctamente.')
            ->with('vehicleId', $vehicle->id);
    }
}
