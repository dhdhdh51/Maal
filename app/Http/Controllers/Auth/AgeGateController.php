<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AgeGateController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.age-gate', [
            'redirect' => $request->query('redirect', route('home')),
            'minAge' => (int) setting('min_age', 18),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']]);

        // Persist confirmation for authenticated users.
        if ($user = $request->user()) {
            $user->forceFill([
                'age_confirmed' => true,
                'age_confirmed_at' => now(),
            ])->save();
        }

        $redirect = $request->input('redirect', route('home'));
        if (! str_starts_with((string) $redirect, url('/'))) {
            $redirect = route('home');
        }

        // 1-year signed cookie for guests.
        return redirect()->to($redirect)
            ->withCookie(Cookie::make('age_confirmed', '1', 60 * 24 * 365, httpOnly: true));
    }
}
