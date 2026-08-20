<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CashSessionRepositoryInterface $cashSessions,
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('Orders/Index', [
            'orders' => $this->orders->forStatus($status !== '' ? $status : null),
            'status' => $status,
            'hasOpenCashSession' => $this->cashSessions->openSessionForUser($request->user()->id) !== null,
        ]);
    }

    public function confirm(Order $order): RedirectResponse
    {
        try {
            $this->orderService->confirm($order);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Pedido confirmado.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->cancel($order, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Pedido cancelado.');
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        $session = $this->cashSessions->openSessionForUser($request->user()->id);

        if (! $session) {
            return back()->withErrors(['order' => 'Debes abrir una caja antes de completar el pedido.']);
        }

        try {
            $this->orderService->complete(
                $order,
                $session,
                $request->user(),
                $request->string('payment_method', 'efectivo')->toString(),
            );
        } catch (InsufficientStockException|InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Pedido completado y registrado como venta.');
    }
}
