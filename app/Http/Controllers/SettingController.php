<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'deliveryEnabled' => Setting::getBool('delivery_enabled', true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_enabled' => ['required', 'boolean'],
        ]);

        Setting::set('delivery_enabled', $data['delivery_enabled'] ? 'true' : 'false');

        return back()->with('success', 'Configuración actualizada.');
    }
}
