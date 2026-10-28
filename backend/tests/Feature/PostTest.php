<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FeaturedPost;
use App\Models\Post;
use App\Models\Review;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'دورة Flutter للمبتدئين',
            'description' => 'وصف الدورة',
            'post_type' => 'course',
            'category_id' => Category::factory()->create()->id,
            'is_online' => true,
            'action' => 'publish',
            ...$overrides,
        ];
    }

    // ==================== الإنشاء ====================

    public function test_unverified_user_cannot_create_a_post(): void
    {
        $this->actingAsAccount($this->user())
            ->postJson('/api/posts', $this->payload())
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_not_verified');
    }

    public function test_guest_cannot_create_a_post(): void
    {
        $this->postJson('/api/posts', $this->payload())->assertStatus(401);
    }

    public function test_verified_user_can_create_a_post_with_media_and_tags(): void
    {
        $account = $this->verifiedUser();

        $response = $this->actingAsAccount($account)->post('/api/posts', $this->payload([
            'is_online' => '1',
            'tags' => ['Flutter', 'مجاني', 'flutter'],
            'media' => [UploadedFile::fake()->image('cover.jpg'), UploadedFile::fake()->image('second.png')],
        ]), ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'دورة Flutter للمبتدئين')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.author.id', $account->id)
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonCount(2, 'data.media')
            ->assertJsonCount(2, 'data.tags');

        $this->assertNotNull($response->json('data.cover_url'));
        Storage::disk('public')->assertExists(Post::first()->media->first()->media_url);
    }

    public function test_company_can_create_a_post_and_counter_updates(): void
    {
        $company = $this->company();

        $this->actingAsAccount($company->account)
            ->postJson('/api/posts', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.author.type', 'company')
            ->assertJsonPath('data.author.company_id', $company->id);

        $company->refresh();
        $this->assertSame(1, $company->posts_count);
        $this->assertNotNull($company->last_active_at);
    }

    public function test_onsite_post_requires_a_location(): void
    {
        $account = $this->verifiedUser();

        $this->actingAsAccount($account)
            ->postJson('/api/posts', $this->payload(['is_online' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['location', 'latitude', 'longitude']);

        $this->actingAsAccount($account)
            ->postJson('/api/posts', $this->payload([
                'is_online' => false,
                'location' => 'دمشق',
                'latitude' => 33.5138,
                'longitude' => 36.2765,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.location', 'دمشق');
    }

    public function test_event_requires_dates(): void
    {
        $this->actingAsAccount($this->verifiedUser())
            ->postJson('/api/posts', $this->payload(['post_type' => 'event']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_post_can_be_saved_as_draft(): void
    {
        $this->actingAsAccount($this->verifiedUser())
            ->postJson('/api/posts', $this->payload(['action' => 'draft']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    // ==================== التعديل والحذف ====================

    public function test_owner_can_update_post(): void
    {
        $account = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $account->id]);

        $this->actingAsAccount($account)
            ->patchJson("/api/posts/{$post->id}", ['title' => 'عنوان جديد', 'tags' => ['جديد'], 'action' => 'archive'])
            ->assertOk()
            ->assertJsonPath('data.title', 'عنوان جديد')
            ->assertJsonPath('data.status', 'archived')
            ->assertJsonPath('data.tags.0.name', 'جديد');
    }

    public function test_owner_can_update_post_media_using_multipart(): void
    {
        $account = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $account->id]);

        $first = $this->actingAsAccount($account)->post("/api/posts/{$post->id}", [
            '_method' => 'PATCH',
            'media' => [UploadedFile::fake()->image('a.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(1, 'data.media');

        $this->actingAsAccount($account)->post("/api/posts/{$post->id}", [
            '_method' => 'PATCH',
            'remove_media_ids' => [$first->json('data.media.0.id')],
            'media' => [UploadedFile::fake()->image('b.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(1, 'data.media');
    }

    public function test_user_cannot_update_or_delete_someone_elses_post(): void
    {
        $post = Post::factory()->create();
        $other = $this->verifiedUser();

        $this->actingAsAccount($other)->patchJson("/api/posts/{$post->id}", ['title' => 'hacked'])->assertStatus(403);
        $this->actingAsAccount($other)->deleteJson("/api/posts/{$post->id}")->assertStatus(403);

        $this->assertNotSame('hacked', $post->fresh()->title);
    }

    public function test_owner_can_delete_post(): void
    {
        $account = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $account->id]);

        $this->actingAsAccount($account)->deleteJson("/api/posts/{$post->id}")->assertOk();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_owner_cannot_set_moderation_statuses(): void
    {
        $account = $this->verifiedUser();
        $post = Post::factory()->create(['account_id' => $account->id]);

        $this->actingAsAccount($account)
            ->patchJson("/api/posts/{$post->id}", ['action' => 'under_review'])
            ->assertStatus(422);
    }

    // ==================== العرض ====================

    public function test_guest_can_browse_published_posts_only(): void
    {
        Post::factory()->count(3)->create();
        Post::factory()->draft()->create();
        Post::factory()->create(['status' => 'under_review']);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.is_owner', false)
            ->assertJsonPath('data.0.my_reaction', null);
    }

    public function test_posts_can_be_filtered_and_searched(): void
    {
        $category = Category::factory()->create();
        Post::factory()->create(['title' => 'Laravel Bootcamp', 'post_type' => 'course', 'category_id' => $category->id]);
        Post::factory()->create(['title' => 'AI Meetup', 'post_type' => 'event', 'is_online' => false, 'location' => 'Damascus']);
        $rated = Post::factory()->create(['title' => 'Top Lecture', 'post_type' => 'lecture']);
        Review::create(['user_id' => $this->user()->user->id, 'post_id' => $rated->id, 'rating' => 5]);

        $this->getJson('/api/posts?keyword=laravel')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Laravel Bootcamp');
        $this->getJson('/api/posts?post_type=event')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'AI Meetup');
        $this->getJson("/api/posts?category_id={$category->id}")->assertJsonCount(1, 'data');
        $this->getJson('/api/posts?is_online=0')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'AI Meetup');
        $this->getJson('/api/posts?rating=4')->assertJsonCount(1, 'data')->assertJsonPath('data.0.rating_avg', 5);
        $this->getJson('/api/posts?keyword=%25%25')->assertJsonCount(0, 'data');
    }

    public function test_unpublished_post_is_visible_to_owner_only(): void
    {
        $owner = $this->verifiedUser();
        $post = Post::factory()->draft()->create(['account_id' => $owner->id]);

        $this->getJson("/api/posts/{$post->id}")->assertStatus(403);
        $this->actingAsAccount($this->user())->getJson("/api/posts/{$post->id}")->assertStatus(403);
        $this->actingAsAccount($owner)->getJson("/api/posts/{$post->id}")->assertOk()->assertJsonPath('data.status', 'draft');
    }

    public function test_my_posts_lists_all_own_statuses(): void
    {
        $account = $this->verifiedUser();
        Post::factory()->create(['account_id' => $account->id]);
        Post::factory()->draft()->create(['account_id' => $account->id]);
        Post::factory()->create();

        $this->actingAsAccount($account)->getJson('/api/posts/my-posts')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAsAccount($account)->getJson('/api/posts/my-posts?status=draft')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_account_posts_show_published_only(): void
    {
        $account = $this->verifiedUser();
        Post::factory()->create(['account_id' => $account->id]);
        Post::factory()->draft()->create(['account_id' => $account->id]);

        $this->getJson("/api/accounts/{$account->id}/posts")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_featured_endpoint_returns_only_running_features(): void
    {
        $company = $this->company();
        $running = Post::factory()->create(['account_id' => $company->account_id]);
        $ended = Post::factory()->create(['account_id' => $company->account_id]);
        Post::factory()->create();

        FeaturedPost::create(['post_id' => $running->id, 'company_id' => $company->id, 'cost' => 10, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(7)]);
        FeaturedPost::create(['post_id' => $ended->id, 'company_id' => $company->id, 'cost' => 10, 'status' => 'active', 'starts_at' => now()->subDays(8), 'ends_at' => now()->subDay()]);

        $this->getJson('/api/posts/featured')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $running->id)
            ->assertJsonPath('data.0.is_featured', true);

        $this->getJson("/api/posts/{$ended->id}")->assertJsonPath('data.is_featured', false);
    }

    public function test_view_is_counted_once_per_viewer(): void
    {
        $post = Post::factory()->create();
        $viewer = $this->user();

        $this->actingAsAccount($viewer)->postJson("/api/posts/{$post->id}/view")->assertOk()->assertJsonPath('data.views_count', 1);
        $this->actingAsAccount($viewer)->postJson("/api/posts/{$post->id}/view")->assertOk()->assertJsonPath('data.views_count', 1);
        $this->actingAsAccount($this->user())->postJson("/api/posts/{$post->id}/view")->assertJsonPath('data.views_count', 2);
        $this->actingAsAccount($post->account)->postJson("/api/posts/{$post->id}/view")->assertJsonPath('data.views_count', 2);
    }
}
