<?php

namespace App\Services;

use App\Enums\Post\PostStatus;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    public function changePassword(Account $account, string $currentPassword, string $newPassword): void
    {
        $credential = $account->authCredentials()->where('provider', 'local')->first();

        if (! $credential?->password_hash) {
            throw ValidationException::withMessages([
                'current_password' => ['هذا الحساب لا يملك كلمة مرور (تم إنشاؤه عبر Google).'],
            ]);
        }

        if (! Hash::check($currentPassword, $credential->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $credential->update(['password_hash' => Hash::make($newPassword)]);

        // نُبقي الجلسة الحالية ونُنهي باقي الجلسات على الأجهزة الأخرى
        $account->tokens()
            ->where('id', '!=', $account->currentAccessToken()?->id)
            ->delete();
    }

    public function destroyAccount(Account $account, ?string $password): void
    {
        $credential = $account->authCredentials()->where('provider', 'local')->first();

        // الحسابات التي لها كلمة مرور يجب أن تؤكدها قبل الحذف
        if ($credential?->password_hash && ! Hash::check((string) $password, $credential->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['كلمة المرور غير صحيحة.'],
            ]);
        }

        DB::transaction(function () use ($account) {
            // إخفاء منشورات الحساب من المنصة
            $account->posts()
                ->where('status', PostStatus::Published->value)
                ->update(['status' => PostStatus::Archived->value]);

            $account->tokens()->delete();

            // تحرير البريد حتى يستطيع صاحبه التسجيل به مجدداً لاحقاً
            $account->forceFill([
                'is_active' => false,
                'fcm_token' => null,
                'email' => "deleted-{$account->id}-{$account->email}",
            ])->save();

            $account->delete();
        });
    }
}
