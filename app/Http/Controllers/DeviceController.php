<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $devices = $request->user()->devices()
            ->withCount(['sessions' => fn ($q) => $q->whereNull('revoked_at')])
            ->latest('last_active_at')
            ->get();

        $currentFingerprint = $request->attributes->get('device_fingerprint');

        return view('dashboard.devices', compact('devices', 'currentFingerprint'));
    }

    public function destroy(Request $request, UserDevice $device): RedirectResponse
    {
        abort_unless($device->user_id === $request->user()->id, 403);

        // Revoke sessions tied to the device, then remove it.
        $device->sessions()->update(['revoked_at' => now(), 'is_playing' => false]);
        $device->delete();

        AuditLogger::log('device.removed', $request->user(), 'Device removed from account', actor: $request->user());

        return back()->with('status', 'Device removed.');
    }

    public function logoutAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->sessions()->update(['revoked_at' => now(), 'is_playing' => false]);

        // Invalidate other browser sessions (keep the current one).
        Auth::logoutOtherDevices((string) $request->input('password', ''));

        AuditLogger::log('device.logout_all', $user, 'Logged out of all other devices', actor: $user);

        return back()->with('status', 'Signed out of all other devices.');
    }
}
