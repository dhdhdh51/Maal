<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(EmailTemplateSeeder::class);
    }

    public function test_registration_creates_unverified_user_and_issues_otp(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'age_confirm' => '1',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole(Permissions::ROLE_USER));
        $this->assertNotEmpty($user->referral_code);
        $this->assertDatabaseHas('otp_verifications', [
            'destination' => 'jane@example.com',
            'channel' => 'email',
            'purpose' => 'verify',
        ]);
    }

    public function test_email_verification_logs_user_in_and_tracks_device(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'age_confirm' => '1',
            'terms' => '1',
        ]);

        $code = cache()->get('otp_test_email_jane@example.com');
        $this->assertNotEmpty($code);

        $response = $this->post('/verify-email', ['code' => $code]);
        $response->assertRedirect(route('home'));

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_devices', ['user_id' => $user->id]);
        $this->assertDatabaseHas('user_sessions', ['user_id' => $user->id]);
    }

    public function test_unverified_user_cannot_complete_login(): void
    {
        User::factory()->unverified()->create(['email' => 'bob@example.com']);

        $response = $this->post('/login', [
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertGuest();
    }

    public function test_verified_user_can_login(): void
    {
        $user = User::factory()->create(['email' => 'ann@example.com']);
        $user->assignRole(Permissions::ROLE_USER);

        $response = $this->post('/login', [
            'email' => 'ann@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_via_otp(): void
    {
        $user = User::factory()->create(['email' => 'carl@example.com']);

        $this->post('/forgot-password', ['email' => 'carl@example.com'])
            ->assertRedirect(route('password.reset'));

        $code = cache()->get('otp_test_email_carl@example.com');
        $this->assertNotEmpty($code);

        $this->post('/reset-password', [
            'email' => 'carl@example.com',
            'code' => $code,
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_age_gate_sets_cookie(): void
    {
        $response = $this->post('/age-gate', [
            'confirm' => '1',
            'redirect' => route('home'),
        ]);

        $response->assertRedirect(route('home'));
        $response->assertCookie('age_confirmed', '1');
    }

    public function test_api_login_returns_token_for_verified_user(): void
    {
        $user = User::factory()->create(['email' => 'api@example.com']);
        $user->assignRole(Permissions::ROLE_USER);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['token', 'token_type', 'user', 'roles']]);
    }
}
