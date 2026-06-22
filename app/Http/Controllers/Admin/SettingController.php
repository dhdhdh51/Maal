<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = Setting::all()->groupBy('group');

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $values = (array) $request->input('settings', []);

        foreach ($values as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                continue;
            }

            // Checkboxes submit only when on; normalise booleans.
            if ($setting->type === 'boolean') {
                $value = $request->boolean("settings.$key") ? '1' : '0';
            }

            $setting->update(['value' => is_array($value) ? json_encode($value) : (string) $value]);
        }

        // Ensure unchecked booleans are persisted as off.
        Setting::where('type', 'boolean')->each(function (Setting $s) use ($values) {
            if (! array_key_exists($s->key, $values)) {
                $s->update(['value' => '0']);
            }
        });

        Cache::forget(Setting::CACHE_KEY);
        AuditLogger::log('settings.updated', null, 'Settings updated');

        return back()->with('status', 'Settings saved.');
    }

    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $on = $request->boolean('enabled');
        Setting::where('key', 'maintenance_mode')->update(['value' => $on ? '1' : '0']);
        Cache::forget(Setting::CACHE_KEY);
        AuditLogger::log('maintenance.toggled', null, 'Maintenance '.($on ? 'enabled' : 'disabled'));

        return back()->with('status', 'Maintenance mode '.($on ? 'enabled' : 'disabled').'.');
    }
}
