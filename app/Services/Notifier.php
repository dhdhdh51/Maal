<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Central dispatch for transactional email (rendered from editable
 * email_templates) and in-app notifications.
 */
class Notifier
{
    /**
     * Send an email rendered from an editable template key. Always injects
     * site-wide variables (site_name, support_email).
     *
     * @param  array<string, string>  $vars
     */
    public function sendTemplate(string $key, string $toEmail, array $vars = [], ?string $toName = null): bool
    {
        $template = EmailTemplate::where('key', $key)->where('is_active', true)->first();

        if (! $template) {
            Log::warning("Email template [{$key}] missing or inactive.");

            return false;
        }

        $vars = array_merge([
            'site_name' => (string) setting('site_name', config('app.name')),
            'support_email' => (string) setting('support_email', ''),
        ], $vars);

        $subject = $template->renderSubject($vars);
        $html = $template->render($vars);

        try {
            Mail::html($html, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName)->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed sending [{$key}] to {$toEmail}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Create an in-app notification for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?string $icon = null,
        array $data = [],
    ): Notification {
        return $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'icon' => $icon,
            'data' => $data ?: null,
        ]);
    }
}
