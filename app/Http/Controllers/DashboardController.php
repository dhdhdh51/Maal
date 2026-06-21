<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected CatalogService $catalog) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $continue = $this->catalog->continueWatching($user, 6);
        $this->catalog->decorate($continue, $user);

        return view('dashboard.index', [
            'user' => $user,
            'continue' => $continue,
            'activeAccess' => $user->categoryAccess()->active()->with('category')->get(),
            'favoritesCount' => $user->favorites()->count(),
            'devicesCount' => $user->devices()->count(),
            'openTickets' => $user->tickets()->whereIn('status', ['open', 'in_progress', 'waiting_user'])->count(),
            'referralLink' => url('/?ref='.$user->referral_code),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('dashboard.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'newsletter_opt_in' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
            'newsletter_opt_in' => $request->boolean('newsletter_opt_in'),
        ]);

        AuditLogger::log('user.profile_updated', $user, 'Profile updated', actor: $user);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $request->user()->update(['password' => Hash::make($request->string('password'))]);
        AuditLogger::log('user.password_changed', $request->user(), actor: $request->user());

        return back()->with('status', 'Password changed.');
    }
}
