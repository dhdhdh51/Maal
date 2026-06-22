<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __construct(protected Notifier $notifier) {}

    public function index(Request $request): View
    {
        $tickets = $request->user()->tickets()->latest()->paginate(20);

        return view('support.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('support.create', [
            'categories' => ['payment', 'access', 'playback', 'refund', 'account', 'other'],
            'faqs' => (array) setting('support_faqs', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:payment,access,playback,refund,account,other'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ticket = $request->user()->tickets()->create([
            'reference' => 'TKT-'.strtoupper(Str::random(8)),
            'category' => $data['category'],
            'subject' => $data['subject'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'message' => $data['message'],
            'attachments' => $this->storeAttachments($request),
        ]);

        return redirect()->route('support.show', $ticket)->with('status', 'Ticket created. We will respond soon.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        $ticket->load('replies.user');

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'message' => $data['message'],
            'attachments' => $this->storeAttachments($request),
        ]);

        $ticket->update(['status' => 'open', 'last_reply_at' => now()]);

        return back()->with('status', 'Reply sent.');
    }

    /**
     * @return array<int, string>|null
     */
    protected function storeAttachments(Request $request): ?array
    {
        if (! $request->hasFile('attachments')) {
            return null;
        }

        $paths = [];
        foreach ($request->file('attachments') as $file) {
            $paths[] = $file->store('support', 'public');
        }

        return $paths;
    }
}
