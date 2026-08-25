<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteWorkOrderRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Models\CommonFailure;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Mechanic;
use App\Models\WorkOrder;
use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Repositories\Contracts\CommonFailureRepositoryInterface;
use App\Repositories\Contracts\CommonServiceRepositoryInterface;
use App\Repositories\Contracts\MechanicRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrders,
        private readonly ProductRepositoryInterface $products,
        private readonly CashSessionRepositoryInterface $cashSessions,
        private readonly MechanicRepositoryInterface $mechanics,
        private readonly CommonFailureRepositoryInterface $commonFailures,
        private readonly CommonServiceRepositoryInterface $commonServices,
        private readonly WorkOrderService $workOrderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString() ?: null;
        $failureId = $request->filled('failure') ? (int) $request->input('failure') : null;
        $type = $request->filled('type') ? $request->string('type')->toString() : null;
        $serviceScope = $request->filled('service_scope') ? $request->string('service_scope')->toString() : null;

        return Inertia::render('Workshop/Index', [
            'workOrders' => $this->workOrders->forStatus($status, $failureId, $type, $serviceScope)
                ->map(fn (WorkOrder $order) => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'type' => $order->type,
                    'service_scope' => $order->service_scope,
                    'customer_name' => $order->customer->name,
                    'vehicle_label' => $order->vehicle->label(),
                    'vehicle_plate' => $order->vehicle->plate,
                    'mechanic' => $order->mechanic?->name,
                    'total' => (float) $order->total,
                    'created_at' => $order->created_at->toIso8601String(),
                    'estimated_delivery_date' => $order->estimated_delivery_date?->toDateString(),
                    'failures' => $order->failures->pluck('description')->values(),
                ])
                ->values(),
            'filters' => ['status' => $status, 'failure' => $failureId, 'type' => $type, 'service_scope' => $serviceScope],
            'commonFailures' => $this->commonFailures->active(),
        ]);
    }

    public function report(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);

        $orders = $this->workOrders->forDateRange($from, $to);
        $billed = $orders->where('status', 'entregado');

        $laborTotal = (float) $billed->sum('labor_total');
        $partsTotal = (float) $billed->sum('parts_total');

        $byMechanic = $billed
            ->groupBy(fn (WorkOrder $order) => $order->mechanic?->id ?? 0)
            ->map(fn ($group) => [
                'name' => $group->first()->mechanic?->name ?? 'Sin asignar',
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();

        $byStatus = $orders
            ->groupBy('status')
            ->map(fn ($group, $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $byType = $billed
            ->groupBy('type')
            ->map(fn ($group, $type) => [
                'type' => $type,
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
            ])
            ->values();

        $byServiceScope = $billed
            ->where('type', 'servicio')
            ->groupBy(fn (WorkOrder $order) => $order->service_scope ?? 'sin_definir')
            ->map(fn ($group, $scope) => [
                'scope' => $scope,
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
            ])
            ->values();

        $topRepairs = $billed
            ->where('type', 'reparacion')
            ->flatMap(fn (WorkOrder $order) => $order->laborItems)
            ->groupBy('description')
            ->map(fn ($group, $description) => [
                'description' => $description,
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $topServiceTasks = $billed
            ->where('type', 'servicio')
            ->flatMap(fn (WorkOrder $order) => $order->services)
            ->groupBy('description')
            ->map(fn ($group, $description) => [
                'description' => $description,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $topParts = $billed
            ->flatMap(fn (WorkOrder $order) => $order->parts)
            ->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? 'Producto eliminado',
                'quantity' => (float) $group->sum('quantity'),
                'total' => (float) $group->sum('subtotal'),
            ])
            ->sortByDesc('total')
            ->take(10)
            ->values();

        return Inertia::render('Workshop/Report', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => [
                'ordersCount' => $orders->count(),
                'billedCount' => $billed->count(),
                'laborTotal' => $laborTotal,
                'partsTotal' => $partsTotal,
                'total' => $laborTotal + $partsTotal,
                'byMechanic' => $byMechanic,
                'byStatus' => $byStatus,
                'byType' => $byType,
                'byServiceScope' => $byServiceScope,
                'topRepairs' => $topRepairs,
                'topServiceTasks' => $topServiceTasks,
                'topParts' => $topParts,
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())
            : Carbon::today();

        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())
            : $to->copy()->subDays(29);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public function create(): Response
    {
        return Inertia::render('Workshop/Create', [
            'customers' => Customer::with('vehicles')->orderBy('name')->get()
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'nit' => $customer->nit,
                    'phone' => $customer->phone,
                    'vehicles' => $customer->vehicles->map(fn (CustomerVehicle $vehicle) => [
                        'id' => $vehicle->id,
                        'label' => $vehicle->label(),
                        'plate' => $vehicle->plate,
                        'mileage' => (float) ($vehicle->mileage ?? 0),
                    ])->values(),
                ])
                ->values(),
            'mechanics' => $this->mechanics->active(),
            'commonFailures' => $this->commonFailures->active(),
            'commonServices' => $this->commonServices->active(),
        ]);
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $vehicle = CustomerVehicle::findOrFail($request->input('customer_vehicle_id'));
        $mechanic = $request->filled('mechanic_id') ? Mechanic::find($request->input('mechanic_id')) : null;
        $failureIds = $request->input('failure_ids', []);
        $serviceIds = $request->input('service_ids', []);

        $reportedIssue = $request->input('reported_issue');

        if (! $reportedIssue && ! empty($failureIds)) {
            $reportedIssue = CommonFailure::query()
                ->whereIn('id', $failureIds)
                ->pluck('description')
                ->implode('. ').'.';
        }

        $workOrder = $this->workOrderService->createWorkOrder(
            $vehicle,
            $mechanic,
            $reportedIssue,
            $request->input('mileage_in') !== null ? (float) $request->input('mileage_in') : null,
            $request->input('estimated_delivery_date'),
            $request->input('notes'),
            $failureIds,
            $request->input('type', 'reparacion'),
            $request->input('service_scope'),
            $serviceIds,
        );

        return redirect()->route('workshop.show', $workOrder->id)->with('success', 'Orden de servicio creada.');
    }

    public function show(WorkOrder $workOrder): Response
    {
        $workOrder->load(['customer', 'vehicle', 'mechanic', 'laborItems', 'parts.product', 'sale', 'failures', 'services']);

        $session = auth()->check() ? $this->cashSessions->openSessionForUser(auth()->id()) : null;

        return Inertia::render('Workshop/Show', [
            'workOrder' => $this->presentWorkOrder($workOrder),
            'products' => $this->products->activeCatalog(),
            'commonFailures' => $this->commonFailures->active(),
            'commonServices' => $this->commonServices->active(),
            'cashSessionOpen' => $session !== null,
        ]);
    }

    public function toggleFailure(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'common_failure_id' => ['required', 'integer', 'exists:common_failures,id'],
        ]);

        try {
            $this->workOrderService->toggleFailure($workOrder, (int) $data['common_failure_id']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back();
    }

    public function toggleService(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'common_service_id' => ['required', 'integer', 'exists:common_services,id'],
        ]);

        try {
            $this->workOrderService->toggleService($workOrder, (int) $data['common_service_id']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back();
    }

    public function addLaborItem(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->workOrderService->addLaborItem($workOrder, $data['description'], (float) $data['amount']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['labor' => $e->getMessage()]);
        }

        return back()->with('success', 'Mano de obra agregada.');
    }

    public function removeLaborItem(WorkOrder $workOrder, int $laborItem): RedirectResponse
    {
        try {
            $this->workOrderService->removeLaborItem($workOrder, $laborItem);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['labor' => $e->getMessage()]);
        }

        return back();
    }

    public function addPart(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->workOrderService->addPart($workOrder, (int) $data['product_id'], (float) $data['quantity']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['part' => $e->getMessage()]);
        }

        return back()->with('success', 'Repuesto agregado.');
    }

    public function removePart(WorkOrder $workOrder, int $part): RedirectResponse
    {
        try {
            $this->workOrderService->removePart($workOrder, $part);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['part' => $e->getMessage()]);
        }

        return back();
    }

    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:recibido,en_proceso,listo'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', 'in:servicio,reparacion'],
            'service_scope' => ['nullable', 'in:menor,mayor'],
        ]);

        try {
            $this->workOrderService->updateStatus(
                $workOrder,
                $data['status'],
                $data['diagnosis'] ?? null,
                $data['type'] ?? null,
                $data['service_scope'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Orden actualizada.');
    }

    public function cancel(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->workOrderService->cancel($workOrder, $data['reason'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('workshop.index')->with('success', 'Orden cancelada.');
    }

    public function complete(CompleteWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $session = $this->cashSessions->openSessionForUser($request->user()->id);

        abort_if(! $session, 422, 'No hay una caja abierta.');

        try {
            $this->workOrderService->completeAndBill($workOrder, $session, $request->user(), $request->input('payments'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payments' => $e->getMessage()]);
        }

        return redirect()->route('workshop.show', $workOrder->id)->with('success', 'Orden entregada y cobrada correctamente.');
    }

    public function print(WorkOrder $workOrder): Response
    {
        $workOrder->load(['customer', 'vehicle', 'mechanic', 'laborItems', 'parts.product', 'failures', 'services']);

        return Inertia::render('Workshop/Print', [
            'workOrder' => $this->presentWorkOrder($workOrder),
            'business' => ['name' => config('app.name')],
        ]);
    }

    private function presentWorkOrder(WorkOrder $workOrder): array
    {
        return [
            'id' => $workOrder->id,
            'status' => $workOrder->status,
            'type' => $workOrder->type,
            'service_scope' => $workOrder->service_scope,
            'created_at' => $workOrder->created_at->toIso8601String(),
            'delivered_at' => $workOrder->delivered_at?->toIso8601String(),
            'estimated_delivery_date' => $workOrder->estimated_delivery_date?->toDateString(),
            'mileage_in' => (float) ($workOrder->mileage_in ?? 0),
            'reported_issue' => $workOrder->reported_issue,
            'diagnosis' => $workOrder->diagnosis,
            'notes' => $workOrder->notes,
            'labor_total' => (float) $workOrder->labor_total,
            'parts_total' => (float) $workOrder->parts_total,
            'discount' => (float) $workOrder->discount,
            'total' => (float) $workOrder->total,
            'sale_id' => $workOrder->sale_id,
            'customer' => [
                'id' => $workOrder->customer->id,
                'name' => $workOrder->customer->name,
                'phone' => $workOrder->customer->phone,
                'nit' => $workOrder->customer->nit,
            ],
            'vehicle' => [
                'id' => $workOrder->vehicle->id,
                'label' => $workOrder->vehicle->label(),
                'plate' => $workOrder->vehicle->plate,
                'color' => $workOrder->vehicle->color,
                'vin' => $workOrder->vehicle->vin,
            ],
            'mechanic' => $workOrder->mechanic?->name,
            'failures' => $workOrder->failures->map(fn ($failure) => [
                'id' => $failure->id,
                'description' => $failure->description,
            ])->values(),
            'services' => $workOrder->services->map(fn ($service) => [
                'id' => $service->id,
                'description' => $service->description,
            ])->values(),
            'laborItems' => $workOrder->laborItems->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'amount' => (float) $item->amount,
            ])->values(),
            'parts' => $workOrder->parts->map(fn ($part) => [
                'id' => $part->id,
                'name' => $part->product?->name ?? 'Producto eliminado',
                'sku' => $part->product?->sku,
                'quantity' => (float) $part->quantity,
                'unit_price' => (float) $part->unit_price,
                'subtotal' => (float) $part->subtotal,
            ])->values(),
        ];
    }
}
