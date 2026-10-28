<?php

namespace App\Http\Resources\Admin;

use App\Enums\Report\ReportAction;
use App\Models\Account;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target = $this->reportable;

        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reported_item' => match (true) {
                $target instanceof Post => $target->title,
                $target instanceof Account => $target->name,
                default => 'محتوى محذوف',
            },
            // حالة الهدف الحالية: حالة المنشور، أو active/blocked للحساب
            'reported_item_status' => match (true) {
                $target instanceof Post => $target->status,
                $target instanceof Account => $target->is_active ? 'active' : 'blocked',
                default => 'deleted',
            },
            'reporter' => $this->reporterAccount ? [
                'id' => $this->reporterAccount->id,
                'name' => $this->reporterAccount->name,
            ] : null,
            'reason' => $this->reason,
            'status' => $this->status,
            'action_taken' => $this->action_taken,
            'available_actions' => ReportAction::forType($this->reportable_type),
            'admin_notes' => $this->admin_notes,
            'resolved_by' => $this->resolver?->name,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
        ];
    }
}
