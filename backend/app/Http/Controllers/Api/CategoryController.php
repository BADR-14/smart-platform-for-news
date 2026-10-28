<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    // ==================== عام ====================

    /**
     * GET /categories?type=post|company
     */
    public function index(Request $request): JsonResponse
    {
        return $this->ok($this->categoryService->getCategories($request->query('type')));
    }

    /**
     * GET /tags?search= — اقتراحات الوسوم عند إنشاء منشور
     */
    public function tags(Request $request): JsonResponse
    {
        return $this->ok($this->categoryService->searchTags($request->query('search')));
    }

    // ==================== الأدمن ====================

    public function adminIndex(): JsonResponse
    {
        return $this->ok($this->categoryService->getCategoriesForAdmin());
    }

    public function store(Request $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
            'type' => ['required', 'in:post,company,both'],
        ]));

        return $this->created($category, 'تم إنشاء التصنيف.');
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $category = $this->categoryService->updateCategory($category, $request->validate([
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'type' => ['sometimes', 'in:post,company,both'],
        ]));

        return $this->ok($category, 'تم تعديل التصنيف.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->deleteCategory($category);

        return $this->message('تم حذف التصنيف.');
    }

    public function adminTags(Request $request): JsonResponse
    {
        return $this->paginated($this->categoryService->getTagsForAdmin($request->query('search')));
    }

    public function destroyTag(Tag $tag): JsonResponse
    {
        $this->categoryService->deleteTag($tag);

        return $this->message('تم حذف الوسم.');
    }
}
