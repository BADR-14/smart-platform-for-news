<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * التصنيفات والوسوم (العرض للعامة والإدارة للأدمن)
 */
class CategoryService
{
    public function getCategories(?string $type = null): Collection
    {
        return Category::query()->forType($type)->orderBy('name')->get(['id', 'name', 'type']);
    }

    public function getCategoriesForAdmin(): Collection
    {
        return Category::query()->withCount(['posts', 'companies'])->orderBy('name')->get();
    }

    /**
     * @param  array{name: string, type: string}  $data
     */
    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * @param  array{name?: string, type?: string}  $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    /**
     * التصنيف المستخدم في منشورات أو شركات لا يُحذف
     */
    public function deleteCategory(Category $category): void
    {
        if ($category->posts()->exists() || $category->companies()->exists()) {
            throw new ApiException('لا يمكن حذف تصنيف مستخدم في منشورات أو شركات.', 422, 'category_in_use');
        }

        // الاسم فريد في الجدول، فنحرّره قبل الحذف المنطقي ليصبح متاحاً للاستخدام من جديد
        $category->update(['name' => "{$category->name} (محذوف #{$category->id})"]);
        $category->delete();
    }

    public function searchTags(?string $search = null, int $limit = 20): Collection
    {
        return Tag::query()
            ->when($search, fn ($q) => $q->where('name', 'LIKE', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);
    }

    public function getTagsForAdmin(?string $search = null): LengthAwarePaginator
    {
        return Tag::query()
            ->withCount('posts')
            ->when($search, fn ($q) => $q->where('name', 'LIKE', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('posts_count')
            ->paginate(20);
    }

    public function deleteTag(Tag $tag): void
    {
        $tag->posts()->detach();
        $tag->delete();
    }
}
