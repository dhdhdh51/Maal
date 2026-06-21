<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function index(Request $request): View
    {
        $access = $request->user()->categoryAccess()
            ->with('category')
            ->latest('granted_at')
            ->get();

        return view('dashboard.access', compact('access'));
    }

    public function invoices(Request $request): View
    {
        $payments = $request->user()->payments()
            ->where('status', 'paid')
            ->latest('paid_at')
            ->paginate(20);

        return view('dashboard.invoices', compact('payments'));
    }

    public function invoice(Request $request, Payment $payment): View
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'paid', 404);

        $payment->loadMissing(['plan', 'category', 'user']);

        return view('dashboard.invoice', compact('payment'));
    }
}
