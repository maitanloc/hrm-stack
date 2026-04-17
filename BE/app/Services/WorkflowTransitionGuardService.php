<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;

class WorkflowTransitionGuardService
{
    private array $stateMappings;
    private array $lockPolicies;

    public function __construct(
        private readonly WorkflowCatalogService $catalogService = new WorkflowCatalogService(),
        private readonly WorkflowRuleEngineService $ruleEngine = new WorkflowRuleEngineService(),
    ) {
        $catalog = $this->catalogService->fullCatalog();
        $this->stateMappings = is_array($catalog['state_mappings'] ?? null)
            ? $catalog['state_mappings']
            : [];
        $this->lockPolicies = is_array($catalog['lock_policies'] ?? null)
            ? $catalog['lock_policies']
            : [];
    }

    public function assertTransitionAllowed(
        string $entity,
        string $currentDbState,
        string $targetDbState,
        array $context = []
    ): array {
        $fromCanonical = $this->dbToCanonicalState($entity, $currentDbState);
        $toCanonical = $this->dbToCanonicalState($entity, $targetDbState);

        $allowNoop = !array_key_exists('allow_noop', $context) || !empty($context['allow_noop']);
        if ($allowNoop && $fromCanonical === $toCanonical) {
            return [
                'entity' => strtolower(trim($entity)),
                'from' => $fromCanonical,
                'to' => $toCanonical,
                'allowed' => true,
                'allowed_targets' => [$toCanonical],
                'rule_code' => 'WF_NOOP_TRANSITION',
                'reason' => null,
                'from_db_state' => $currentDbState,
                'to_db_state' => $targetDbState,
            ];
        }

        $result = $this->ruleEngine->validateTransition($entity, $fromCanonical, $toCanonical, $context);
        $result['from_db_state'] = $currentDbState;
        $result['to_db_state'] = $targetDbState;
        if (!empty($result['allowed'])) {
            return $result;
        }

        $message = (string) ($result['reason'] ?? 'Workflow transition is not allowed.');
        throw new HttpException($message, 422, 'validation_error');
    }

    public function payrollTerminalStatuses(): array
    {
        $statuses = $this->lockPolicies['payroll_terminal_statuses'] ?? [];
        if (!is_array($statuses)) {
            return ['PAID', 'CLOSED'];
        }

        $normalized = [];
        foreach ($statuses as $status) {
            $normalized[] = $this->normalizeState((string) $status);
        }
        return array_values(array_unique(array_filter($normalized, static fn(string $value): bool => $value !== '')));
    }

    public function isPayrollTerminalStatus(?string $status): bool
    {
        if ($status === null || $status === '') {
            return false;
        }

        return in_array($this->normalizeState($status), $this->payrollTerminalStatuses(), true);
    }

    private function dbToCanonicalState(string $entity, string $dbState): string
    {
        $entityKey = strtolower(trim($entity));
        $entityMappings = $this->stateMappings[$entityKey]['db_to_canonical'] ?? [];
        if (!is_array($entityMappings)) {
            return $this->normalizeState($dbState);
        }

        $normalizedInput = $this->normalizeState($dbState);
        foreach ($entityMappings as $source => $target) {
            if ($this->normalizeState((string) $source) !== $normalizedInput) {
                continue;
            }
            return $this->normalizeState((string) $target);
        }

        return $normalizedInput;
    }

    private function normalizeState(string $state): string
    {
        $normalized = strtoupper(trim($state));
        return str_replace(' ', '_', $normalized);
    }
}

