<?php

namespace Tests;

use App\Contracts\DocumentTextExtractor;
use App\Models\Account;
use App\Models\Company;
use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\MediaTypeSeeder;
use Database\Seeders\ReactTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeDocumentTextExtractor;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected FakeDocumentTextExtractor $ocr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([AccountTypeSeeder::class, ReactTypeSeeder::class, MediaTypeSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');
        Mail::fake();

        // قارئ وثائق مزيّف: الاختبارات لا تتصل بـ Google Vision
        $this->ocr = new FakeDocumentTextExtractor;
        $this->app->instance(DocumentTextExtractor::class, $this->ocr);
    }

    protected function actingAsAccount(Account $account): static
    {
        Sanctum::actingAs($account);

        return $this;
    }

    /**
     * يلغي أثر actingAsAccount السابق ضمن نفس الاختبار ليُرسل الطلب التالي كزائر
     */
    protected function asGuest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    protected function user(array $attributes = []): Account
    {
        return Account::factory()->create($attributes);
    }

    protected function verifiedUser(array $attributes = []): Account
    {
        return Account::factory()->verified()->create($attributes);
    }

    protected function admin(): Account
    {
        return Account::factory()->admin()->create();
    }

    /**
     * شركة موافق عليها (حسابها من نوع Company) ومعها محفظتها
     */
    protected function company(array $attributes = [], float $balance = 0): Company
    {
        $company = Company::factory()->create($attributes);

        if ($balance > 0) {
            $company->wallet()->update(['balance' => $balance]);
        }

        return $company->refresh();
    }
}
