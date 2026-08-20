<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $roles = $user?->getRoleNames() ?? collect();
        $permissions = $user?->getAllPermissions()->pluck('name') ?? collect();

        // getRoleNames()/getAllPermissions() cache the `roles` (and `roles.permissions`)
        // relations on $user, and route middleware further down the pipeline (e.g.
        // Spatie's `permission:` middleware) can reload them again after this point.
        // $user is a shared object reference, so if we handed it over as-is those later
        // reloads would leak into the response even though we stripped them here. Take a
        // plain-array snapshot instead, which is immune to relation loading that happens
        // later in the request lifecycle.
        $userData = $user
            ?->unsetRelation('roles')
            ->unsetRelation('permissions')
            ->toArray();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
