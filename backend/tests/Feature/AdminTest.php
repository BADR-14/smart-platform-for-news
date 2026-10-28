<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CompanyPlan;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Tag;
use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_admin_area_is_restricted_to_admins(): void
    {
        $this->getJson('/api/admin/overview')->assertStatus(401);
        $this->actingAsAccount($this->user())->getJson('/api/admin/overview')->assertStatus(403);
        $this->actingAsAccount($this->company()->account)->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_overview_and_analytics(): void
    {
        $this->user();
        $this->company();
        Post::factory()->create();
        Post::factory()->create(['status' => 'under_review']);

        $this->actingAsAccount($this->admin())->getJson('/api/admin/overview')
            ->assertOk()
            ->assertJsonPath('data.statistics.companies', 1)
            ->assertJsonPath('data.statistics.posts', 2)
            ->assertJsonPath('data.statistics.under_review_posts', 1)
            ->assertJsonCount(12, 'data.charts.news_growth');

        $analytics = $this->getJson('/api/admin/analytics')->assertOk();

        $analytics->assertJsonPath('data.counters.total_news', 2)
            ->assertJsonPath('data.monthly_reports.records.0.year', now()->year)
            ->assertJsonPath('data.monthly_reports.records.0.month_number', now()->month)
            ->assertJsonPath('data.monthly_reports.records.0.news_count', 2)
            ->assertJsonPath('data.available_years.0', now()->year);

        $this->assertSame(2, $analytics->json('data.charts.news_growth')[now()->month - 1]);

        $this->getJson('/api/admin/analytics?year=2001')->assertOk()->assertJsonCount(0, 'data.monthly_reports.records');
    }

    // ==================== المستخدمون ====================

    public function test_admin_manages_users(): void
    {
        $admin = $this->admin();
        $target = $this->user(['name' => 'Target User']);
        $target->createToken('session');
        $this->verifiedUser();
        $this->company();

        $this->actingAsAccount($admin)->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('stats.total', 2)
            ->assertJsonPath('stats.verified', 1);

        $this->getJson('/api/admin/users?search=Target')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/users?verification=approved')->assertJsonCount(1, 'data');

        $this->postJson("/api/admin/users/{$target->id}/ban", ['ban_reason' => 'spam'])->assertOk();

        $target->refresh();
        $this->assertFalse($target->is_active);
        $this->assertSame(0, $target->tokens()->count());
        $this->getJson('/api/admin/users?status=blocked')->assertJsonCount(1, 'data')->assertJsonPath('data.0.ban_reason', 'spam');

        $this->postJson("/api/admin/users/{$target->id}/ban", ['ban_reason' => 'again'])->assertStatus(422);
        $this->postJson("/api/admin/users/{$target->id}/unban")->assertOk();
        $this->assertTrue($target->fresh()->is_active);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertOk();
        $this->assertSoftDeleted('accounts', ['id' => $target->id]);
    }

    public function test_admin_accounts_cannot_be_banned_or_deleted(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAsAccount($admin)->postJson("/api/admin/users/{$other->id}/ban", ['ban_reason' => 'x'])->assertStatus(403);
        $this->actingAsAccount($admin)->deleteJson("/api/admin/users/{$admin->id}")->assertStatus(403);

        $this->assertTrue($other->fresh()->is_active);
    }

    // ==================== الشركات ====================

    public function test_admin_manages_companies(): void
    {
        $company = $this->company(['name' => 'Acme'], balance: 75);

        $this->actingAsAccount($this->admin())->getJson('/api/admin/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme')
            ->assertJsonPath('data.0.wallet_balance', 75)
            ->assertJsonPath('stats.total_companies', 1)
            ->assertJsonPath('stats.total_balance', 75);

        $this->postJson("/api/admin/companies/{$company->id}/ban", ['ban_reason' => 'fraud'])->assertOk();
        $this->assertFalse($company->account->fresh()->is_active);
        $this->getJson('/api/admin/companies?status=blocked')->assertJsonCount(1, 'data');

        // الشركة المحظورة تختفي من القائمة العامة
        $this->asGuest()->getJson('/api/companies')->assertJsonCount(0, 'data');

        $this->actingAsAccount(Account::where('account_type_id', 3)->first())
            ->postJson("/api/admin/companies/{$company->id}/unban")->assertOk();
    }

    // ==================== المنشورات ====================

    public function test_admin_manages_posts(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Managed post']);
        Post::factory()->draft()->create();

        $this->actingAsAccount($admin)->getJson('/api/admin/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('stats.total_news', 2)
            ->assertJsonPath('stats.published', 1);

        $this->getJson('/api/admin/posts?status=published&search=Managed')->assertJsonCount(1, 'data');

        $this->postJson("/api/admin/posts/{$post->id}/toggle-archive")->assertOk()->assertJsonPath('data.status', 'archived');
        $this->postJson("/api/admin/posts/{$post->id}/toggle-archive")->assertOk()->assertJsonPath('data.status', 'published');

        $this->patchJson("/api/admin/posts/{$post->id}/status", ['status' => 'rejected', 'note' => 'مخالف'])->assertOk();
        $this->assertSame('rejected', $post->fresh()->status);

        $this->patchJson("/api/admin/posts/{$post->id}/status", ['status' => 'draft'])->assertStatus(422);

        $this->deleteJson("/api/admin/posts/{$post->id}")->assertOk();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    // ==================== الباقات ====================

    public function test_admin_manages_plans(): void
    {
        $this->actingAsAccount($this->admin());

        $planId = $this->postJson('/api/admin/plans', ['name' => 'Basic', 'posts_count' => 5, 'price' => 40])
            ->assertCreated()
            ->assertJsonPath('data.price_per_post', 8)
            ->json('data.id');

        $this->putJson("/api/admin/plans/{$planId}", ['name' => 'Basic+', 'posts_count' => 10, 'price' => 70])
            ->assertOk()->assertJsonPath('data.name', 'Basic+');

        $this->patchJson("/api/admin/plans/{$planId}/toggle-status")->assertOk()->assertJsonPath('data.is_active', false);

        $this->getJson('/api/admin/plans')
            ->assertOk()
            ->assertJsonCount(1, 'data.plans')
            ->assertJsonPath('data.stats.active_plans', 0)
            ->assertJsonPath('data.settings.featured_post_price', 10)
            ->assertJsonPath('data.settings.featured_post_duration_days', 7);

        $this->postJson('/api/admin/plans', ['name' => '', 'posts_count' => 0, 'price' => -1])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'posts_count', 'price']);
    }

    public function test_plan_with_subscriptions_cannot_be_deleted(): void
    {
        $this->actingAsAccount($this->admin());

        $used = Plan::factory()->create();
        $unused = Plan::factory()->create();
        CompanyPlan::factory()->create(['plan_id' => $used->id, 'company_id' => $this->company(['name' => 'Subscriber'])->id]);

        $this->deleteJson("/api/admin/plans/{$used->id}")->assertStatus(422)->assertJsonPath('code', 'plan_has_subscriptions');
        $this->deleteJson("/api/admin/plans/{$unused->id}")->assertOk();

        $this->getJson('/api/admin/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Subscriber');

        $this->getJson('/api/admin/featured-posts')->assertOk()->assertJsonCount(0, 'data');
    }

    // ==================== التصنيفات والوسوم ====================

    public function test_admin_manages_categories_and_tags(): void
    {
        $this->actingAsAccount($this->admin());

        $categoryId = $this->postJson('/api/admin/categories', ['name' => 'تقنية', 'type' => 'both'])
            ->assertCreated()->json('data.id');

        $this->postJson('/api/admin/categories', ['name' => 'تقنية', 'type' => 'post'])
            ->assertStatus(422)->assertJsonValidationErrors('name');

        $this->putJson("/api/admin/categories/{$categoryId}", ['name' => 'تقنية المعلومات'])->assertOk();

        Post::factory()->create(['category_id' => $categoryId]);
        $this->deleteJson("/api/admin/categories/{$categoryId}")->assertStatus(422)->assertJsonPath('code', 'category_in_use');

        $empty = Category::factory()->create(['name' => 'فارغ']);
        $this->deleteJson("/api/admin/categories/{$empty->id}")->assertOk();
        // الاسم يتحرر بعد الحذف
        $this->postJson('/api/admin/categories', ['name' => 'فارغ', 'type' => 'post'])->assertCreated();

        $tag = Tag::create(['name' => 'flutter']);
        $this->getJson('/api/admin/tags')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/admin/tags/{$tag->id}")->assertOk();
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_public_category_and_tag_endpoints(): void
    {
        Category::factory()->create(['name' => 'Posts only', 'type' => 'post']);
        Category::factory()->create(['name' => 'Companies only', 'type' => 'company']);
        Category::factory()->create(['name' => 'Shared', 'type' => 'both']);
        Tag::create(['name' => 'flutter']);
        Tag::create(['name' => 'laravel']);

        $this->getJson('/api/categories')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/categories?type=post')->assertJsonCount(2, 'data');
        $this->getJson('/api/categories?type=company')->assertJsonCount(2, 'data');
        $this->getJson('/api/tags?search=flu')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/react-types')->assertOk()->assertJsonCount(6, 'data');
    }
}
