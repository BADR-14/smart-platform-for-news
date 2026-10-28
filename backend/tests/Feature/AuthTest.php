<?php

namespace Tests\Feature;

use App\Mail\SendOtpMail;
use App\Models\Account;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private function register(array $overrides = []): string
    {
        $otp = '';

        $this->postJson('/api/register', [
            'name' => 'Layla Haddad',
            'email' => 'layla@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            ...$overrides,
        ])->assertCreated();

        Mail::assertSent(SendOtpMail::class, function (SendOtpMail $mail) use (&$otp) {
            $otp = $mail->otpCode;

            return true;
        });

        return $otp;
    }

    public function test_register_does_not_leak_otp_or_issue_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Layla Haddad',
            'email' => 'layla@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()->assertExactJson([
            'message' => 'تم إنشاء الحساب. أرسلنا رمز التحقق إلى بريدك الإلكتروني.',
            'data' => ['email' => 'layla@example.com'],
        ]);

        $account = Account::where('email', 'layla@example.com')->firstOrFail();

        $this->assertNotNull($account->user, 'profile row is created with the account');
        $this->assertSame(0, $account->tokens()->count());
        $this->assertStringNotContainsString('otp', $response->getContent());
    }

    public function test_otp_is_stored_hashed(): void
    {
        $otp = $this->register();

        $stored = Account::where('email', 'layla@example.com')->value('otp_code');

        $this->assertNotSame($otp, $stored);
        $this->assertTrue(Hash::check($otp, $stored));
    }

    public function test_cannot_login_before_verifying_email(): void
    {
        $this->register();

        $this->postJson('/api/login', ['email' => 'layla@example.com', 'password' => 'secret123'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'email_unverified');
    }

    public function test_verifying_otp_returns_a_token_and_account(): void
    {
        $otp = $this->register();

        $response = $this->postJson('/api/verify-otp', ['email' => 'layla@example.com', 'otp_code' => $otp])
            ->assertOk()
            ->assertJsonPath('data.account.type', 'user')
            ->assertJsonPath('data.account.email_verified', true);

        $this->getJson('/api/me', ['Authorization' => 'Bearer '.$response->json('data.access_token')])
            ->assertOk()
            ->assertJsonPath('data.email', 'layla@example.com');
    }

    public function test_wrong_or_expired_otp_is_rejected(): void
    {
        $otp = $this->register();

        $this->postJson('/api/verify-otp', ['email' => 'layla@example.com', 'otp_code' => '000000'])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_otp');

        Account::where('email', 'layla@example.com')->update(['otp_expires_at' => now()->subMinute()]);

        $this->postJson('/api/verify-otp', ['email' => 'layla@example.com', 'otp_code' => $otp])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_otp');
    }

    public function test_otp_attempts_are_rate_limited(): void
    {
        $this->register();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/verify-otp', ['email' => 'layla@example.com', 'otp_code' => '000000'])->assertStatus(422);
        }

        $this->postJson('/api/verify-otp', ['email' => 'layla@example.com', 'otp_code' => '000000'])
            ->assertStatus(429);
    }

    public function test_login_with_valid_credentials(): void
    {
        $account = $this->user(['email' => 'user@example.com']);

        $this->postJson('/api/login', ['email' => 'User@Example.com', 'password' => 'password', 'fcm_token' => 'device-token'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'account' => ['id', 'name', 'type', 'is_verified']]])
            ->assertJsonMissingPath('data.account.fcm_token');

        $this->assertSame('device-token', $account->fresh()->fcm_token);
    }

    public function test_login_does_not_reveal_whether_the_email_exists(): void
    {
        $this->user(['email' => 'user@example.com']);

        $wrongPassword = $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'nope']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'ghost@example.com', 'password' => 'nope']);

        $wrongPassword->assertStatus(401);
        $unknownEmail->assertStatus(401);
        $this->assertSame($wrongPassword->json(), $unknownEmail->json());
    }

    public function test_banned_account_cannot_login(): void
    {
        Account::factory()->banned('spam')->create(['email' => 'banned@example.com']);

        $this->postJson('/api/login', ['email' => 'banned@example.com', 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned')
            ->assertJsonPath('reason', 'spam');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $account = $this->user();
        $first = $account->createToken('phone')->plainTextToken;
        $account->createToken('web');

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer {$first}"])->assertOk();

        $this->assertSame(1, $account->tokens()->count());
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }

    public function test_password_reset_with_otp_code(): void
    {
        $account = $this->user(['email' => 'user@example.com']);
        $account->createToken('old-session');

        $this->postJson('/api/password/otp', ['email' => 'user@example.com'])->assertOk();

        $code = '';
        Mail::assertSent(SendOtpMail::class, function (SendOtpMail $mail) use (&$code) {
            $code = $mail->otpCode;

            return $mail->purpose === SendOtpMail::PURPOSE_RESET_PASSWORD;
        });

        $this->postJson('/api/password/otp/verify', ['email' => 'user@example.com', 'otp_code' => $code])->assertOk();

        $this->postJson('/api/password/reset', [
            'email' => 'user@example.com',
            'otp_code' => $code,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        $this->assertSame(0, $account->tokens()->count(), 'old sessions are revoked');
        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'newpass123'])->assertOk();

        // الرمز يُستخدم مرة واحدة فقط
        $this->postJson('/api/password/reset', [
            'email' => 'user@example.com',
            'otp_code' => $code,
            'password' => 'another123',
            'password_confirmation' => 'another123',
        ])->assertStatus(422);
    }

    public function test_password_reset_with_emailed_link(): void
    {
        Notification::fake();
        $account = $this->user(['email' => 'user@example.com']);

        $this->postJson('/api/password/email', ['email' => 'user@example.com'])->assertOk();

        $token = '';
        Notification::assertSentTo($account, ResetPassword::class, function (ResetPassword $notification) use (&$token, $account) {
            $token = $notification->token;

            return str_contains($notification->toMail($account)->actionUrl, config('platform.frontend_url').'/reset-password');
        });

        $this->postJson('/api/password/reset', [
            'email' => 'user@example.com',
            'token' => $token,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'newpass123'])->assertOk();
    }

    public function test_password_reset_request_does_not_reveal_unknown_emails(): void
    {
        $this->postJson('/api/password/otp', ['email' => 'ghost@example.com'])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_google_login_creates_a_verified_account(): void
    {
        config(['services.google.allowed_client_ids' => ['our-client-id']]);

        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'our-client-id',
            'sub' => 'google-123',
            'email' => 'g.user@example.com',
            'email_verified' => 'true',
            'name' => 'Google User',
        ])]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('data.account.email', 'g.user@example.com')
            ->assertJsonPath('data.account.email_verified', true);

        $this->assertDatabaseHas('auth_credentials', ['provider' => 'google', 'provider_identifier' => 'google-123']);
    }

    public function test_google_login_rejects_tokens_issued_for_another_app(): void
    {
        config(['services.google.allowed_client_ids' => ['our-client-id']]);

        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'someone-else',
            'sub' => 'google-123',
            'email' => 'g.user@example.com',
            'email_verified' => 'true',
        ])]);

        $this->postJson('/api/auth/google', ['id_token' => 'foreign-token'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_google_token');

        $this->assertDatabaseMissing('accounts', ['email' => 'g.user@example.com']);
    }

    public function test_banned_account_cannot_login_with_google(): void
    {
        config(['services.google.allowed_client_ids' => ['our-client-id']]);
        Account::factory()->banned()->create(['email' => 'g.user@example.com']);

        Http::fake(['oauth2.googleapis.com/*' => Http::response([
            'aud' => 'our-client-id', 'sub' => 'google-123', 'email' => 'g.user@example.com', 'email_verified' => 'true',
        ])]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_banned');
    }
}
