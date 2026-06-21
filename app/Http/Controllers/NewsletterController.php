<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:40'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'user_id' => $request->user()?->id,
                'source' => $data['source'] ?? 'site',
                'utm_campaign' => $request->query('utm_campaign'),
            ]
        );

        return ApiResponse::success(null, 'Thanks for subscribing!');
    }
}
