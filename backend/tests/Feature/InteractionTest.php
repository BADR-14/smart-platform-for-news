<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Company;
use App\Models\Follow;
use App\Models\Post;
use App\Models\PostShare;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    // ==================== التفاعلات ====================

    public function test_react_toggle_creates_updates_and_removes(): void
    {
        $post = Post::factory()->create();
        $account = $this->user();

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 1])
            ->assertOk()
            ->assertJsonPath('data.action', 'created')
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.my_reaction', 1);

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 2])
            ->assertJsonPath('data.action', 'updated')
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.my_reaction', 2);

        $this->actingAsAccount($account)->getJson("/api/posts/{$post->id}")->assertJsonPath('data.my_reaction', 2);

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 2])
            ->assertJsonPath('data.action', 'deleted')
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.my_reaction', null);

        // إعادة التفاعل بعد الإلغاء تعمل (القيد الفريد لا يمنعها)
        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 3])
            ->assertJsonPath('data.action', 'created');
    }

    public function test_reacting_notifies_the_post_owner(): void
    {
        $post = Post::factory()->create();

        $this->actingAsAccount($this->user())->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 1])->assertOk();

        $this->assertSame(1, $post->account->notifications()->count());
        $this->assertSame('post_reacted', $post->account->notifications()->first()->data['type']);
    }

    public function test_react_type_must_exist(): void
    {
        $post = Post::factory()->create();

        $this->actingAsAccount($this->user())
            ->postJson("/api/posts/{$post->id}/react", ['react_type_id' => 999])
            ->assertStatus(422);
    }

    // ==================== الحفظ ====================

    public function test_bookmark_toggle_and_listing(): void
    {
        $post = Post::factory()->create();
        $account = $this->user();

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/bookmark")
            ->assertOk()->assertJsonPath('data.is_bookmarked', true);

        $this->actingAsAccount($account)->getJson('/api/profile/bookmarks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.is_bookmarked', true);

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/bookmark")
            ->assertJsonPath('data.is_bookmarked', false);

        $this->actingAsAccount($account)->getJson('/api/profile/bookmarks')->assertJsonCount(0, 'data');
    }

    // ==================== التعليقات ====================

    public function test_comment_and_reply_flow(): void
    {
        $post = Post::factory()->create();
        $commenter = $this->user();
        $replier = $this->user();

        $commentId = $this->actingAsAccount($commenter)
            ->postJson("/api/posts/{$post->id}/comments", ['content' => '  تعليق أول  '])
            ->assertCreated()
            ->assertJsonPath('data.content', 'تعليق أول')
            ->assertJsonPath('data.author.id', $commenter->id)
            ->assertJsonPath('data.is_owner', true)
            ->json('data.id');

        $this->actingAsAccount($replier)
            ->postJson("/api/posts/{$post->id}/comments/{$commentId}", ['content' => 'رد'])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $commentId);

        $this->assertSame(2, $post->fresh()->comments_count);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.replies_count', 1)
            ->assertJsonPath('data.0.can_delete', false);

        $this->getJson("/api/posts/{$post->id}/comments/{$commentId}/replies")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'رد');

        // صاحب التعليق الأصلي يصله إشعار بالرد
        $this->assertSame('comment_reply', $commenter->notifications()->first()->data['type']);
    }

    public function test_comment_can_be_deleted_by_its_author_or_the_post_owner_only(): void
    {
        $post = Post::factory()->create();
        $author = $this->user();
        $stranger = $this->user();

        $make = fn () => Comment::create(['post_id' => $post->id, 'user_id' => $author->user->id, 'content' => 'x']);
        $post->forceFill(['comments_count' => 2])->save();

        $first = $make();
        $second = $make();

        $this->actingAsAccount($stranger)->deleteJson("/api/posts/{$post->id}/comments/{$first->id}")->assertStatus(403);
        $this->actingAsAccount($author)->deleteJson("/api/posts/{$post->id}/comments/{$first->id}")->assertOk();
        $this->actingAsAccount($post->account)->deleteJson("/api/posts/{$post->id}/comments/{$second->id}")->assertOk();

        $this->assertSame(0, $post->fresh()->comments_count);
    }

    public function test_comment_must_belong_to_the_post_in_the_url(): void
    {
        $post = Post::factory()->create();
        $otherPost = Post::factory()->create();
        $author = $this->user();
        $comment = Comment::create(['post_id' => $otherPost->id, 'user_id' => $author->user->id, 'content' => 'x']);

        $this->actingAsAccount($author)->deleteJson("/api/posts/{$post->id}/comments/{$comment->id}")->assertStatus(403);
        $this->actingAsAccount($author)->postJson("/api/posts/{$post->id}/comments/{$comment->id}", ['content' => 'r'])->assertStatus(404);
    }

    // ==================== التقييم ====================

    public function test_review_is_created_then_updated_and_reflected_in_the_post(): void
    {
        $post = Post::factory()->create();
        $account = $this->user();

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/reviews", ['rating' => 3, 'content' => 'جيد'])
            ->assertCreated()->assertJsonPath('data.rating', 3);

        $this->actingAsAccount($account)->postJson("/api/posts/{$post->id}/reviews", ['rating' => 5])->assertCreated();
        $this->actingAsAccount($this->user())->postJson("/api/posts/{$post->id}/reviews", ['rating' => 4])->assertCreated();

        $this->assertDatabaseCount('reviews', 2);

        $this->actingAsAccount($account)->getJson("/api/posts/{$post->id}")
            ->assertJsonPath('data.rating_avg', 4.5)
            ->assertJsonPath('data.reviews_count', 2)
            ->assertJsonPath('data.my_rating', 5);

        $this->getJson("/api/posts/{$post->id}/reviews")->assertOk()->assertJsonCount(2, 'data');
        $this->actingAsAccount($account)->getJson('/api/profile/reviews')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_owner_cannot_review_own_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAsAccount($post->account)
            ->postJson("/api/posts/{$post->id}/reviews", ['rating' => 5])
            ->assertStatus(422)
            ->assertJsonPath('code', 'cannot_review_own_post');
    }

    // ==================== المشاركة ====================

    public function test_share_flow_and_feed_integration(): void
    {
        $post = Post::factory()->create();
        $sharer = $this->user();

        $shareId = $this->actingAsAccount($sharer)
            ->postJson("/api/post-shares/{$post->id}", ['share_text' => 'أنصح به'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'share')
            ->assertJsonPath('data.post.id', $post->id)
            ->assertJsonPath('data.shared_by.id', $sharer->id)
            ->json('data.id');

        $this->assertSame(1, $post->fresh()->shares_count);

        // نفس المنشور لا يُشارك مرتين
        $this->actingAsAccount($sharer)->postJson("/api/post-shares/{$post->id}")
            ->assertStatus(409)->assertJsonPath('code', 'already_shared');

        // الصفحة الرئيسية تعرض المنشور والمشاركة معاً
        $feed = $this->actingAsAccount($sharer)->getJson('/api/posts')->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing(['post', 'share'], array_column($feed->json('data'), 'type'));
        $share = collect($feed->json('data'))->firstWhere('type', 'share');
        $this->assertTrue($share['post']['is_shared']);

        $this->actingAsAccount($this->user())->patchJson("/api/post-shares/{$shareId}", ['share_text' => 'x'])->assertStatus(403);
        $this->actingAsAccount($sharer)->patchJson("/api/post-shares/{$shareId}", ['share_text' => 'نص معدل'])
            ->assertOk()->assertJsonPath('data.share_text', 'نص معدل');

        $this->actingAsAccount($this->user())->deleteJson("/api/post-shares/{$shareId}")->assertStatus(403);
        $this->actingAsAccount($sharer)->deleteJson("/api/post-shares/{$shareId}")->assertOk();

        $this->assertSame(0, $post->fresh()->shares_count);
        $this->assertDatabaseCount('post_shares', 0);
    }

    public function test_cannot_share_own_or_unpublished_post(): void
    {
        $owner = $this->verifiedUser();
        $own = Post::factory()->create(['account_id' => $owner->id]);
        $draft = Post::factory()->draft()->create();

        $this->actingAsAccount($owner)->postJson("/api/post-shares/{$own->id}")->assertStatus(422);
        $this->actingAsAccount($owner)->postJson("/api/post-shares/{$draft->id}")
            ->assertStatus(422)->assertJsonPath('code', 'post_not_published');
    }

    public function test_share_of_a_hidden_post_disappears_from_the_feed(): void
    {
        $post = Post::factory()->create();
        PostShare::create(['account_id' => $this->user()->id, 'post_id' => $post->id]);

        $post->update(['status' => 'under_review']);

        $this->getJson('/api/posts')->assertOk()->assertJsonCount(0, 'data');
    }

    // ==================== الشركات والمتابعة ====================

    public function test_follow_and_unfollow_keep_the_counter_in_sync(): void
    {
        $company = $this->company();
        $follower = $this->user();

        $this->actingAsAccount($follower)->postJson("/api/companies/{$company->id}/follow")
            ->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        // المتابعة مرتين لا تكرر العدّاد
        $this->actingAsAccount($follower)->postJson("/api/companies/{$company->id}/follow")
            ->assertJsonPath('data.followers_count', 1);

        $this->actingAsAccount($follower)->getJson("/api/companies/{$company->id}")
            ->assertOk()
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        $this->asGuest()->getJson("/api/companies/{$company->id}")->assertJsonPath('data.is_following', false);

        $this->assertSame('new_follower', $company->account->notifications()->first()->data['type']);

        $this->actingAsAccount($follower)->deleteJson("/api/companies/{$company->id}/follow")
            ->assertOk()
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);
    }

    public function test_feed_shows_posts_of_followed_companies_only(): void
    {
        $followed = $this->company();
        $other = $this->company();
        $account = $this->user();

        Follow::create(['company_id' => $followed->id, 'account_id' => $account->id]);

        $post = Post::factory()->create(['account_id' => $followed->account_id]);
        Post::factory()->create(['account_id' => $other->account_id]);
        Post::factory()->draft()->create(['account_id' => $followed->account_id]);

        $this->actingAsAccount($account)->getJson('/api/feed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id);
    }

    public function test_public_company_listing_shows_approved_companies_only(): void
    {
        $approved = $this->company(['name' => 'Approved Co']);
        $pending = Company::factory()->pendingApproval()->create(['name' => 'Pending Co']);
        $banned = $this->company(['name' => 'Banned Co']);
        $banned->account->update(['is_active' => false]);

        Post::factory()->create(['account_id' => $approved->account_id]);
        Post::factory()->draft()->create(['account_id' => $approved->account_id]);

        $this->getJson('/api/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Approved Co')
            ->assertJsonPath('data.0.is_verified', true);

        $this->getJson('/api/companies?search=Approved')->assertJsonCount(1, 'data');
        $this->getJson('/api/companies?search=Pending')->assertJsonCount(0, 'data');

        $this->getJson("/api/companies/{$pending->id}")->assertStatus(404);
        $this->actingAsAccount($pending->account)->getJson("/api/companies/{$pending->id}")->assertOk();

        // منشورات الشركة العامة: المنشورة فقط
        $this->getJson("/api/companies/{$approved->id}/posts")->assertOk()->assertJsonCount(1, 'data');
    }
}
