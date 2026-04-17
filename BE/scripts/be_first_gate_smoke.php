<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Services\WorkflowRuleEngineService;
use App\Services\WorkflowTransitionGuardService;

$ruleEngine = new WorkflowRuleEngineService();
$transitionGuard = new WorkflowTransitionGuardService();

$checks = [];
$failed = 0;

/**
 * @param callable():bool $fn
 */
function run_check(array &$checks, int &$failed, string $name, callable $fn): void
{
    try {
        $ok = $fn();
        if (!$ok) {
            $failed++;
        }
        $checks[] = [
            'name' => $name,
            'ok' => $ok,
            'message' => $ok ? 'PASS' : 'FAIL',
        ];
    } catch (\Throwable $exception) {
        $failed++;
        $checks[] = [
            'name' => $name,
            'ok' => false,
            'message' => 'ERROR: ' . $exception->getMessage(),
        ];
    }
}

run_check($checks, $failed, 'Request: DRAFT -> SUBMITTED allowed', static function () use ($transitionGuard): bool {
    $result = $transitionGuard->assertTransitionAllowed('request', 'NHÁP', 'CHỜ_DUYỆT');
    return !empty($result['allowed']);
});

run_check($checks, $failed, 'Request: SUBMITTED -> APPROVED blocked', static function () use ($transitionGuard): bool {
    try {
        $transitionGuard->assertTransitionAllowed('request', 'CHỜ_DUYỆT', 'ĐÃ_DUYỆT');
        return false;
    } catch (\Throwable) {
        return true;
    }
});

run_check($checks, $failed, 'Request: SUBMITTED -> PENDING_APPROVAL allowed', static function () use ($transitionGuard): bool {
    $result = $transitionGuard->assertTransitionAllowed('request', 'CHỜ_DUYỆT', 'ĐANG_XỬ_LÝ');
    return !empty($result['allowed']);
});

run_check($checks, $failed, 'Payroll: OPEN -> REVIEWING allowed', static function () use ($transitionGuard): bool {
    $result = $transitionGuard->assertTransitionAllowed('payroll', 'OPEN', 'REVIEWING');
    return !empty($result['allowed']);
});

run_check($checks, $failed, 'Payroll: OPEN -> CLOSED blocked', static function () use ($transitionGuard): bool {
    try {
        $transitionGuard->assertTransitionAllowed('payroll', 'OPEN', 'CLOSED');
        return false;
    } catch (\Throwable) {
        return true;
    }
});

run_check($checks, $failed, 'Payroll terminal statuses include PAID and CLOSED', static function () use ($transitionGuard): bool {
    $statuses = $transitionGuard->payrollTerminalStatuses();
    return in_array('PAID', $statuses, true) && in_array('CLOSED', $statuses, true);
});

run_check($checks, $failed, 'Schedule publish readiness blocks unassigned rows', static function () use ($ruleEngine): bool {
    $facts = [[
        'employee_code' => 'NV0001',
        'work_date' => '2026-04-17',
        'has_shift' => false,
        'has_approved_off_reason' => false,
        'has_leave_conflict' => false,
        'has_overtime_without_shift' => false,
    ]];
    $result = $ruleEngine->evaluateSchedulePublishReadiness($facts, false);
    return empty($result['ready']) && (($result['summary']['blocker_count'] ?? 0) >= 1);
});

run_check($checks, $failed, 'Strict mode blocks warning-only publish', static function () use ($ruleEngine): bool {
    $facts = [[
        'employee_code' => 'NV0002',
        'work_date' => '2026-04-17',
        'has_shift' => true,
        'has_approved_off_reason' => true,
        'has_leave_conflict' => true,
        'has_overtime_without_shift' => false,
    ]];
    $result = $ruleEngine->evaluateSchedulePublishReadiness($facts, true);
    return empty($result['ready']) && (($result['summary']['warning_count'] ?? 0) >= 1);
});

echo "BE-first workflow gate smoke test\n";
echo "================================\n";
foreach ($checks as $check) {
    $prefix = $check['ok'] ? '[PASS]' : '[FAIL]';
    echo $prefix . ' ' . $check['name'] . "\n";
    if (!$check['ok']) {
        echo '  -> ' . $check['message'] . "\n";
    }
}

echo "--------------------------------\n";
echo 'Total: ' . count($checks) . ', Failed: ' . $failed . "\n";

exit($failed === 0 ? 0 : 1);

