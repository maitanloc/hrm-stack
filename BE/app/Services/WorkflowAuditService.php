<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\WorkflowAuditLog;

class WorkflowAuditService
{
    public function __construct(
        private readonly WorkflowAuditLog $auditLogs = new WorkflowAuditLog(),
    ) {
    }

    public function recordTransition(
        string $entityType,
        string $entityRef,
        string $fromState,
        string $toState,
        int $actorId,
        array $context = []
    ): ?int {
        return $this->safeWrite([
            'entity_type' => strtoupper(trim($entityType)),
            'entity_ref' => trim($entityRef),
            'action_type' => 'STATE_TRANSITION',
            'from_state' => $fromState,
            'to_state' => $toState,
            'actor_id' => $actorId > 0 ? $actorId : null,
            'context_json' => $this->encodeContext($context),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recordEvent(
        string $entityType,
        string $entityRef,
        string $actionType,
        int $actorId,
        array $context = []
    ): ?int {
        return $this->safeWrite([
            'entity_type' => strtoupper(trim($entityType)),
            'entity_ref' => trim($entityRef),
            'action_type' => strtoupper(trim($actionType)),
            'from_state' => null,
            'to_state' => null,
            'actor_id' => $actorId > 0 ? $actorId : null,
            'context_json' => $this->encodeContext($context),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function safeWrite(array $payload): ?int
    {
        try {
            return $this->auditLogs->create($payload);
        } catch (\Throwable) {
            return null;
        }
    }

    private function encodeContext(array $context): string
    {
        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE);
        return is_string($encoded) ? $encoded : '{}';
    }
}

