<?php

namespace App\Http\Resources\Verification;

use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin VerificationRequest
 */
class VerificationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $account = $this->account;
        $company = $this->isForCompany() ? $account?->company : null;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'confidence_score' => $this->confidence_score,
            'rejection_reasons' => $this->rejection_reasons ?? [],
            'submitted_at' => $this->created_at,
            'reviewed_at' => $this->reviewed_at,

            'account' => $account ? [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'avatar' => $account->avatarUrl(),
                'account_type' => $account->role()->slug(),
                'national_id' => $account->user?->national_id,
                'linkedin_url' => $account->user?->linkedin_url,
            ] : null,

            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logoUrl(),
                'website_url' => $company->website_url,
                'category' => $company->category?->name,
                'phone' => $company->phone,
                'address' => $company->address,
                'description' => $company->description,
                'company_registration_number' => $company->company_registration_number,
                'tax_id' => $company->tax_id,
            ] : null,

            'reviewed_by' => $this->reviewer ? ['name' => $this->reviewer->account?->name] : null,

            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($document) => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                // رابط موقّع صالح 30 دقيقة — يعمل داخل <img> دون ترويسة Authorization
                'file_url' => URL::temporarySignedRoute(
                    'admin.verification.document',
                    now()->addMinutes(30),
                    ['document' => $document->id],
                ),
                'is_image' => (bool) preg_match('/\.(jpe?g|png|webp)$/i', $document->file_url),
                'extracted_text' => $document->extracted_text,
            ])->values()),
        ];
    }
}
