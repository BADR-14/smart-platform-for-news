<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminVerificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Company\CompanyDashboardController;
use App\Http\Controllers\Api\Company\CompanyManagementController;
use App\Http\Controllers\Api\Company\CompanyPlanController;
use App\Http\Controllers\Api\Company\FeaturedPostController;
use App\Http\Controllers\Api\Company\StripeWebhookController;
use App\Http\Controllers\Api\Company\WalletController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PlanManagementController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostShareController;
use App\Http\Controllers\Api\PostsManagementController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReactController;
use App\Http\Controllers\Api\Report\AdminReportController;
use App\Http\Controllers\Api\Report\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1) المصادقة — مسارات عامة عليها حد لعدد المحاولات
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/auth/google', 'googleLogin');

        // الأدمن: دخول بالرابط السحري فقط (لا يُقبل في /login)
        Route::post('/admin/auth/magic-link/verify', 'verifyMagicLink');
    });

    Route::middleware('throttle:otp')->group(function () {
        Route::post('/verify-otp', 'verifyOtp');
        Route::post('/resend-otp', 'resendOtp');
        Route::post('/admin/auth/magic-link', 'sendMagicLink');

        // استعادة كلمة المرور: رابط (ويب) أو رمز OTP (موبايل)
        Route::post('/password/email', 'sendResetLink');
        Route::post('/password/otp', 'sendResetCode');
        Route::post('/password/otp/verify', 'verifyResetCode');
        Route::post('/password/reset', 'resetPassword');
    });
});

// Stripe Webhook (موثّق بتوقيع Stripe وليس بالتوكن)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

// عرض وثيقة توثيق للأدمن عبر رابط موقّع مؤقت (يعمل داخل <img> بدون ترويسة Authorization)
Route::get('/admin/verification/documents/{document}', [AdminVerificationController::class, 'document'])
    ->middleware('signed')
    ->name('admin.verification.document');

/*
|--------------------------------------------------------------------------
| 2) مسارات عامة للزائر — إذا أُرسل توكن صالح تُرجَع حالة المشاهد أيضاً
|--------------------------------------------------------------------------
*/
Route::middleware('auth.optional')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/tags', [CategoryController::class, 'tags']);
    Route::get('/react-types', [ReactController::class, 'types']);

    Route::controller(PostController::class)->group(function () {
        Route::get('/posts', 'index')->name('posts.index');
        Route::get('/posts/featured', 'featured')->name('posts.featured');
        Route::get('/posts/{post}', 'show')->whereNumber('post')->name('posts.show');
        Route::post('/posts/{post}/view', 'recordView')->whereNumber('post')->name('posts.view');
        Route::get('/accounts/{account}/posts', 'accountPosts')->name('accounts.posts');
    });

    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments.index');
    Route::get('/posts/{post}/comments/{comment}/replies', [CommentController::class, 'replies'])->name('posts.comments.replies');
    Route::get('/posts/{post}/reviews', [ReviewController::class, 'index'])->name('posts.reviews.index');

    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::controller(CompanyController::class)->prefix('companies')->group(function () {
        Route::get('/', 'index');
        Route::get('/{company}', 'show');
        Route::get('/{company}/posts', 'posts');
    });
});

/*
|--------------------------------------------------------------------------
| 3) مسارات تحتاج تسجيل دخول
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ---- الحساب ----
    Route::controller(AuthController::class)->group(function () {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
        Route::post('/fcm-token', 'updateFcmToken');
    });

    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show')->name('profile.show');
        Route::patch('/', 'update')->name('profile.update');
        Route::post('/avatar', 'updateAvatar')->name('profile.avatar.update');
        Route::get('/reviews', 'reviews')->name('profile.reviews');
        Route::get('/bookmarks', 'bookmarks')->name('profile.bookmarks');
        Route::get('/verification-status', 'verificationStatus')->name('profile.verification');
    });

    Route::prefix('settings')->controller(SettingsController::class)->group(function () {
        Route::post('/password', 'changePassword')->name('settings.password');
        Route::delete('/account', 'destroyAccount')->name('settings.account.destroy');
    });

    Route::prefix('notification-settings')->controller(NotificationPreferenceController::class)->group(function () {
        Route::get('/', 'show')->name('notificationSettings.show');
        Route::patch('/', 'update')->name('notificationSettings.update');
    });

    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('notifications.index');
        Route::get('/unread-count', 'unreadCount')->name('notifications.unreadCount');
        Route::patch('/read-all', 'markAllAsRead')->name('notifications.readAll');
        Route::patch('/{notification}/read', 'markAsRead')->name('notifications.read');
        Route::delete('/{notification}', 'destroy')->name('notifications.destroy');
    });

    // ---- المنشورات ----
    Route::prefix('posts')->group(function () {
        Route::controller(PostController::class)->group(function () {
            Route::get('/my-posts', 'myPosts')->name('posts.mine');

            // النشر للحسابات الموثّقة فقط
            Route::post('/', 'store')->middleware('verified.account')->name('posts.store');

            // POST مع _method=PATCH لدعم رفع الملفات (multipart) عند التعديل
            Route::match(['patch', 'post'], '/{post}', 'update')->whereNumber('post')->name('posts.update');
            Route::delete('/{post}', 'destroy')->whereNumber('post')->name('posts.destroy');
        });

        Route::post('/{post}/react', [ReactController::class, 'react'])->name('posts.react');
        Route::post('/{post}/bookmark', [BookmarkController::class, 'toggleBookmark'])->name('posts.bookmark');
        Route::post('/{post}/reviews', [ReviewController::class, 'store'])->name('posts.reviews.store');

        Route::prefix('/{post}/comments')->controller(CommentController::class)->group(function () {
            Route::post('/', 'store')->name('posts.comments.store');
            Route::post('/{comment}', 'reply')->name('posts.comments.reply');
            Route::delete('/{comment}', 'destroy')->name('posts.comments.destroy');
        });
    });

    // المشاركة (إعادة النشر مع نص اختياري)
    Route::prefix('post-shares')->controller(PostShareController::class)->group(function () {
        Route::post('/{post}', 'store')->name('post-shares.store');
        Route::patch('/{postShare}', 'update')->name('post-shares.update');
        Route::delete('/{postShare}', 'destroy')->name('post-shares.destroy');
    });

    // منشورات الشركات التي أتابعها
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');

    // البلاغات: على منشور أو على حساب
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:20,1')->name('reports.store');

    // ---- التوثيق ----
    Route::post('/verification/user', [VerificationController::class, 'userVerification'])->name('verification.user');

    // ---- الشركات ----
    Route::prefix('companies')->controller(CompanyController::class)->group(function () {
        Route::post('/', 'create')->name('companies.create');
        Route::post('/{company}/follow', 'follow')->name('companies.follow');
        Route::delete('/{company}/follow', 'unfollow')->name('companies.unfollow');
    });

    /*
    |----------------------------------------------------------------------
    | 4) لوحة الشركة — حسابات الشركات الموافق عليها فقط
    |----------------------------------------------------------------------
    */
    Route::middleware('role:Company')->prefix('company')->group(function () {
        Route::controller(CompanyDashboardController::class)->group(function () {
            Route::get('/dashboard', 'overview');
            Route::get('/profile', 'profile');
            Route::post('/profile', 'updateProfile');
        });

        Route::prefix('wallet')->controller(WalletController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/topup', 'initiateTopUp');
            Route::post('/topup/confirm', 'confirmTopUp');
            Route::post('/withdraw', 'withdraw');
            Route::get('/transactions', 'transactions');
        });

        Route::controller(CompanyPlanController::class)->group(function () {
            Route::get('/plans', 'index');
            Route::post('/plans/{plan}/subscribe', 'subscribe');
            Route::get('/subscriptions', 'subscriptions');
        });

        Route::controller(FeaturedPostController::class)->group(function () {
            Route::get('/featured', 'index');
            Route::get('/featured/options', 'options');
            Route::post('/posts/{post}/feature', 'store');
        });
    });

    /*
    |----------------------------------------------------------------------
    | 5) لوحة الأدمن
    |----------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/overview', [DashboardController::class, 'index']);
        Route::get('/analytics', [DashboardController::class, 'analytics']);

        Route::prefix('users')->controller(UserManagementController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/{account}/ban', 'ban');
            Route::post('/{account}/unban', 'unban');
            Route::delete('/{account}', 'destroy');
        });

        Route::prefix('companies')->controller(CompanyManagementController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/{company}/ban', 'ban');
            Route::post('/{company}/unban', 'unban');
        });

        Route::prefix('posts')->controller(PostsManagementController::class)->group(function () {
            Route::get('/', 'index');
            Route::patch('/{post}/status', 'updateStatus');
            Route::post('/{post}/toggle-archive', 'toggleArchiveStatus');
            Route::delete('/{post}', 'destroy');
        });

        Route::prefix('reports')->controller(AdminReportController::class)->group(function () {
            Route::get('/', 'index');
            Route::patch('/{report}/resolve', 'resolve');
            Route::patch('/{report}/dismiss', 'dismiss');
        });

        Route::controller(PlanManagementController::class)->group(function () {
            Route::get('/plans', 'index');
            Route::post('/plans', 'store');
            Route::put('/plans/{plan}', 'update');
            Route::delete('/plans/{plan}', 'destroy');
            Route::patch('/plans/{plan}/toggle-status', 'toggleStatus');
            Route::get('/subscriptions', 'subscriptions');
            Route::get('/featured-posts', 'featuredPosts');
            Route::patch('/platform-settings', 'updateSettings');
        });

        Route::prefix('verification')->controller(AdminVerificationController::class)->group(function () {
            Route::get('/', 'index')->name('admin.verification.index');
            Route::get('/accounts/{account}/history', 'accountHistory')->name('admin.verification.history');
            Route::get('/{verificationRequest}', 'show')->whereNumber('verificationRequest')->name('admin.verification.show');
            Route::post('/{verificationRequest}/review', 'review')->name('admin.verification.review');
        });

        Route::controller(CategoryController::class)->group(function () {
            Route::get('/categories', 'adminIndex');
            Route::post('/categories', 'store');
            Route::put('/categories/{category}', 'update');
            Route::delete('/categories/{category}', 'destroy');
            Route::get('/tags', 'adminTags');
            Route::delete('/tags/{tag}', 'destroyTag');
        });
    });
});
