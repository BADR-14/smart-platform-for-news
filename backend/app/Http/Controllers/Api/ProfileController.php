<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ReviewResource;
use App\Services\BookmarkService;
use App\Services\ProfileService;
use App\Services\Verifications\VerificationStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly BookmarkService $bookmarkService,
        private readonly VerificationStatusService $verificationStatusService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->ok(new ProfileResource($this->profileService->getProfile($request->user())));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $account = $this->profileService->updateProfile($request->user(), $request->validated());

        return $this->ok(new ProfileResource($account), 'تم تحديث الملف الشخصي.');
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $account = $this->profileService->updateAvatar($request->user(), $request->file('avatar'));

        return $this->ok(['avatar' => $account->avatarUrl()], 'تم تحديث الصورة الشخصية.');
    }

    public function reviews(Request $request): JsonResponse
    {
        return $this->paginated($this->profileService->getReviews($request->user()), ReviewResource::class);
    }

    public function bookmarks(Request $request): JsonResponse
    {
        return $this->paginated($this->bookmarkService->getBookmarkedPosts($request->user()), PostResource::class);
    }

    public function verificationStatus(Request $request): JsonResponse
    {
        return $this->ok($this->verificationStatusService->forAccount($request->user()));
    }
}
