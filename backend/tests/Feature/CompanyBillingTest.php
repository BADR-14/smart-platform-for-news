<?php

namespace Tests\Feature;

use App\Models\CompanyPlan;
use App\Models\FeaturedPost;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Post;
use App\Services\StripeService;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

/**
 * لوحة الشركة: المحفظة، الباقات، والمنشورات المميزة
 */
class CompanyBillingTest extends TestCase
{
    private function fakeStripe(string $status = 'succeeded'): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) use ($status) {
            $mock->shouldReceive('publishableKey')->andReturn('pk_test_123');
            $mock->shouldReceive('createPaymentIntent')->andReturn(
                PaymentIntent::constructFrom(['id' => 'pi_test_1', 'client_secret' => 'secret_1'])
            );
            $mock->shouldReceive('retrievePaymentIntent')->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_test_1',
                'status' => $status,
                'latest_charge' => ['receipt_url' => 'https://stripe.test/receipt'],
            ]));
        });
    }

    // ==================== الصلاحيات ====================

    public function test_company_area_is_restricted_to_company_accounts(): void
    {
        $this->actingAsAccount($this->verifiedUser())->getJson('/api/company/wallet')->assertStatus(403);
        $this->actingAsAccount($this->admin())->getJson('/api/company/wallet')->assertStatus(403);
        $this->asGuest()->getJson('/api/company/wallet')->assertStatus(401);
    }

    // ==================== المحفظة ====================

    public function test_company_can_view_wallet_and_dashboard(): void
    {
        $company = $this->company(balance: 120);
        Post::factory()->count(2)->create(['account_id' => $company->account_id]);
        Post::factory()->draft()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($company->account)->getJson('/api/company/wallet')
            ->assertOk()
            ->assertJsonPath('data.wallet.balance', 120)
            ->assertJsonPath('data.wallet.currency', 'USD');

        $this->actingAsAccount($company->account)->getJson('/api/company/dashboard')
            ->assertOk()
            ->assertJsonPath('data.posts.total', 3)
            ->assertJsonPath('data.posts.published', 2)
            ->assertJsonPath('data.posts.draft', 1)
            ->assertJsonPath('data.wallet.balance', 120)
            ->assertJsonPath('data.active_plan', null);
    }

    public function test_top_up_is_credited_only_after_stripe_confirms_and_only_once(): void
    {
        $this->fakeStripe();
        $company = $this->company();

        $this->actingAsAccount($company->account)->postJson('/api/company/wallet/topup', ['amount' => 50])
            ->assertCreated()
            ->assertJsonPath('data.client_secret', 'secret_1')
            ->assertJsonPath('data.publishable_key', 'pk_test_123');

        $this->assertSame('0.00', $company->wallet->fresh()->balance, 'no credit before payment succeeds');
        $this->assertDatabaseHas('payments', ['gateway_payment_id' => 'pi_test_1', 'status' => 'pending']);

        $confirm = fn () => $this->actingAsAccount($company->account)
            ->postJson('/api/company/wallet/topup/confirm', ['payment_intent_id' => 'pi_test_1']);

        $confirm()->assertOk()->assertJsonPath('data.balance', 50);
        $confirm()->assertOk()->assertJsonPath('data.balance', 50);

        $this->assertSame(1, $company->wallet->transactions()->count());
        $this->assertSame('completed', Payment::first()->status);

        $this->actingAsAccount($company->account)->getJson('/api/company/wallet/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'credit')
            ->assertJsonPath('data.0.kind', 'topup');
    }

    public function test_top_up_is_not_credited_when_payment_did_not_succeed(): void
    {
        $this->fakeStripe(status: 'requires_payment_method');
        $company = $this->company();

        $this->actingAsAccount($company->account)->postJson('/api/company/wallet/topup', ['amount' => 50])->assertCreated();

        $this->actingAsAccount($company->account)
            ->postJson('/api/company/wallet/topup/confirm', ['payment_intent_id' => 'pi_test_1'])
            ->assertStatus(422)->assertJsonPath('code', 'payment_not_completed');

        $this->assertSame('0.00', $company->wallet->fresh()->balance);
    }

    public function test_company_cannot_confirm_another_companys_payment(): void
    {
        $this->fakeStripe();
        $owner = $this->company();
        $intruder = $this->company();

        $this->actingAsAccount($owner->account)->postJson('/api/company/wallet/topup', ['amount' => 50])->assertCreated();

        $this->actingAsAccount($intruder->account)
            ->postJson('/api/company/wallet/topup/confirm', ['payment_intent_id' => 'pi_test_1'])
            ->assertStatus(404);

        $this->assertSame('0.00', $intruder->wallet->fresh()->balance);
    }

    public function test_top_up_amount_is_validated(): void
    {
        $this->actingAsAccount($this->company()->account)
            ->postJson('/api/company/wallet/topup', ['amount' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_stripe_webhook_rejects_unsigned_requests(): void
    {
        $this->postJson('/api/webhooks/stripe', ['type' => 'payment_intent.succeeded'])->assertStatus(400);
    }

    public function test_withdraw_reduces_balance_and_cannot_exceed_it(): void
    {
        $company = $this->company(balance: 30);

        $this->actingAsAccount($company->account)->postJson('/api/company/wallet/withdraw', ['amount' => 20])
            ->assertOk()
            ->assertJsonPath('data.wallet.balance', 10)
            ->assertJsonPath('data.transaction.type', 'debit')
            ->assertJsonPath('data.transaction.kind', 'withdrawal');

        $this->actingAsAccount($company->account)->postJson('/api/company/wallet/withdraw', ['amount' => 20])
            ->assertStatus(422)->assertJsonPath('code', 'insufficient_balance');

        $this->assertSame('10.00', $company->wallet->fresh()->balance);
    }

    // ==================== الباقات ====================

    public function test_company_can_subscribe_to_a_plan_from_the_wallet(): void
    {
        $company = $this->company(balance: 100);
        $plan = Plan::factory()->create(['posts_count' => 5, 'price' => 40]);
        Plan::factory()->create(['is_active' => false]);

        $this->actingAsAccount($company->account)->getJson('/api/company/plans')
            ->assertOk()
            ->assertJsonCount(1, 'data.plans')
            ->assertJsonPath('data.plans.0.price_per_post', 8)
            ->assertJsonPath('data.single_post_price', 10)
            ->assertJsonPath('data.active_plan', null);

        $this->actingAsAccount($company->account)->postJson("/api/company/plans/{$plan->id}/subscribe")
            ->assertCreated()
            ->assertJsonPath('data.posts_remaining', 5)
            ->assertJsonPath('data.status', 'active');

        $this->assertSame('60.00', $company->wallet->fresh()->balance);

        // لا باقة ثانية ما دام في الحالية رصيد
        $this->actingAsAccount($company->account)->postJson("/api/company/plans/{$plan->id}/subscribe")
            ->assertStatus(422)->assertJsonPath('code', 'active_plan_exists');

        $this->actingAsAccount($company->account)->getJson('/api/company/subscriptions')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_subscription_fails_without_enough_balance_or_for_inactive_plan(): void
    {
        $company = $this->company(balance: 10);
        $plan = Plan::factory()->create(['price' => 40]);
        $inactive = Plan::factory()->create(['is_active' => false]);

        $this->actingAsAccount($company->account)->postJson("/api/company/plans/{$plan->id}/subscribe")
            ->assertStatus(422)->assertJsonPath('code', 'insufficient_balance');

        $this->actingAsAccount($company->account)->postJson("/api/company/plans/{$inactive->id}/subscribe")
            ->assertStatus(422)->assertJsonPath('code', 'plan_inactive');

        $this->assertDatabaseCount('company_plans', 0);
        $this->assertSame('10.00', $company->wallet->fresh()->balance);
    }

    public function test_company_can_buy_a_new_plan_after_the_previous_one_is_used_up(): void
    {
        $company = $this->company(balance: 100);
        $plan = Plan::factory()->create(['posts_count' => 5, 'price' => 40]);
        CompanyPlan::factory()->expired()->create(['company_id' => $company->id, 'plan_id' => $plan->id]);

        $this->actingAsAccount($company->account)->postJson("/api/company/plans/{$plan->id}/subscribe")->assertCreated();

        $this->assertSame(2, $company->plans()->count());
    }

    // ==================== المنشورات المميزة ====================

    public function test_featuring_a_post_from_the_wallet_charges_the_single_post_price(): void
    {
        $company = $this->company(balance: 25);
        $post = Post::factory()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($company->account)->getJson('/api/company/featured/options')
            ->assertOk()
            ->assertJsonPath('data.price', 10)
            ->assertJsonPath('data.duration_days', 7)
            ->assertJsonPath('data.wallet_balance', 25);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$post->id}/feature", ['payment_method' => 'wallet'])
            ->assertCreated()
            ->assertJsonPath('data.cost', 10)
            ->assertJsonPath('data.payment_method', 'wallet')
            ->assertJsonPath('data.status', 'active');

        $this->assertSame('15.00', $company->wallet->fresh()->balance);

        $featured = FeaturedPost::firstOrFail();
        $this->assertEqualsWithDelta(7, now()->diffInDays($featured->ends_at), 0.01);

        // يظهر أعلى الصفحة الرئيسية للجميع
        $this->asGuest()->getJson('/api/posts/featured')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $post->id);

        // لا يُميَّز (ولا يُدفع عنه) مرتين
        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$post->id}/feature", ['payment_method' => 'wallet'])
            ->assertStatus(422)->assertJsonPath('code', 'already_featured');

        $this->assertSame('15.00', $company->wallet->fresh()->balance);
        $this->actingAsAccount($company->account)->getJson('/api/company/featured')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_featuring_a_post_from_the_plan_uses_one_plan_post(): void
    {
        $company = $this->company(balance: 5);
        $plan = Plan::factory()->create(['posts_count' => 5, 'price' => 40]);
        $companyPlan = CompanyPlan::factory()->create([
            'company_id' => $company->id, 'plan_id' => $plan->id, 'posts_remaining' => 1, 'price_paid' => 40,
        ]);
        $first = Post::factory()->create(['account_id' => $company->account_id]);
        $second = Post::factory()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$first->id}/feature", ['payment_method' => 'plan'])
            ->assertCreated()
            ->assertJsonPath('data.payment_method', 'plan')
            ->assertJsonPath('data.cost', 8);

        $companyPlan->refresh();
        $this->assertSame(0, $companyPlan->posts_remaining);
        $this->assertSame('expired', $companyPlan->status);
        $this->assertSame('5.00', $company->wallet->fresh()->balance, 'wallet is not charged when paying from the plan');

        // الباقة انتهت
        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$second->id}/feature", ['payment_method' => 'plan'])
            ->assertStatus(422)->assertJsonPath('code', 'no_active_plan');
    }

    public function test_featuring_fails_cleanly_without_enough_balance(): void
    {
        $company = $this->company(balance: 3);
        $post = Post::factory()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$post->id}/feature", ['payment_method' => 'wallet'])
            ->assertStatus(422)->assertJsonPath('code', 'insufficient_balance');

        $this->assertDatabaseCount('featured_posts', 0);
    }

    public function test_company_can_only_feature_its_own_published_posts(): void
    {
        $company = $this->company(balance: 50);
        $foreign = Post::factory()->create();
        $draft = Post::factory()->draft()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$foreign->id}/feature", ['payment_method' => 'wallet'])
            ->assertStatus(403);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$draft->id}/feature", ['payment_method' => 'wallet'])
            ->assertStatus(422)->assertJsonPath('code', 'post_not_published');

        $this->assertSame('50.00', $company->wallet->fresh()->balance);
    }

    public function test_admin_controls_featured_price_and_duration(): void
    {
        $company = $this->company(balance: 100);
        $post = Post::factory()->create(['account_id' => $company->account_id]);

        $this->actingAsAccount($this->admin())
            ->patchJson('/api/admin/platform-settings', ['featured_post_price' => 25, 'featured_post_duration_days' => 3])
            ->assertOk()
            ->assertJsonPath('data.featured_post_price', 25)
            ->assertJsonPath('data.featured_post_duration_days', 3);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$post->id}/feature", ['payment_method' => 'wallet'])
            ->assertCreated()->assertJsonPath('data.cost', 25);

        $this->assertSame('75.00', $company->wallet->fresh()->balance);
        $this->assertEqualsWithDelta(3, now()->diffInDays(FeaturedPost::firstOrFail()->ends_at), 0.01);
    }

    public function test_expire_command_closes_ended_features_and_allows_featuring_again(): void
    {
        $company = $this->company(balance: 50);
        $post = Post::factory()->create(['account_id' => $company->account_id]);

        $featured = FeaturedPost::create([
            'post_id' => $post->id, 'company_id' => $company->id, 'cost' => 10, 'status' => 'active',
            'starts_at' => now()->subDays(8), 'ends_at' => now()->subDay(),
        ]);

        Artisan::call('featured:expire');

        $this->assertSame('expired', $featured->fresh()->status);

        $this->actingAsAccount($company->account)
            ->postJson("/api/company/posts/{$post->id}/feature", ['payment_method' => 'wallet'])
            ->assertCreated();
    }

    // ==================== ملف الشركة ====================

    public function test_company_can_update_its_profile(): void
    {
        $company = $this->company(['name' => 'Old Name']);

        $this->actingAsAccount($company->account)
            ->postJson('/api/company/profile', ['name' => 'New Name', 'description' => 'وصف جديد'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.description', 'وصف جديد');

        // اسم الحساب (الظاهر على المنشورات) يتبع اسم الشركة
        $this->assertSame('New Name', $company->account->fresh()->name);
    }
}
