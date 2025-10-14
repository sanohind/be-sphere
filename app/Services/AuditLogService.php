<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public static function log(
        AuditAction $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): void {
        $request = $request ?? request();

        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action->value,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}