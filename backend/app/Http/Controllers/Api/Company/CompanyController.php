<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyIndexRequest;
use App\Http\Requests\Company\GetCompanyPostsRequest;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\PostResource;
use App\Models\Company;
use App\Services\Company\CompanyService;
use App\Services\Verifications\CompanyVerificationService;
use App\Services\Verifications\VerificationStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService,
        private readonly CompanyVerificationService $companyVerificationService,
        private readonly VerificationStatusService $verificationStatusService,
    ) {}

    /**
     * GET /companies?search=&category_id=
     */
    public function index(CompanyIndexRequest $request): JsonResponse
    {
        $companies = $this->companyService->getCompanies(
            search: $request->validated('search'),
            categoryId: $request->integer('category_id') ?: null,
            viewer: $request->user(),
            perPage: $request->integer('per_page', 12),
        );

        return $this->paginated($companies, CompanyResource::class);
    }

    /**
     * GET /companies/{company}
     */
    public function show(Request $request, Company $company): JsonResponse
    {
        return $this->ok(new CompanyResource($this->companyService->getCompany($company, $request->user())));
    }

    /**
     * GET /companies/{company}/posts
     */
    public function posts(GetCompanyPostsRequest $request, Company $company): JsonResponse
    {
        $posts = $this->companyService->getCompanyPosts($company, $request->user(), $request->integer('per_page', 10));

        return $this->paginated($posts, PostResource::class);
    }

    /**
     * POST /companies/{company}/follow
     */
    public function follow(Request $request, Company $company): JsonResponse
    {
        return $this->ok($this->companyService->follow($company, $request->user()), 'تمت المتابعة بنجاح.');
    }

    /**
     * DELETE /companies/{company}/follow
     */
    public function unfollow(Request $request, Company $company): JsonResponse
    {
        return $this->ok($this->companyService->unfollow($company, $request->user()), 'تم إلغاء المتابعة.');
    }

    /**
     * POST /companies — طلب إنشاء شركة وتوثيقها بخطوة واحدة
     */
    public function create(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->companyVerificationService->submitCompanyVerification(
            $request->user(),
            $request->safe()->except(['logo', 'documents']),
            $request->file('logo'),
            [
                'commercial_register' => $request->file('documents.commercial_register'),
                'tax_document' => $request->file('documents.tax_document'),
            ],
        );

        return $this->created(
            [
                'company' => new CompanyResource($company),
                'verification' => $this->verificationStatusService->forAccount($request->user()->refresh()),
            ],
            'تم استلام طلب إنشاء الشركة. ستتم مراجعته وإشعارك بالنتيجة.',
        );
    }
}
