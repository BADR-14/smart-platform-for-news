<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Company;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    private const NATIONAL_ID = '12345678901';

    private function submitUserVerification(Account $account): TestResponse
    {
        return $this->actingAsAccount($account)->post('/api/verification/user', [
            'national_id' => self::NATIONAL_ID,
            'identity_front' => UploadedFile::fake()->image('front.jpg'),
            'identity_back' => UploadedFile::fake()->image('back.jpg'),
        ], ['Accept' => 'application/json']);
    }

    private function companyPayload(array $overrides = []): array
    {
        return [
            'name' => 'Modern Tech',
            'website_url' => 'https://modern.tech',
            'category_id' => Category::factory()->create(['type' => 'company'])->id,
            'description' => 'Software house',
            'phone' => '+963912345678',
            'company_registration_number' => '12345',
            'tax_id' => '123456789012',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'documents' => [
                'commercial_register' => UploadedFile::fake()->image('register.jpg'),
                'tax_document' => UploadedFile::fake()->image('tax.jpg'),
            ],
            ...$overrides,
        ];
    }

    private function submitCompany(Account $account, array $overrides = []): TestResponse
    {
        return $this->actingAsAccount($account)
            ->post('/api/companies', $this->companyPayload($overrides), ['Accept' => 'application/json']);
    }

    // ==================== توثيق المستخدم: القرار الآلي ====================

    public function test_high_score_is_approved_automatically(): void
    {
        $account = $this->user(['name' => 'Layla Haddad']);
        $this->ocr->reads('SYRIAN ARAB REPUBLIC  layla  HADDAD  ID 123 4567 8901  issued 12/05/2020', 0.96);

        $this->submitUserVerification($account)
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.user.status', 'approved');

        $request = VerificationRequest::firstOrFail();
        $this->assertSame(99, $request->confidence_score);
        $this->assertNotNull($request->reviewed_at);
        $this->assertTrue($account->fresh()->isVerified());
        $this->assertSame('verification_reviewed', $account->notifications()->first()->data['type']);
    }

    public function test_medium_score_goes_to_admin_review(): void
    {
        $admin = $this->admin();
        $account = $this->user(['name' => 'Layla Haddad']);
        // الاسم غير مطابق: 30 (رقم) + 20 (تاريخ) + 19 (وضوح) = 69
        $this->ocr->reads('Someone Else  12345678901  12/05/2020', 0.95);

        $this->submitUserVerification($account)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.user.can_submit', false);

        $request = VerificationRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame(69, $request->confidence_score);
        $this->assertSame(['name_mismatch'], $request->rejection_reasons);
        $this->assertSame('verification_requested', $admin->notifications()->first()->data['type']);
    }

    public function test_low_score_is_rejected_automatically(): void
    {
        $account = $this->user(['name' => 'Layla Haddad']);
        $this->ocr->reads('completely unrelated text', 0.95);

        $this->submitUserVerification($account)
            ->assertCreated()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.user.attempts_left', 2)
            ->assertJsonPath('data.user.can_submit', true);

        $this->assertContains('id_mismatch', VerificationRequest::firstOrFail()->rejection_reasons);
    }

    public function test_request_falls_back_to_manual_review_when_ocr_is_unavailable(): void
    {
        $this->admin();
        $account = $this->user();
        $this->ocr->available = false;

        $this->submitUserVerification($account)->assertCreated()->assertJsonPath('data.status', 'pending');

        $request = VerificationRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->confidence_score);
    }

    public function test_all_requests_go_to_admin_when_automatic_verification_is_disabled(): void
    {
        config(['platform.verification.auto_enabled' => false]);

        $admin = $this->admin();
        $account = $this->user(['name' => 'Layla Haddad']);
        // وثيقة كانت ستُقبل آلياً لو كان التوثيق الآلي مفعّلاً
        $this->ocr->reads('SYRIAN ARAB REPUBLIC  layla  HADDAD  ID 123 4567 8901  issued 12/05/2020', 0.96);

        $this->submitUserVerification($account)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $request = VerificationRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->confidence_score);
        $this->assertFalse($account->fresh()->isVerified());
        $this->assertSame('verification_requested', $admin->notifications()->first()->data['type']);
    }

    public function test_request_falls_back_to_manual_review_when_ocr_crashes(): void
    {
        $account = $this->user();
        $this->ocr->shouldFail = true;

        $this->submitUserVerification($account)->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_documents_are_stored_privately(): void
    {
        $this->ocr->available = false;
        $this->submitUserVerification($this->user())->assertCreated();

        $path = VerificationRequest::firstOrFail()->documents()->firstOrFail()->file_url;

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_verification_attempts_are_limited(): void
    {
        $account = $this->user();
        $this->ocr->reads('unrelated', 0.9);

        foreach (range(1, 3) as $attempt) {
            $this->submitUserVerification($account)->assertCreated();
        }

        $this->submitUserVerification($account)
            ->assertStatus(422)
            ->assertJsonPath('code', 'verification_attempts_exhausted');
    }

    public function test_cannot_submit_while_a_request_is_pending_or_after_approval(): void
    {
        $account = $this->user();
        $this->ocr->available = false;

        $this->submitUserVerification($account)->assertCreated();
        $this->submitUserVerification($account)->assertStatus(422)->assertJsonPath('code', 'verification_pending');

        $this->submitUserVerification($this->verifiedUser())
            ->assertStatus(422)->assertJsonPath('code', 'already_verified');
    }

    // ==================== مراجعة الأدمن ====================

    public function test_admin_can_approve_a_pending_request(): void
    {
        $admin = $this->admin();
        $account = $this->user();
        $this->ocr->available = false;
        $this->submitUserVerification($account)->assertCreated();
        $request = VerificationRequest::firstOrFail();

        $list = $this->actingAsAccount($admin)->getJson('/api/admin/verification?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('stats.pending', 1)
            ->assertJsonPath('data.0.account.national_id', self::NATIONAL_ID);

        // رابط الوثيقة موقّع: يعمل بدون توكن، ويُرفض إذا عُبث به
        $documentUrl = $list->json('data.0.documents.0.file_url');
        $this->asGuest()->get($documentUrl)->assertOk();
        $this->asGuest()->getJson(strtok($documentUrl, '?'))->assertStatus(403);

        $this->actingAsAccount($admin)
            ->postJson("/api/admin/verification/{$request->id}/review", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by.name', $admin->name);

        $this->assertTrue($account->fresh()->isVerified());

        // لا يُراجَع الطلب مرتين
        $this->actingAsAccount($admin)
            ->postJson("/api/admin/verification/{$request->id}/review", ['decision' => 'rejected', 'rejection_reasons' => ['x']])
            ->assertStatus(422)->assertJsonPath('code', 'already_reviewed');
    }

    public function test_admin_rejection_requires_reasons_and_is_shown_to_the_user(): void
    {
        $admin = $this->admin();
        $account = $this->user();
        $this->ocr->available = false;
        $this->submitUserVerification($account)->assertCreated();
        $request = VerificationRequest::firstOrFail();

        $this->actingAsAccount($admin)
            ->postJson("/api/admin/verification/{$request->id}/review", ['decision' => 'rejected'])
            ->assertStatus(422)->assertJsonValidationErrors('rejection_reasons');

        $this->actingAsAccount($admin)
            ->postJson("/api/admin/verification/{$request->id}/review", [
                'decision' => 'rejected',
                'rejection_reasons' => ['الصورة غير واضحة'],
            ])->assertOk();

        $this->actingAsAccount($account->fresh())->getJson('/api/profile/verification-status')
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.user.rejection_reasons.0', 'الصورة غير واضحة')
            ->assertJsonPath('data.user.can_submit', true);

        $this->actingAsAccount($admin)->getJson("/api/admin/verification/accounts/{$account->id}/history")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_non_admin_cannot_access_verification_management(): void
    {
        $this->actingAsAccount($this->user())->getJson('/api/admin/verification')->assertStatus(403);
    }

    // ==================== طلب الشركة ====================

    public function test_company_request_keeps_the_account_as_user_until_approved(): void
    {
        $admin = $this->admin();
        $account = $this->user(['name' => 'Maher Ali']);
        $this->ocr->available = false;

        $this->submitCompany($account)
            ->assertCreated()
            ->assertJsonPath('data.company.name', 'Modern Tech')
            ->assertJsonPath('data.company.is_verified', false)
            ->assertJsonPath('data.verification.company.status', 'pending')
            ->assertJsonPath('data.verification.account_type', 'user');

        $account->refresh();
        $company = Company::firstOrFail();

        $this->assertSame('user', $account->role()->slug());
        $this->assertSame('Maher Ali', $account->name);
        $this->assertNotNull($company->wallet, 'wallet is created with the company');
        $this->assertDatabaseHas('company_memberships', ['company_id' => $company->id, 'role' => 'owner']);
        $this->assertContains('new_company_registered', $admin->notifications()->get()->pluck('data.type')->all());

        // غير ظاهرة للعامة ولا يملك صلاحيات لوحة الشركة قبل الموافقة
        $this->asGuest()->getJson('/api/companies')->assertJsonCount(0, 'data');
        $this->actingAsAccount($account)->getJson('/api/company/dashboard')->assertStatus(403);
    }

    public function test_approving_a_company_request_converts_the_account_to_a_company(): void
    {
        $admin = $this->admin();
        $account = $this->user(['name' => 'Maher Ali']);
        $this->ocr->available = false;
        $this->submitCompany($account)->assertCreated();

        $request = VerificationRequest::where('type', 'company')->firstOrFail();

        $this->actingAsAccount($admin)
            ->getJson('/api/admin/verification?type=company')
            ->assertJsonPath('data.0.company.name', 'Modern Tech')
            ->assertJsonPath('data.0.company.tax_id', '123456789012');

        $this->actingAsAccount($admin)
            ->postJson("/api/admin/verification/{$request->id}/review", ['decision' => 'approved'])
            ->assertOk();

        $account->refresh();

        $this->assertTrue($account->isCompany());
        $this->assertTrue($account->isVerified());
        $this->assertSame('Modern Tech', $account->name);
        $this->assertNotNull($account->avatar, 'company logo becomes the account avatar');

        $this->asGuest()->getJson('/api/companies')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Modern Tech');
        $this->actingAsAccount($account)->getJson('/api/company/dashboard')->assertOk();
        $this->actingAsAccount($account)->getJson('/api/me')->assertJsonPath('data.type', 'company');
    }

    public function test_company_documents_are_scored_against_company_name_and_tax_id(): void
    {
        $account = $this->user(['name' => 'Maher Ali']);
        $this->ocr->reads('Commercial Register — MODERN TECH — Tax No 123456789012 — 2021-03-15', 0.97);

        $this->submitCompany($account)->assertCreated()->assertJsonPath('data.verification.company.status', 'approved');

        $this->assertTrue($account->fresh()->isCompany());
    }

    public function test_rejected_company_request_leaves_a_verified_user_verified(): void
    {
        $account = $this->verifiedUser();
        $this->ocr->reads('nothing relevant', 0.9);

        $this->submitCompany($account)->assertCreated()->assertJsonPath('data.verification.company.status', 'rejected');

        $account->refresh();
        $this->assertTrue($account->isVerified());
        $this->assertFalse($account->isCompany());

        // إعادة التقديم تحدّث نفس الشركة بدل إنشاء شركة ثانية
        $this->ocr->available = false;
        $this->submitCompany($account, ['name' => 'Modern Tech v2'])->assertCreated();

        $this->assertDatabaseCount('companies', 1);
        $this->assertSame('Modern Tech v2', Company::firstOrFail()->name);
    }

    public function test_company_request_validates_required_fields(): void
    {
        $this->actingAsAccount($this->user())
            ->postJson('/api/companies', ['phone' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'description', 'phone', 'logo', 'documents']);
    }

    public function test_existing_company_cannot_submit_another_request(): void
    {
        $company = $this->company();

        $this->submitCompany($company->account)->assertStatus(422)->assertJsonPath('code', 'invalid_account_type');
    }
}
