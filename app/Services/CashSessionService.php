<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\User;
use App\Repositories\Contracts\CashSessionRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashSessionService
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessions,
        private readonly SaleRepositoryInterface $sales,
    ) {
    }

    public function open(User $user, float $openingAmount): CashSession
    {
        if ($this->cashSessions->openSessionForUser($user->id)) {
            throw new RuntimeException('Ya existe una caja abierta para este usuario.');
        }

        return $this->cashSessions->create([
            'user_id' => $user->id,
            'opening_amount' => $openingAmount,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    public function close(CashSession $session, float $closingAmount, ?string $notes = null): CashSession
    {
        if (! $session->isOpen()) {
            throw new RuntimeException('Esta caja ya está cerrada.');
        }

        return DB::transaction(function () use ($session, $closingAmount, $notes) {
            $expected = (float) $session->opening_amount + $this->sales->totalCashForSession($session->id);

            return $this->cashSessions->update($session, [
                'closing_amount' => $closingAmount,
                'expected_amount' => $expected,
                'difference' => $closingAmount - $expected,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $notes,
            ]);
        });
    }
}
