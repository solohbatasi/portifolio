<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('admin.allowed_emails', ['solomonbatasi@gmail.com', 'batasisolomon029@gmail.com']);
    }

    public function test_guests_cannot_access_admin_and_login_redirects_to_google(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->once()->with(['openid', 'email', 'profile'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.test'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $this->get('/auth/google/redirect')->assertRedirect('https://accounts.google.test');
    }

    public function test_both_allowed_accounts_can_login_case_insensitively_without_tokens(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->twice()->andReturn(
            GoogleUser::fake(['id' => 'google-1', 'name' => 'Solomon Batasi', 'email' => 'SOLOMONBATASI@GMAIL.COM', 'email_verified' => true]),
            GoogleUser::fake(['id' => 'google-2', 'name' => 'Solomon Batasi', 'email' => 'batasisolomon029@gmail.com', 'email_verified' => true]),
        );
        Socialite::shouldReceive('driver')->twice()->with('google')->andReturn($provider);
        foreach (['SOLOMONBATASI@GMAIL.COM', 'batasisolomon029@gmail.com'] as $email) {
            $this->get('/auth/google/callback')->assertRedirect('/admin');
            $this->assertAuthenticated();
            $user = User::where('email', strtolower($email))->firstOrFail();
            $this->assertArrayNotHasKey('token', $user->getAttributes());
            auth()->logout();
        }
    }

    public function test_unverified_or_unallowed_accounts_are_rejected_without_user_creation(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->twice()->andReturn(
            GoogleUser::fake(['id' => 'denied-1', 'email' => 'outsider@example.com', 'email_verified' => true]),
            GoogleUser::fake(['id' => 'denied-2', 'email' => 'solomonbatasi@gmail.com', 'email_verified' => false]),
        );
        Socialite::shouldReceive('driver')->twice()->with('google')->andReturn($provider);
        foreach ([['outsider@example.com', true], ['solomonbatasi@gmail.com', false]] as [$email, $verified]) {
            $this->get('/auth/google/callback')->assertRedirect('/admin/access-denied');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_removed_allowlisted_user_loses_access_and_logout_is_post_only(): void
    {
        $user = User::create(['name' => 'Solomon', 'email' => 'solomonbatasi@gmail.com', 'is_active' => true]);
        config()->set('admin.allowed_emails', []);
        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/access-denied');
        $this->get('/admin/logout')->assertMethodNotAllowed();
    }
}
