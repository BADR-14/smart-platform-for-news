<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_user_can_view_own_profile(): void
    {
        $account = $this->verifiedUser(['name' => 'Layla']);
        Post::factory()->count(2)->create(['account_id' => $account->id]);
        Post::factory()->draft()->create(['account_id' => $account->id]);

        $this->actingAsAccount($account)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Layla')
            ->assertJsonPath('data.email', $account->email)
            ->assertJsonPath('data.type', 'user')
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.posts_count', 2);
    }

    public function test_user_can_update_profile(): void
    {
        $account = $this->user();

        $this->actingAsAccount($account)->patchJson('/api/profile', [
            'name' => 'New Name',
            'short_bio' => 'Flutter developer',
            'phone_number' => '+963912345678',
            'linkedin_url' => 'https://linkedin.com/in/someone',
        ])->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.short_bio', 'Flutter developer');

        $this->assertSame('New Name', $account->fresh()->name);
        $this->assertSame('+963912345678', $account->user->fresh()->phone_number);
    }

    public function test_profile_update_validates_input(): void
    {
        $this->actingAsAccount($this->user())
            ->patchJson('/api/profile', ['linkedin_url' => 'not-a-url', 'phone_number' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['linkedin_url', 'phone_number']);
    }

    public function test_user_can_upload_avatar(): void
    {
        $account = $this->user();

        $response = $this->actingAsAccount($account)->postJson('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ])->assertOk();

        $this->assertNotNull($response->json('data.avatar'));
        Storage::disk('public')->assertExists($account->fresh()->avatar);
    }

    public function test_user_can_change_password(): void
    {
        $account = $this->user(['email' => 'user@example.com']);

        $this->actingAsAccount($account)->postJson('/api/settings/password', [
            'current_password' => 'password',
            'new_password' => 'brandnew123',
            'new_password_confirmation' => 'brandnew123',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'brandnew123'])->assertOk();
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $this->actingAsAccount($this->user())->postJson('/api/settings/password', [
            'current_password' => 'wrong-one',
            'new_password' => 'brandnew123',
            'new_password_confirmation' => 'brandnew123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    public function test_user_can_delete_own_account(): void
    {
        $account = $this->verifiedUser(['email' => 'user@example.com']);
        $post = Post::factory()->create(['account_id' => $account->id]);

        $this->actingAsAccount($account)
            ->deleteJson('/api/settings/account', ['password' => 'wrong'])
            ->assertStatus(422);

        $this->actingAsAccount($account)
            ->deleteJson('/api/settings/account', ['password' => 'password'])
            ->assertOk();

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
        $this->assertSame('archived', $post->fresh()->status);

        // البريد يتحرر ليُستخدم في تسجيل جديد
        $this->postJson('/api/register', [
            'name' => 'Again',
            'email' => 'user@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();
    }

    public function test_public_profile_hides_private_fields(): void
    {
        $account = $this->verifiedUser();
        $account->user->update(['phone_number' => '+963912345678', 'national_id' => '12345678901']);

        $response = $this->getJson("/api/accounts/{$account->id}")
            ->assertOk()
            ->assertJsonPath('data.name', $account->name)
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.phone_number')
            ->assertJsonMissingPath('data.national_id');

        $this->assertStringNotContainsString('12345678901', $response->getContent());
    }

    public function test_admin_profile_is_not_public(): void
    {
        $this->getJson('/api/accounts/'.$this->admin()->id)->assertStatus(404);
    }
}
