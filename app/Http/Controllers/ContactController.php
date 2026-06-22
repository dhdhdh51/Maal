<?php

namespace App\Http\Controllers;

use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function submit(Request $request, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Logged-in users get a tracked support ticket; guests notify support inbox.
        if ($user = $request->user()) {
            $ticket = $user->tickets()->create([
                'reference' => 'TKT-'.strtoupper(Str::random(8)),
                'category' => 'other',
                'subject' => 'Contact form',
                'status' => 'open',
                'last_reply_at' => now(),
            ]);
            $ticket->replies()->create(['user_id' => $user->id, 'is_staff' => false, 'message' => $data['message']]);
        } else {
            $notifier->sendTemplate('support_ticket_reply', (string) setting('support_email', config('mail.from.address')), [
                'name' => 'Support',
                'reference' => 'CONTACT',
                'message' => "From {$data['name']} <{$data['email']}>:\n\n{$data['message']}",
            ]);
        }

        return back()->with('status', 'Thanks for reaching out — we will reply soon.');
    }
}
