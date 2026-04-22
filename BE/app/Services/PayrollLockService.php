<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Models\SalaryPeriod;

class PayrollLockService
{
    public function __construct(
        private readonly SalaryPeriod $salaryPeriods = new SalaryPeriod(),
        private readonly WorkflowTransitionGuardService $workflowTransitionGuard = new WorkflowTransitionGuardService(),
    ) {
    }

    public function findLockedPeriodWithinRange(string $fromDate, string $toDate): ?array
    {
        $config = require base_path('config/hrm_workflow.php');
        $lockPolicies = is_array($config['lock_policies'] ?? null) ? $config['lock_policies'] : [];
        if (empty($lockPolicies['block_schedule_mutation_when_payroll_locked'])) {
            return null;
        }

        return $this->salaryPeriods->findLockedWithinRange(
            $fromDate,
            $toDate,
            $this->workflowTransitionGuard->payrollTerminalStatuses()
        );
    }

    public function assertRangeUnlocked(string $fromDate, string $toDate, string $operation): void
    {
        if ($toDate < $fromDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $lockedPeriod = $this->findLockedPeriodWithinRange($fromDate, $toDate);
        if ($lockedPeriod === null) {
            return;
        }

        $status = strtoupper((string) ($lockedPeriod['status'] ?? 'LOCKED'));
        $periodCode = (string) ($lockedPeriod['period_code'] ?? '');
        $label = $periodCode !== '' ? ($periodCode . ' (' . $status . ')') : $status;

        throw new HttpException(
            sprintf('Cannot %s because payroll period %s overlaps %s to %s.', $operation, $label, $fromDate, $toDate),
            422,
            'validation_error'
        );
    }
}
