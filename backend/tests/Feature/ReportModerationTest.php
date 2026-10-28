<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Report;
use Tests\TestCase;

class ReportModerationTest extends TestCase
{
    // ==================== إرسال البلاغ ====================

    public function test_user_can_report_a_post_and_admins_are_notified(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Suspicious post']);
        $reporter = $this->user();

        $this->actingAsAccount($reporter)->postJson('/api/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'محتوى مضلل',
        ])->assertCreated();

        $this->assertDatabaseHas('reports', [
            'reporter_account_id' => $reporter->id, 'reportable_type' => 'post', 'reportable_id' => $post->id, 'status' => 'pending',
        ]);

        $notification = $admin->notifications()->first();
        $this->assertSame('report_created', $notification->data['type']);
        $this->assertStringContainsString('Suspicious post', $notification->data['body']);
    }

    public function test_user_can_report_an_account(): void
    {
        $target = $this->user();

        $this->actingAsAccount($this->user())->postJson('/api/reports', [
            'reportable_type' => 'account',
            'reportable_id' => $target->id,
            'reason' => 'تعليقات مسيئة',
        ])->assertCreated();

        $this->assertDatabaseHas('reports', ['reportable_type' => 'account', 'reportable_id' => $target->id]);
    }

    public function test_report_rules(): void
    {
        $reporter = $this->verifiedUser();
        $post = Post::factory()->create();
        $own = Post::factory()->create(['account_id' => $reporter->id]);
        $payload = fn (string $type, int $id) => ['reportable_type' => $type, 'reportable_id' => $id, 'reason' => 'سبب واضح'];

        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('post', $post->id))->assertCreated();

        // لا تكرار، ولا بلاغ على النفس، ولا على هدف غير موجود
        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('post', $post->id))
            ->assertStatus(409)->assertJsonPath('code', 'already_reported');
        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('post', $own->id))->assertStatus(422);
        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('account', $reporter->id))->assertStatus(422);
        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('post', 99999))->assertStatus(404);
        $this->actingAsAccount($reporter)->postJson('/api/reports', $payload('account', $this->admin()->id))->assertStatus(404);
        $this->actingAsAccount($reporter)->postJson('/api/reports', ['reportable_type' => 'comment', 'reportable_id' => 1])
            ->assertStatus(422)->assertJsonValidationErrors(['reportable_type', 'reason']);
    }

    // ==================== معالجة الأدمن ====================

    private function report(string $type, int $id): Report
    {
        return Report::create([
            'reporter_account_id' => $this->user()->id,
            'reportable_type' => $type,
            'reportable_id' => $id,
            'reason' => 'مخالفة',
        ]);
    }

    public function test_admin_lists_reports_with_filters_and_stats(): void
    {
        $this->report('post', Post::factory()->create(['title' => 'Reported title'])->id);
        $this->report('account', $this->user(['name' => 'Reported user'])->id);

        $this->actingAsAccount($this->admin())->getJson('/api/admin/reports')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('stats.pending', 2)
            ->assertJsonPath('stats.posts', 1)
            ->assertJsonPath('stats.accounts', 1);

        $this->getJson('/api/admin/reports?type=post')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reported_item', 'Reported title')
            ->assertJsonPath('data.0.available_actions', ['none', 'under_review', 'reject_post', 'delete_post']);

        $this->getJson('/api/admin/reports?type=account')
            ->assertJsonPath('data.0.reported_item', 'Reported user')
            ->assertJsonPath('data.0.available_actions', ['none', 'ban_account']);
    }

    public function test_resolving_a_post_report_can_put_the_post_under_review(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        $report = $this->report('post', $post->id);
        $duplicate = $this->report('post', $post->id);

        $this->actingAsAccount($admin)->patchJson("/api/admin/reports/{$report->id}/resolve", [
            'action' => 'under_review',
            'admin_notes' => 'يحتاج تدقيق المصدر',
        ])->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.action_taken', 'under_review')
            ->assertJsonPath('data.reported_item_status', 'under_review');

        $post->refresh();
        $this->assertSame('under_review', $post->status);
        $this->assertSame('يحتاج تدقيق المصدر', $post->moderation_note);

        // يختفي من الصفحة الرئيسية، وصاحبه يُبلَّغ ويرى الملاحظة
        $this->asGuest()->getJson('/api/posts')->assertJsonCount(0, 'data');
        $this->assertSame('post_moderated', $post->account->notifications()->first()->data['type']);
        $this->actingAsAccount($post->account)->getJson("/api/posts/{$post->id}")
            ->assertOk()->assertJsonPath('data.moderation_note', 'يحتاج تدقيق المصدر');

        // البلاغ المكرر على نفس المنشور يُغلق بنفس القرار
        $this->assertSame('resolved', $duplicate->fresh()->status);

        // الأدمن يعيد نشره بعد المراجعة
        $this->actingAsAccount($admin)->patchJson("/api/admin/posts/{$post->id}/status", ['status' => 'published'])->assertOk();
        $this->assertSame('published', $post->fresh()->status);
        $this->assertNull($post->fresh()->moderation_note);
    }

    public function test_resolving_a_post_report_can_reject_or_delete_the_post(): void
    {
        $admin = $this->admin();
        $rejected = Post::factory()->create();
        $deleted = Post::factory()->create();

        $this->actingAsAccount($admin)
            ->patchJson('/api/admin/reports/'.$this->report('post', $rejected->id)->id.'/resolve', ['action' => 'reject_post'])
            ->assertOk();
        $this->actingAsAccount($admin)
            ->patchJson('/api/admin/reports/'.$this->report('post', $deleted->id)->id.'/resolve', ['action' => 'delete_post'])
            ->assertOk()->assertJsonPath('data.reported_item', 'محتوى محذوف');

        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSoftDeleted('posts', ['id' => $deleted->id]);
        $this->assertSame('post_deleted', $deleted->account->notifications()->first()->data['type']);
    }

    public function test_resolving_an_account_report_can_ban_the_account(): void
    {
        $target = $this->user();
        $target->createToken('session');
        $report = $this->report('account', $target->id);

        $this->actingAsAccount($this->admin())->patchJson("/api/admin/reports/{$report->id}/resolve", [
            'action' => 'ban_account',
            'admin_notes' => 'إساءة متكررة',
        ])->assertOk()->assertJsonPath('data.reported_item_status', 'blocked');

        $target->refresh();
        $this->assertFalse($target->is_active);
        $this->assertSame('إساءة متكررة', $target->ban_reason);
        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_owner_cannot_republish_a_post_that_is_under_review(): void
    {
        $post = Post::factory()->create(['status' => 'under_review', 'title' => 'Old title']);

        $this->actingAsAccount($post->account)
            ->patchJson("/api/posts/{$post->id}", ['title' => 'Fixed title', 'action' => 'publish'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Fixed title')
            ->assertJsonPath('data.status', 'under_review');
    }

    public function test_action_must_match_the_report_type(): void
    {
        $report = $this->report('account', $this->user()->id);

        $this->actingAsAccount($this->admin())
            ->patchJson("/api/admin/reports/{$report->id}/resolve", ['action' => 'delete_post'])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_report_action');

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_report_can_be_dismissed_and_not_handled_twice(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        $report = $this->report('post', $post->id);

        $this->actingAsAccount($admin)->patchJson("/api/admin/reports/{$report->id}/dismiss", ['admin_notes' => 'بلاغ غير مبرر'])
            ->assertOk()->assertJsonPath('data.status', 'dismissed');

        $this->assertSame('published', $post->fresh()->status);

        $this->actingAsAccount($admin)->patchJson("/api/admin/reports/{$report->id}/resolve", ['action' => 'delete_post'])
            ->assertStatus(422)->assertJsonPath('code', 'report_already_handled');
    }
}
