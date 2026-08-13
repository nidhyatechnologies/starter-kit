<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RecordAuditEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(string $event, ?User $subject = null, array $context = []): void
    {
        AuditLog::query()->create([
            'actor_id' => Auth::id(),
            'subject_id' => $subject?->id,
            'event' => $event,
            'context' => $context,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
