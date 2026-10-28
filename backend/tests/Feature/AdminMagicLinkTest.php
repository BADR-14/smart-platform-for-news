<?php

namespace Tests\Feature;

use App\Mail\AdminMagicLinkMail;
use App\Models\AdminLoginLink;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMagicLinkTest extends TestCase
{
    /**
     * يطلب رابطاً للأدمن ويرجع التوكن الخام المضمَّن في الرابط المرسَل بالبريد.
     */
    private function requestLink(string $email): ?string
    {
        Mail::fake();

        $this->postJson('/api/admin/auth/magic-link', ['email' => $email])->assertOk();

        $token = null;
        Mail::assertSent(AdminMagicLinkMail::class, function (AdminMagicLinkMail $mail) use (&$token) {
            parse_str((string) parse_url($mail->url, PHP_URL_QUERY), $query);
            $token = $query['token'] ?? null;

            return true;
        });

        return $token;
    }

    public function test_admin_receives_a_link_and_signs_in_with_it(): void
    {
        $admin = $this->admin();

        $token = $this->requestLink($admin->email);
        $this->assertSame(64, strlen((string) $token));

        // التوكن لا يُخزَّن كما هو: بصمته فقط
        $this->assertDatabaseMissing('admin_login_links', ['token_hash' => $token]);
        $this->assertDatabaseHas('admin_login_links', ['token_hash' => hash('sha256', $token)]);

        $this->postJson('/api/admin/auth/magic-link/verify', ['email' => $admin->email, 'token' => $token])
            ->assertOk()
            ->assertJsonPath('data.account.type', 'admin')
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    public function test_link_works_only_once(): void
    {
        $admin = $this->admin();
        $token = $this->requestLink($admin->email);

        $payload = ['email' => $admin->email, 'token' => $token];
        $this->postJson('/api/admin/auth/magic-link/verify', $payload)->assertOk();
        $this->postJson('/api/admin/auth/magic-link/verify', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_magic_link');
    }

    public function test_expired_link_is_rejected(): void
    {
        $admin = $this->admin();
        $token = $this->requestLink($admin->email);

        AdminLoginLink::query()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/admin/auth/magic-link/verify', ['email' => $admin->email, 'token' => $token])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_magic_link');
    }

    public function test_requesting_a_new_link_invalidates_the_previous_one(): void
    {
        $admin = $this->admin();
        $first = $this->requestLink($admin->email);
        $second = $this->requestLink($admin->email);

        $this->postJson('/api/admin/auth/magic-link/verify', ['email' => $admin->email, 'token' => $first])
            ->assertStatus(422);
        $this->postJson('/api/admin/auth/magic-link/verify', ['email' => $admin->email, 'token' => $second])
            ->assertOk();
    }

    public function test_non_admin_email_gets_the_same_response_but_no_link(): void
    {
        Mail::fake();
        $user = $this->user();

        $this->postJson('/api/admin/auth/magic-link', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);
        $this->postJson('/api/admin/auth/magic-link', ['email' => 'nobody@example.com'])->assertOk();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('admin_login_links', 0);
    }

    public function test_wrong_token_is_rejected(): void
    {
        $admin = $this->admin();
        $this->requestLink($admin->email);

        $this->postJson('/api/admin/auth/magic-link/verify', ['email' => $admin->email, 'token' => str_repeat('a', 64)])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_magic_link');
    }

    public function test_admin_cannot_sign_in_with_a_password(): void
    {
        $admin = $this->admin();

        // حتى بكلمة المرور الصحيحة: الرد نفسه كأن البيانات خاطئة
        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_credentials');
    }
}
