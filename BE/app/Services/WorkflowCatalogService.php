<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;

class WorkflowCatalogService
{
    private array $catalog;

    public function __construct(?array $catalog = null)
    {
        $this->catalog = $catalog ?? $this->loadCatalog();
    }

    public function overview(): array
    {
        return [
            'version' => $this->catalog['version'] ?? null,
            'principle' => $this->catalog['principle'] ?? 'BE_FIRST',
            'summary_chain' => $this->catalog['summary_chain'] ?? [],
            'workflow_chain' => $this->catalog['workflow_chain'] ?? [],
            'backend_phase_priority' => $this->catalog['backend_phase_priority'] ?? [],
        ];
    }

    public function fullCatalog(): array
    {
        return $this->catalog;
    }

    public function section(string $section): array
    {
        $normalized = $this->normalizeKey($section);
        return match ($normalized) {
            'states', 'entity_states' => $this->catalog['entity_states'] ?? [],
            'rules', 'rule_catalog' => $this->catalog['rule_catalog'] ?? [],
            'roles', 'role_matrix' => $this->catalog['role_matrix'] ?? [],
            'domain', 'domain_model' => $this->catalog['domain_model'] ?? [],
            'permission', 'permission_matrix' => $this->catalog['permission_matrix'] ?? [],
            'mapping', 'state_mappings' => $this->catalog['state_mappings'] ?? [],
            'lock_policy', 'lock_policies' => $this->catalog['lock_policies'] ?? [],
            'phases', 'backend_phase_priority' => $this->catalog['backend_phase_priority'] ?? [],
            'checklist', 'be_gate_checklist' => $this->catalog['be_gate_checklist'] ?? [],
            'workflow_chain' => $this->catalog['workflow_chain'] ?? [],
            default => throw new HttpException('Unknown workflow catalog section', 422, 'validation_error'),
        };
    }

    public function transitions(string $entity): array
    {
        $entityState = $this->entityState($entity);
        return [
            'entity' => $this->normalizeKey($entity),
            'initial_state' => $entityState['initial_state'] ?? null,
            'terminal_states' => $entityState['terminal_states'] ?? [],
            'states' => $entityState['states'] ?? [],
            'transitions' => $entityState['transitions'] ?? [],
        ];
    }

    public function entityState(string $entity): array
    {
        $key = $this->normalizeKey($entity);
        $states = $this->catalog['entity_states'] ?? [];
        if (!isset($states[$key]) || !is_array($states[$key])) {
            throw new HttpException('Unknown workflow entity', 422, 'validation_error');
        }

        return $states[$key];
    }

    public function rules(?string $module = null): array
    {
        $catalog = $this->catalog['rule_catalog'] ?? [];
        if ($module === null || $module === '') {
            return $catalog;
        }

        $key = $this->normalizeKey($module);
        if (!isset($catalog[$key]) || !is_array($catalog[$key])) {
            throw new HttpException('Unknown workflow rule module', 422, 'validation_error');
        }

        return $catalog[$key];
    }

    private function loadCatalog(): array
    {
        $path = base_path('config/hrm_workflow.php');
        if (!is_file($path)) {
            throw new HttpException('Workflow catalog is not configured', 500, 'server_error');
        }

        $catalog = require $path;
        if (!is_array($catalog)) {
            throw new HttpException('Workflow catalog must return an array', 500, 'server_error');
        }

        return $catalog;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim($value));
    }
}
