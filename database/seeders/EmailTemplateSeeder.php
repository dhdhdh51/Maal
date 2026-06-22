<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'registration_verify',
                'name' => 'Registration Verification',
                'subject' => 'Verify your {site_name} account',
                'body' => '<p>Hi {name},</p><p>Your verification code is <strong>{otp}</strong>. It expires in 10 minutes.</p>',
                'vars' => ['name', 'otp', 'site_name'],
            ],
            [
                'key' => 'payment_success',
                'name' => 'Payment Successful',
                'subject' => 'Payment received - {invoice_number}',
                'body' => '<p>Hi {name},</p><p>We received your payment of {currency} {amount}. Invoice: {invoice_number}.</p>',
                'vars' => ['name', 'amount', 'currency', 'invoice_number'],
            ],
            [
                'key' => 'access_granted',
                'name' => 'Category Access Granted',
                'subject' => 'Access unlocked: {category_name}',
                'body' => '<p>Hi {name},</p><p>You now have access to <strong>{category_name}</strong> until {expires_at}.</p>',
                'vars' => ['name', 'category_name', 'expires_at'],
            ],
            [
                'key' => 'access_expiry_reminder',
                'name' => 'Access Expiry Reminder',
                'subject' => 'Your access to {category_name} expires soon',
                'body' => '<p>Hi {name},</p><p>Your access to {category_name} expires on {expires_at}. Renew to keep watching.</p>',
                'vars' => ['name', 'category_name', 'expires_at'],
            ],
            [
                'key' => 'payment_failed',
                'name' => 'Payment Failed',
                'subject' => 'Payment failed',
                'body' => '<p>Hi {name},</p><p>Your payment could not be completed. Please try again.</p>',
                'vars' => ['name'],
            ],
            [
                'key' => 'support_ticket_reply',
                'name' => 'Support Ticket Reply',
                'subject' => 'Reply to your ticket {reference}',
                'body' => '<p>Hi {name},</p><p>Support replied to ticket {reference}:</p><blockquote>{message}</blockquote>',
                'vars' => ['name', 'reference', 'message'],
            ],
            [
                'key' => 'new_device_login',
                'name' => 'New Device Login',
                'subject' => 'New sign-in to your {site_name} account',
                'body' => '<p>Hi {name},</p><p>A new sign-in was detected from {device} ({ip}, {country}). If this was not you, secure your account.</p>',
                'vars' => ['name', 'device', 'ip', 'country', 'site_name'],
            ],
            [
                'key' => 'password_changed',
                'name' => 'Password Changed',
                'subject' => 'Your password was changed',
                'body' => '<p>Hi {name},</p><p>Your account password was just changed. If this was not you, contact support immediately.</p>',
                'vars' => ['name'],
            ],
            [
                'key' => 'coupon_offer',
                'name' => 'Coupon Offer',
                'subject' => 'A special offer just for you',
                'body' => '<p>Hi {name},</p><p>Use code <strong>{code}</strong> to get {discount} off.</p>',
                'vars' => ['name', 'code', 'discount'],
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(
                ['key' => $t['key']],
                [
                    'name' => $t['name'],
                    'subject' => $t['subject'],
                    'body_html' => $t['body'],
                    'available_variables' => $t['vars'],
                    'channel' => 'email',
                    'is_active' => true,
                ],
            );
        }
    }
}
