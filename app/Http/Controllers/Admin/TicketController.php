<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->with('user')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('last_reply_at')->paginate(30)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load('replies.user', 'user');

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:open,in_progress,waiting_user,resolved,closed'],
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_staff' => true,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'status' => $data['status'],
            'assigned_to' => $request->user()->id,
            'last_reply_at' => now(),
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);

        $this->notifier->sendTemplate('support_ticket_reply', $ticket->user->email, [
            'name' => $ticket->user->name,
            'reference' => $ticket->reference,
            'message' => $data['message'],
        ], $ticket->user->name);

        $this->notifier->notify($ticket->user, 'ticket_reply', 'Support replied',
            'Your ticket '.$ticket->reference.' has a new reply.', route('support.show', $ticket), 'chat');

        return back()->with('status', 'Reply sent.');
    }
}
