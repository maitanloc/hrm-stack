<?php
declare(strict_types=1);

namespace App\Services;

class WorkflowRuleEngineService
{
    public function __construct(
        private readonly WorkflowCatalogService $catalogService = new WorkflowCatalogService(),
    ) {
    }

    public function validateTransition(string $entity, string $currentState, string $targetState, array $context = []): array
    {
        $definition = $this->catalogService->entityState($entity);
        $transitions = $definition['transitions'] ?? [];

        $from = $this->normalizeState($currentState);
        $to = $this->normalizeState($targetState);
        $allowedTargets = array_values(array_unique(array_map(
            [$this, 'normalizeState'],
            $transitions[$from] ?? []
        )));

        $isAllowed = in_array($to, $allowedTargets, true);
        $blockReason = null;
        $ruleCode = null;

        if ($this->isTerminalState($definition, $from) && $to !== $from) {
            $isAllowed = false;
            $blockReason = 'Current state is terminal and cannot be changed.';
            $ruleCode = 'WF_TERMINAL_STATE_IMMUTABLE';
        } elseif (!empty($context['locked']) && $to !== $from) {
            $isAllowed = false;
            $blockReason = 'Entity is locked by period policy.';
            $ruleCode = 'WF_LOCKED_ENTITY_BLOCK';
        } elseif (!$isAllowed) {
            $blockReason = 'Transition is not defined in workflow state machine.';
            $ruleCode = 'WF_INVALID_TRANSITION';
        }

        return [
            'entity' => strtolower(trim($entity)),
            'from' => $from,
            'to' => $to,
            'allowed' => $isAllowed,
            'allowed_targets' => $allowedTargets,
            'rule_code' => $ruleCode,
            'reason' => $blockReason,
        ];
    }

    public function evaluateSchedulePublishReadiness(array $facts, bool $strictMode = false): array
    {
        $unassignedExamples = [];
        $leaveConflictExamples = [];
        $otWithoutShiftExamples = [];

        foreach ($facts as $fact) {
            $employeeCode = (string) ($fact['employee_code'] ?? ('#' . (int) ($fact['employee_id'] ?? 0)));
            $workDate = (string) ($fact['work_date'] ?? '');
            $tag = trim($employeeCode . ' ' . $workDate);

            $hasShift = !empty($fact['has_shift']);
            $hasApprovedOffReason = !empty($fact['has_approved_off_reason']);
            $hasLeaveConflict = !empty($fact['has_leave_conflict']);
            $hasOvertimeWithoutShift = !empty($fact['has_overtime_without_shift']);

            if (!$hasShift && !$hasApprovedOffReason) {
                $unassignedExamples[] = $tag;
            }
            if ($hasLeaveConflict) {
                $leaveConflictExamples[] = $tag;
            }
            if ($hasOvertimeWithoutShift) {
                $otWithoutShiftExamples[] = $tag;
            }
        }

        $blockers = [];
        if ($unassignedExamples !== []) {
            $blockers[] = [
                'rule_code' => 'SCH_PUBLISH_BLOCK_UNASSIGNED',
                'severity' => 'BLOCKER',
                'message' => sprintf(
                    'Tuần chưa đủ điều kiện: Còn %d vị trí chưa được phân ca hoặc chưa có lý do nghỉ. (Vd: %s)',
                    count($unassignedExamples),
                    implode(', ', array_slice($unassignedExamples, 0, 3))
                ),
                'sample' => array_slice($unassignedExamples, 0, 10),
                'count' => count($unassignedExamples),
            ];
        }

        $warnings = [];
        if ($leaveConflictExamples !== []) {
            $warnings[] = [
                'rule_code' => 'SCH_WARN_LEAVE_CONFLICT',
                'severity' => 'WARNING',
                'message' => sprintf(
                    'Xung đột lịch: %d trường hợp trùng lịch ca và đơn nghỉ. (Vd: %s)',
                    count($leaveConflictExamples),
                    implode(', ', array_slice($leaveConflictExamples, 0, 3))
                ),
                'sample' => array_slice($leaveConflictExamples, 0, 10),
                'count' => count($leaveConflictExamples),
            ];
        }
        if ($otWithoutShiftExamples !== []) {
            $warnings[] = [
                'rule_code' => 'SCH_WARN_OT_WITHOUT_SHIFT',
                'severity' => 'WARNING',
                'message' => sprintf(
                    'Thiếu lịch nền: %d trường hợp có OT nhưng chưa có ca nền. (Vd: %s)',
                    count($otWithoutShiftExamples),
                    implode(', ', array_slice($otWithoutShiftExamples, 0, 3))
                ),
                'sample' => array_slice($otWithoutShiftExamples, 0, 10),
                'count' => count($otWithoutShiftExamples),
            ];
        }

        $ready = $blockers === [] && (!$strictMode || $warnings === []);

        return [
            'ready' => $ready,
            'strict_mode' => $strictMode,
            'summary' => [
                'fact_count' => count($facts),
                'blocker_count' => count($blockers),
                'warning_count' => count($warnings),
            ],
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    private function isTerminalState(array $definition, string $state): bool
    {
        $terminalStates = array_values(array_unique(array_map(
            [$this, 'normalizeState'],
            $definition['terminal_states'] ?? []
        )));
        return in_array($state, $terminalStates, true);
    }

    private function normalizeState(string $state): string
    {
        $normalized = strtoupper(trim($state));
        return str_replace(' ', '_', $normalized);
    }
}
