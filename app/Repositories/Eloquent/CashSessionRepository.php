<?php

namespace App\Repositories\Eloquent;

use App\Models\CashSession;
use App\Repositories\Contracts\CashSessionRepositoryInterface;

class CashSessionRepository extends BaseRepository implements CashSessionRepositoryInterface
{
    public function __construct(CashSession $model)
    {
        parent::__construct($model);
    }

    public function openSessionForUser(int $userId): ?CashSession
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }
}
