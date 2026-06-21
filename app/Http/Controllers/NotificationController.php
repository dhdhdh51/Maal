<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markRead();

        return $notification->action_url ? redirect()->to($notification->action_url) : back();
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return ApiResponse::success(null, 'All notifications marked read.');
    }
}
