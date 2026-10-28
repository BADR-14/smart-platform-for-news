<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Post;
use App\Services\External\FcmService;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    private function accountWithNotifications(): Account
    {
        $owner = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $owner->id]);

        foreach (range(1, 3) as $i) {
            $this->actingAsAccount($this->user())
                ->postJson("/api/posts/{$post->id}/comments", ['content' => "comment {$i}"])
                ->assertCreated();
        }

        return $owner;
    }

    public function test_notifications_are_listed_with_unread_count(): void
    {
        $owner = $this->accountWithNotifications();

        $this->actingAsAccount($owner)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('unread_count', 3)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.type', 'post_commented')
            ->assertJsonPath('data.0.is_read', false)
            ->assertJsonStructure(['data' => [['id', 'type', 'title', 'body', 'meta' => ['post_id'], 'created_at', 'time_ago']]]);

        $this->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('data.unread_count', 3);
    }

    public function test_notifications_can_be_read_and_deleted(): void
    {
        $owner = $this->accountWithNotifications();
        $ids = $owner->notifications()->pluck('id');

        $this->actingAsAccount($owner)->patchJson("/api/notifications/{$ids[0]}/read")->assertOk();
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread_count', 2);
        $this->getJson('/api/notifications?unread_only=1')->assertJsonCount(2, 'data');

        $this->deleteJson("/api/notifications/{$ids[1]}")->assertOk();
        $this->getJson('/api/notifications')->assertJsonCount(2, 'data');

        $this->patchJson('/api/notifications/read-all')->assertOk();
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.unread_count', 0);
    }

    public function test_user_cannot_touch_someone_elses_notification(): void
    {
        $owner = $this->accountWithNotifications();
        $id = $owner->notifications()->value('id');

        $this->actingAsAccount($this->user())->patchJson("/api/notifications/{$id}/read")->assertStatus(404);
        $this->actingAsAccount($this->user())->deleteJson("/api/notifications/{$id}")->assertStatus(404);
    }

    public function test_notification_preferences_require_login_and_control_login_notifications(): void
    {
        $this->getJson('/api/notification-settings')->assertStatus(401);

        $account = $this->user(['email' => 'user@example.com']);

        $this->actingAsAccount($account)->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.email_notifications', true)
            ->assertJsonPath('data.login_notifications', true);

        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'password'])->assertOk();
        $this->assertSame(1, $account->notifications()->count());

        $this->actingAsAccount($account)->patchJson('/api/notification-settings', [
            'email_notifications' => false,
            'login_notifications' => false,
        ])->assertOk()->assertJsonPath('data.login_notifications', false);

        $this->postJson('/api/login', ['email' => 'user@example.com', 'password' => 'password'])->assertOk();
        $this->assertSame(1, $account->notifications()->count(), 'no login notification after opting out');
    }

    public function test_notifications_are_pushed_to_the_device_when_a_token_exists(): void
    {
        $owner = $this->verifiedUser();
        $owner->forceFill(['fcm_token' => 'device-token'])->save();
        $post = Post::factory()->create(['account_id' => $owner->id]);

        $this->mock(FcmService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (string $token, string $title, string $body, array $data) => $token === 'device-token'
                    && $title === 'تفاعل جديد على خبرك'
                    && $data['type'] === 'post_reacted');
        });

        $this->actingAsAccount($this->user())->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 1])->assertOk();
    }

    public function test_own_actions_do_not_notify_yourself(): void
    {
        $owner = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $owner->id]);

        $this->actingAsAccount($owner)->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 1])->assertOk();
        $this->actingAsAccount($owner)->postJson("/api/posts/{$post->id}/comments", ['content' => 'my own'])->assertCreated();

        $this->assertSame(0, $owner->notifications()->count());
    }
}
