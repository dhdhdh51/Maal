<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Static/legal pages. Body content is editable via settings
     * (legal.<key>_content), with a sensible default fallback.
     */
    protected array $pages = [
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'content-policy' => 'Content Policy',
        'refund-policy' => 'Refund Policy',
        'dmca' => 'Copyright / DMCA',
    ];

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, $this->pages), 404);

        $key = str_replace('-', '_', $page);

        return view('pages.legal', [
            'title' => $this->pages[$page],
            'body' => (string) setting("legal.{$key}_content", $this->default($page)),
            'updatedAt' => setting("legal.{$key}_updated_at"),
        ]);
    }

    protected function default(string $page): string
    {
        $site = setting('site_name', config('app.name'));
        $email = setting('support_email', 'support@example.com');

        return match ($page) {
            'terms' => "<p>By using {$site} you agree to use the platform lawfully, that you meet the minimum age requirement, and that access to paid categories is governed by the plan you purchase. Accounts may be suspended for abuse, fraud or unlawful activity.</p>",
            'privacy' => "<p>{$site} collects the information needed to operate your account, process payments and deliver content. We never sell your personal data. Contact {$email} for data requests.</p>",
            'content-policy' => '<p>Only lawful content with verified rights and consent is permitted. Uploaders must confirm content rights before publishing. Report violations using the report tools provided on every video and category.</p>',
            'refund-policy' => '<p>Digital access purchases are generally non-refundable once content has been accessed, except where required by law. Contact support for billing issues.</p>',
            'dmca' => "<p>To submit a copyright complaint, use the report option on the relevant content or email {$email} with the work identification, your contact details and a good-faith statement. We action valid complaints promptly.</p>",
            default => '',
        };
    }
}
