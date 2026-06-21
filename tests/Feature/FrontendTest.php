<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_legal_pages_render(): void
    {
        foreach (['terms', 'privacy', 'content-policy', 'refund-policy', 'dmca'] as $page) {
            $this->get("/{$page}")->assertOk();
        }
    }

    public function test_unknown_page_404s(): void
    {
        $this->get('/not-a-real-page')->assertNotFound();
    }

    public function test_sitemap_is_xml(): void
    {
        $res = $this->get('/sitemap.xml');
        $res->assertOk();
        $this->assertStringContainsString('application/xml', $res->headers->get('Content-Type'));
        $res->assertSee('<urlset', false);
    }

    public function test_robots_txt(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
    }

    public function test_contact_form_submits_for_guest(): void
    {
        $this->post('/contact', [
            'name' => 'Guest', 'email' => 'g@example.com', 'message' => 'Hello there team.',
        ])->assertRedirect();
    }

    public function test_contact_form_creates_ticket_for_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/contact', [
            'name' => $user->name, 'email' => $user->email, 'message' => 'Need help please.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['user_id' => $user->id, 'subject' => 'Contact form']);
    }
}
