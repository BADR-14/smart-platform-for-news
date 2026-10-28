<?php

namespace Database\Seeders;

use App\Enums\Featured\FeaturedStatus;
use App\Models\Account;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\FeaturedPost;
use App\Models\Follow;
use App\Models\Plan;
use App\Models\Post;
use App\Models\PostShare;
use App\Models\Report;
use App\Models\Review;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\UserReact;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\Verifications\BaseVerificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * بيانات تجريبية للعرض والمناقشة. كل الحسابات كلمة مرورها: password
 *
 *   user@test.com      مستخدم غير موثّق
 *   verified@test.com  مستخدم موثّق (يستطيع النشر)
 *   company@test.com   شركة موثّقة لها رصيد وباقة نشطة
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Account::where('email', 'company@test.com')->exists()) {
            return; // زُرعت مسبقاً
        }

        $categories = Category::all();
        $tags = Tag::all();

        // ---------- الحسابات ----------
        $plainUser = Account::factory()->create(['name' => 'سامر الخطيب', 'email' => 'user@test.com']);
        $verifiedUser = Account::factory()->verified()->create(['name' => 'ليلى حداد', 'email' => 'verified@test.com']);

        $verifiedUser->user->update([
            'short_bio' => 'مهندسة برمجيات مهتمة بتطوير تطبيقات الموبايل.',
            'linkedin_url' => 'https://linkedin.com/in/layla-haddad',
        ]);

        $users = collect([$plainUser, $verifiedUser])
            ->merge(Account::factory()->count(4)->verified()->create())
            ->merge(Account::factory()->count(4)->create());

        // ---------- الشركات ----------
        $companyNames = [
            ['شركة التقنية الحديثة', 'company@test.com', 'حلول برمجية وتدريب تقني للشركات والأفراد.'],
            ['أكاديمية المستقبل', 'academy@test.com', 'دورات ومعسكرات تدريبية في البرمجة وعلوم البيانات.'],
            ['معارض الشام', 'expo@test.com', 'تنظيم المعارض والمؤتمرات المتخصصة.'],
            ['حاضنة الأعمال الريادية', 'incubator@test.com', 'دعم وتمويل الشركات الناشئة.'],
            ['مركز الإبداع للتصميم', 'design@test.com', 'ورشات ودورات في التصميم وتجربة المستخدم.'],
        ];

        $companies = collect($companyNames)->map(fn (array $row) => Company::factory()->create([
            'account_id' => Account::factory()->company()->state(['email' => $row[1]]),
            'name' => $row[0],
            'description' => $row[2],
            'category_id' => $categories->whereIn('type', ['company', 'both'])->random()->id,
        ]));

        $mainCompany = $companies->first();
        $this->fundWallet($mainCompany, 250);
        $this->fundWallet($companies[1], 60);

        // ---------- الباقات ----------
        $plan = Plan::orderBy('price')->first();

        if ($plan) {
            CompanyPlan::create([
                'company_id' => $mainCompany->id,
                'plan_id' => $plan->id,
                'posts_remaining' => $plan->posts_count - 1,
                'status' => 'active',
                'price_paid' => $plan->price,
            ]);
        }

        // ---------- المنشورات ----------
        // الشركة الرئيسية تأخذ الحصة الأكبر من المنشورات حتى تكون لوحتها غنية بالبيانات
        $authors = collect()
            ->merge(array_fill(0, 4, $mainCompany->account))
            ->merge($companies->skip(1)->flatMap(fn (Company $company) => [$company->account, $company->account]))
            ->merge($users->filter->isVerified())
            ->values();
        $posts = $this->seedPosts($authors, $categories, $tags);

        // مسودة ومنشور مؤرشف للشركة الرئيسية (تظهر في لوحة الشركة فقط)
        Post::factory()->draft()->create([
            'account_id' => $mainCompany->account_id,
            'category_id' => $categories->random()->id,
            'title' => 'مسودة: برنامج التدريب الصيفي',
        ]);

        // ---------- المنشورات المميزة ----------
        $companyPosts = $posts->filter(fn (Post $post) => $companies->pluck('account_id')->contains($post->account_id));

        $companyPosts->take(3)->each(function (Post $post, int $index) use ($companies, $mainCompany) {
            $company = $companies->firstWhere('account_id', $post->account_id);

            FeaturedPost::create([
                'post_id' => $post->id,
                'company_id' => $company->id,
                'cost' => 10,
                'payment_method' => $company->is($mainCompany) ? 'plan' : 'wallet',
                'status' => FeaturedStatus::Active->value,
                'starts_at' => now()->subDays($index),
                'ends_at' => now()->addDays(7 - $index),
            ]);
        });

        // ---------- التفاعلات ----------
        $this->seedInteractions($posts, $users, $companies);

        // ---------- البلاغات ----------
        $reportedPosts = $posts->random(2);

        foreach ($reportedPosts as $index => $post) {
            Report::create([
                'reporter_account_id' => $users[$index]->id,
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => $index === 0 ? 'محتوى مضلل' : 'إعلان غير مرتبط بالموضوع',
            ]);
        }

        Report::create([
            'reporter_account_id' => $verifiedUser->id,
            'reportable_type' => 'account',
            'reportable_id' => $users->last()->id,
            'reason' => 'تعليقات مسيئة',
        ]);

        // ---------- طلبات التوثيق بانتظار الأدمن ----------
        $pendingUser = $users->reject->isVerified()->last();
        $pendingUser->update(['verification_status' => 'pending']);
        $pendingUser->user->update(['national_id' => '01234567890']);

        $userRequest = VerificationRequest::create([
            'account_id' => $pendingUser->id,
            'type' => 'user',
            'status' => 'pending',
            'confidence_score' => 72,
            'rejection_reasons' => ['name_mismatch'],
        ]);
        $this->attachSampleDocument($userRequest, 'identity_front', 'ID CARD - FRONT');
        $this->attachSampleDocument($userRequest, 'identity_back', 'ID CARD - BACK');

        $pendingCompany = Company::factory()->pendingApproval()->create([
            'name' => 'شركة الأفق للاستشارات',
            'description' => 'استشارات إدارية ومالية للشركات الصغيرة والمتوسطة.',
            'category_id' => $categories->whereIn('type', ['company', 'both'])->random()->id,
        ]);
        // حساب صاحب الطلب يبقى مستخدماً باسمه حتى تتم الموافقة
        $pendingCompany->account->update(['name' => 'ماهر العلي', 'email' => 'pending-company@test.com']);

        $companyRequest = VerificationRequest::create([
            'account_id' => $pendingCompany->account_id,
            'type' => 'company',
            'status' => 'pending',
        ]);
        $this->attachSampleDocument($companyRequest, 'commercial_register', 'COMMERCIAL REGISTER');
        $this->attachSampleDocument($companyRequest, 'tax_document', 'TAX DOCUMENT');
    }

    /**
     * وثيقة تجريبية (صورة بسيطة مولَّدة) حتى تظهر الوثائق في نافذة مراجعة الأدمن
     */
    private function attachSampleDocument(VerificationRequest $request, string $type, string $label): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $image = imagecreatetruecolor(640, 400);
        imagefill($image, 0, 0, imagecolorallocate($image, 242, 246, 249));
        imagefilledrectangle($image, 0, 0, 640, 70, imagecolorallocate($image, 26, 52, 75));
        imagestring($image, 5, 24, 26, 'SAMPLE - '.$label, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 4, 24, 110, 'Demo document for request #'.$request->id, imagecolorallocate($image, 26, 52, 75));
        imagestring($image, 3, 24, 150, 'Generated by DemoSeeder - not a real document', imagecolorallocate($image, 100, 116, 139));
        imagerectangle($image, 24, 200, 200, 370, imagecolorallocate($image, 220, 122, 50));

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        $path = "verification/{$request->id}/{$type}.png";
        Storage::disk(BaseVerificationService::DOCUMENTS_DISK)->put($path, $contents);

        VerificationDocument::create([
            'verification_request_id' => $request->id,
            'document_type' => $type,
            'file_url' => $path,
        ]);
    }

    private function fundWallet(Company $company, float $amount): void
    {
        $wallet = $company->wallet()->firstOrFail();
        $wallet->increment('balance', $amount);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'status' => 'completed',
            'amount' => $amount,
            'currency' => $wallet->currency,
            'description' => 'شحن المحفظة (بيانات تجريبية)',
            'meta' => ['kind' => 'topup'],
        ]);
    }

    /**
     * @param  Collection<int, Account>  $authors
     * @return Collection<int, Post>
     */
    private function seedPosts(Collection $authors, Collection $categories, Collection $tags): Collection
    {
        $titles = [
            'lecture' => [
                'محاضرة: مدخل إلى الذكاء الاصطناعي التوليدي',
                'محاضرة: أساسيات الأمن السيبراني للشركات',
                'محاضرة: كيف تبني مسيرتك المهنية في البرمجة',
                'محاضرة: اتجاهات التسويق الرقمي هذا العام',
                'محاضرة: إدارة المشاريع بمنهجية Agile',
            ],
            'exhibition' => [
                'معرض التقنية والابتكار السنوي',
                'معرض الشركات الناشئة',
                'معرض التوظيف والتدريب',
                'معرض التصميم والفنون الرقمية',
            ],
            'event' => [
                'ملتقى رواد الأعمال',
                'هاكاثون تطوير التطبيقات',
                'لقاء مجتمع المطورين الشهري',
                'يوم مفتوح للتعريف بالمنح التدريبية',
                'مؤتمر التحول الرقمي',
            ],
            'course' => [
                'دورة تطوير تطبيقات الموبايل باستخدام Flutter',
                'دورة تطوير الواجهات باستخدام React',
                'دورة بناء الـ API باستخدام Laravel',
                'دورة تحليل البيانات باستخدام Python',
                'دورة أساسيات تصميم تجربة المستخدم',
                'دورة المحاسبة لغير المحاسبين',
            ],
        ];

        $postCategories = $categories->whereIn('type', ['post', 'both']);
        $posts = collect();
        $daysAgo = 0;

        foreach ($titles as $type => $typeTitles) {
            foreach ($typeTitles as $title) {
                $isOnline = $type === 'lecture' || fake()->boolean(30);

                $post = Post::factory()
                    ->when(! $isOnline, fn ($factory) => $factory->onsite())
                    ->create([
                        'account_id' => $authors[$posts->count() % $authors->count()]->id,
                        'category_id' => $postCategories->random()->id,
                        'post_type' => $type,
                        'title' => $title,
                        'description' => "{$title}.\n\nفرصة للتعلّم وتبادل الخبرات مع مختصين في المجال. "
                            .'المقاعد محدودة والتسجيل متاح حتى اكتمال العدد.',
                        'location' => $isOnline ? null : fake()->randomElement(['دمشق - المزة', 'حلب - الجميلية', 'حمص - الحمراء', 'اللاذقية - الكورنيش']),
                        'created_at' => now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 20)),
                    ]);

                $post->tags()->sync($tags->random(fake()->numberBetween(1, 3))->pluck('id'));
                $posts->push($post);
                $daysAgo += 2;
            }
        }

        return $posts;
    }

    /**
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, Account>  $users
     * @param  Collection<int, Company>  $companies
     */
    private function seedInteractions(Collection $posts, Collection $users, Collection $companies): void
    {
        $comments = ['معلومات مفيدة، شكراً لكم.', 'هل التسجيل ما زال متاحاً؟', 'حضرت النسخة السابقة وكانت ممتازة.', 'متى موعد الدورة القادمة؟', 'أنصح الجميع بالحضور.'];

        foreach ($posts as $post) {
            $others = $users->reject(fn (Account $account) => $account->id === $post->account_id);

            foreach ($others->random(fake()->numberBetween(2, 6)) as $account) {
                UserReact::create([
                    'user_id' => $account->user->id,
                    'post_id' => $post->id,
                    'react_type_id' => fake()->numberBetween(1, 6),
                ]);
            }

            foreach ($others->random(fake()->numberBetween(0, 3)) as $account) {
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $account->user->id,
                    'content' => fake()->randomElement($comments),
                ]);

                if (fake()->boolean(30)) {
                    Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $post->account->user->id,
                        'parent_id' => $comment->id,
                        'content' => 'شكراً لاهتمامك، يسعدنا حضورك.',
                    ]);
                }
            }

            foreach ($others->random(fake()->numberBetween(0, 3)) as $account) {
                Review::create([
                    'user_id' => $account->user->id,
                    'post_id' => $post->id,
                    'rating' => fake()->numberBetween(3, 5),
                    'content' => fake()->boolean() ? 'تجربة مفيدة ومنظمة.' : null,
                ]);
            }

            if (fake()->boolean(40)) {
                Bookmark::create(['user_id' => $others->random()->user->id, 'post_id' => $post->id]);
            }

            if (fake()->boolean(20)) {
                PostShare::create([
                    'account_id' => $others->random()->id,
                    'post_id' => $post->id,
                    'share_text' => 'أنصح بمتابعة هذا المنشور.',
                ]);
            }

            // العدّادات المخزّنة تطابق البيانات الفعلية
            $post->forceFill([
                'likes_count' => $post->reacts()->count(),
                'comments_count' => $post->comments()->count(),
                'shares_count' => $post->shares()->count(),
                'views_count' => fake()->numberBetween(20, 600),
            ])->saveQuietly();
        }

        foreach ($companies as $company) {
            foreach ($users->random(fake()->numberBetween(2, 7)) as $account) {
                Follow::firstOrCreate(['company_id' => $company->id, 'account_id' => $account->id]);
            }

            $company->update(['followers_count' => $company->follows()->count()]);
        }
    }
}
