<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Models\CashSession;
use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Services\CashSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CashSessionController extends Controller
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessions,
        private readonly CashSessionService $cashSessionService,
    ) {
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->cashSessions->openSessionForUser($request->user()->id)) {
            return redirect()->route('pos.create');
        }

        return Inertia::render('CashSessions/Open');
    }

    public function store(OpenCashSessionRequest $request): RedirectResponse
    {
        try {
            $this->cashSessionService->open($request->user(), (float) $request->input('opening_amount'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['opening_amount' => $e->getMessage()]);
        }

        return redirect()->route('pos.create')->with('success', 'Caja abierta correctamente.');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $session = $this->cashSessions->openSessionForUser($request->user()->id);

        if (! $session) {
            return redirect()->route('cash-sessions.create');
        }

        return Inertia::render('CashSessions/Close', ['cashSession' => $session]);
    }

    public function update(CloseCashSessionRequest $request, CashSession $cashSession): RedirectResponse
    {
        try {
            $this->cashSessionService->close(
                $cashSession,
                (float) $request->input('closing_amount'),
                $request->input('notes'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['closing_amount' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'Caja cerrada correctamente.');
    }
}
