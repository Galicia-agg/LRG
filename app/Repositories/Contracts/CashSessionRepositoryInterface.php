<?php

namespace App\Repositories\Contracts;

use App\Models\CashSession;

interface CashSessionRepositoryInterface extends RepositoryInterface
{
    public function openSessionForUser(int $userId): ?CashSession;
}
