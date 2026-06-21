<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ----- General / Site -----
            ['general', 'site_name', 'Maal', 'string', true],
            ['general', 'site_tagline', 'Premium streaming, unlocked.', 'string', true],
            ['general', 'logo', '', 'string', true],
            ['general', 'favicon', '', 'string', true],
            ['general', 'support_email', 'support@your-domain.com', 'string', true],
            ['general', 'maintenance_mode', '0', 'boolean', true],
            ['general', 'maintenance_message', 'We will be back shortly.', 'text', true],

            // ----- Legal / Compliance -----
            ['legal', 'age_gate_enabled', '1', 'boolean', true],
            ['legal', 'min_age', '18', 'integer', true],
            ['legal', 'terms_url', '/terms', 'string', true],
            ['legal', 'privacy_url', '/privacy', 'string', true],
            ['legal', 'content_policy_url', '/content-policy', 'string', true],
            ['legal', 'refund_policy_url', '/refund-policy', 'string', true],
            ['legal', 'watch_history_enabled', '1', 'boolean', true],

            // ----- Preview / Streaming -----
            ['streaming', 'default_preview_seconds', '20', 'integer', true],
            ['streaming', 'autoplay_next', '1', 'boolean', true],

            // ----- Security / Devices -----
            ['security', 'default_device_limit', '2', 'integer', false],
            ['security', 'admin_2fa_required', '1', 'boolean', false],
            ['security', 'login_anomaly_alerts', '1', 'boolean', false],

            // ----- Watermark -----
            ['watermark', 'enabled', '0', 'boolean', false],
            ['watermark', 'text', '{email} • {datetime}', 'string', false],
            ['watermark', 'opacity', '0.35', 'string', false],
            ['watermark', 'font_size', '14', 'integer', false],
            ['watermark', 'move_interval', '8', 'integer', false],

            // ----- Payments -----
            ['payment', 'default_gateway', 'payu', 'string', false],
            ['payment', 'currency', 'INR', 'string', true],
            ['payment', 'tax_percent', '0', 'string', false],

            // ----- Upload / Storage -----
            ['upload', 'max_storage_quota_gb', '0', 'integer', false],
            ['upload', 'chunk_size_mb', '8', 'integer', false],
            ['upload', 'allowed_formats', 'mp4,mov,mkv,webm,avi', 'string', false],

            // ----- Support / WhatsApp -----
            ['support', 'whatsapp_enabled', '1', 'boolean', true],
            ['support', 'whatsapp_number', '', 'string', true],
            ['support', 'whatsapp_on_checkout_only', '0', 'boolean', true],

            // ----- SEO / Marketing -----
            ['seo', 'meta_title', 'Maal - Premium Streaming', 'string', true],
            ['seo', 'meta_description', 'Watch premium content with category-based access.', 'text', true],
            ['marketing', 'newsletter_popup_enabled', '0', 'boolean', true],
            ['marketing', 'newsletter_popup_delay', '15', 'integer', true],
        ];

        foreach ($settings as [$group, $key, $value, $type, $isPublic]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => $value, 'type' => $type, 'is_public' => $isPublic],
            );
        }
    }
}
