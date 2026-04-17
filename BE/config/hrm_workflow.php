<?php
declare(strict_types=1);

return [
    'version' => '2026-04-17',
    'principle' => 'BE_FIRST',
    'summary_chain' => [
        'MASTER_DATA',
        'SCHEDULING',
        'PUBLISH_SCHEDULE',
        'ACTUAL_ATTENDANCE',
        'REQUEST_WORKFLOW',
        'RECONCILIATION',
        'TIMESHEET_CALCULATION',
        'PAYROLL_CALCULATION',
        'PAYROLL_APPROVAL',
        'REPORTING',
    ],
    'workflow_chain' => [
        [
            'code' => 'MASTER_DATA',
            'name' => 'Du lieu nen HRM',
            'output' => 'Employee, org, contract, policy va permission da duoc chuan hoa',
        ],
        [
            'code' => 'SCHEDULING',
            'name' => 'Lap ke hoach nhan su / phan ca',
            'output' => 'Lich nhap theo phong ban, nhan vien, ngay',
        ],
        [
            'code' => 'PUBLISH_SCHEDULE',
            'name' => 'Trinh duyet va publish lich',
            'output' => 'Lich chinh thuc co version',
        ],
        [
            'code' => 'ACTUAL_ATTENDANCE',
            'name' => 'Cham cong thuc te',
            'output' => 'Log check-in/check-out tu thiet bi',
        ],
        [
            'code' => 'REQUEST_WORKFLOW',
            'name' => 'Don tu phat sinh',
            'output' => 'Nghi phep, OT, quen cham cong, doi ca, dieu chinh cong',
        ],
        [
            'code' => 'RECONCILIATION',
            'name' => 'Doi soat',
            'output' => 'Ghiep lich + cong + don + policy de phan loai sai lech',
        ],
        [
            'code' => 'TIMESHEET_CALCULATION',
            'name' => 'Tinh cong',
            'output' => 'Bang cong tam va bang cong chot',
        ],
        [
            'code' => 'PAYROLL_CALCULATION',
            'name' => 'Tinh luong',
            'output' => 'Bang luong gross/net va cac khoan',
        ],
        [
            'code' => 'PAYROLL_APPROVAL',
            'name' => 'Duyet payroll',
            'output' => 'Khoa ky luong',
        ],
        [
            'code' => 'REPORTING',
            'name' => 'Xuat ket qua',
            'output' => 'Phieu luong, bao cao cong/OT/vang mat/chi phi',
        ],
    ],
    'domain_model' => [
        [
            'bounded_context' => 'ORG_AND_EMPLOYEE',
            'entities' => ['Employee', 'Department', 'Position', 'Contract', 'EmploymentHistory'],
            'data_ownership' => 'HR',
        ],
        [
            'bounded_context' => 'SHIFT_AND_SCHEDULING',
            'entities' => ['ShiftType', 'ShiftSchedule', 'ShiftAssignment', 'ShiftAssignmentOverride', 'SchedulePublishLog', 'ShiftChangeLog'],
            'data_ownership' => 'MANAGER_HR',
        ],
        [
            'bounded_context' => 'REQUEST_AND_APPROVAL',
            'entities' => ['Request', 'LeaveRequest', 'OvertimeRequest', 'ApprovalStep'],
            'data_ownership' => 'MANAGER_HR',
        ],
        [
            'bounded_context' => 'ATTENDANCE_AND_TIMESHEET',
            'entities' => ['Attendance', 'AttendanceResult', 'AttendanceExceptionRequest'],
            'data_ownership' => 'MANAGER_HR',
        ],
        [
            'bounded_context' => 'PAYROLL',
            'entities' => ['SalaryPeriod', 'SalaryDetail', 'SalaryBreakdown', 'PayrollAdjustment'],
            'data_ownership' => 'PAYROLL',
        ],
        [
            'bounded_context' => 'GOVERNANCE',
            'entities' => ['WorkflowAuditLog', 'Role', 'Permission'],
            'data_ownership' => 'SYSTEM_ADMIN',
        ],
    ],
    'entity_states' => [
        'schedule' => [
            'initial_state' => 'DRAFT',
            'terminal_states' => ['LOCKED'],
            'states' => [
                'DRAFT',
                'SUBMITTED',
                'REVIEWED',
                'APPROVED',
                'PUBLISHED',
                'ADJUSTED_AFTER_PUBLISH',
                'LOCKED',
            ],
            'transitions' => [
                'DRAFT' => ['SUBMITTED', 'PUBLISHED'],
                'SUBMITTED' => ['DRAFT', 'REVIEWED', 'APPROVED', 'PUBLISHED'],
                'REVIEWED' => ['SUBMITTED', 'APPROVED', 'PUBLISHED'],
                'APPROVED' => ['REVIEWED', 'PUBLISHED'],
                'PUBLISHED' => ['ADJUSTED_AFTER_PUBLISH', 'LOCKED'],
                'ADJUSTED_AFTER_PUBLISH' => ['REVIEWED', 'PUBLISHED', 'LOCKED'],
                'LOCKED' => [],
            ],
        ],
        'request' => [
            'initial_state' => 'DRAFT',
            'terminal_states' => ['APPLIED_TO_ATTENDANCE', 'REJECTED', 'CANCELLED'],
            'states' => [
                'DRAFT',
                'SUBMITTED',
                'PENDING_APPROVAL',
                'APPROVED',
                'REJECTED',
                'CANCELLED',
                'APPLIED_TO_ATTENDANCE',
            ],
            'transitions' => [
                'DRAFT' => ['SUBMITTED', 'CANCELLED'],
                'SUBMITTED' => ['PENDING_APPROVAL', 'CANCELLED'],
                'PENDING_APPROVAL' => ['APPROVED', 'REJECTED', 'CANCELLED'],
                'APPROVED' => ['APPLIED_TO_ATTENDANCE'],
                'REJECTED' => [],
                'CANCELLED' => [],
                'APPLIED_TO_ATTENDANCE' => [],
            ],
        ],
        'timesheet' => [
            'initial_state' => 'RAW',
            'terminal_states' => ['LOCKED_FOR_PAYROLL'],
            'states' => [
                'RAW',
                'CALCULATED',
                'REVIEWED',
                'CONFIRMED',
                'LOCKED_FOR_PAYROLL',
            ],
            'transitions' => [
                'RAW' => ['CALCULATED'],
                'CALCULATED' => ['RAW', 'REVIEWED'],
                'REVIEWED' => ['CALCULATED', 'CONFIRMED'],
                'CONFIRMED' => ['LOCKED_FOR_PAYROLL', 'REVIEWED'],
                'LOCKED_FOR_PAYROLL' => [],
            ],
        ],
        'payroll' => [
            'initial_state' => 'DRAFT_PAYROLL',
            'terminal_states' => ['CLOSED'],
            'states' => [
                'DRAFT_PAYROLL',
                'REVIEWED',
                'APPROVED',
                'PAID',
                'CLOSED',
            ],
            'transitions' => [
                'DRAFT_PAYROLL' => ['REVIEWED'],
                'REVIEWED' => ['DRAFT_PAYROLL', 'APPROVED'],
                'APPROVED' => ['PAID'],
                'PAID' => ['CLOSED'],
                'CLOSED' => [],
            ],
        ],
    ],
    'rule_catalog' => [
        'schedule_planning' => [
            [
                'code' => 'SCH_PUBLISH_BLOCK_UNASSIGNED',
                'severity' => 'BLOCKER',
                'description' => 'Khong cho publish neu con nhan su chua duoc phan ca va khong co ly do nghi hop le.',
            ],
            [
                'code' => 'SCH_WARN_LEAVE_CONFLICT',
                'severity' => 'WARNING',
                'description' => 'Canh bao khi co ca lam trung voi don nghi da duyet.',
            ],
            [
                'code' => 'SCH_WARN_OT_WITHOUT_SHIFT',
                'severity' => 'WARNING',
                'description' => 'Canh bao OT phat sinh khi khong co ca nen.',
            ],
        ],
        'attendance' => [
            [
                'code' => 'ATT_GRACE_PERIOD',
                'severity' => 'POLICY',
                'description' => 'Cho phep grace period cho check-in/check-out.',
            ],
            [
                'code' => 'ATT_NIGHT_SHIFT',
                'severity' => 'POLICY',
                'description' => 'Xu ly ca qua dem va tinh sang ngay tiep theo.',
            ],
            [
                'code' => 'ATT_MISSING_PAIR',
                'severity' => 'POLICY',
                'description' => 'Xu ly thieu check-in/check-out.',
            ],
        ],
        'timesheet' => [
            [
                'code' => 'TS_HALF_DAY',
                'severity' => 'POLICY',
                'description' => 'Quy tac quy doi nua cong/ca cong/theo gio.',
            ],
            [
                'code' => 'TS_OT_ROUNDING',
                'severity' => 'POLICY',
                'description' => 'Lam tron OT theo rule.',
            ],
            [
                'code' => 'TS_HOLIDAY_FACTOR',
                'severity' => 'POLICY',
                'description' => 'He so ngay le, ngay nghi, chu nhat.',
            ],
        ],
        'payroll' => [
            [
                'code' => 'PR_APPROVAL_REQUIRED_FOR_OT',
                'severity' => 'POLICY',
                'description' => 'Chi tinh OT da duoc phe duyet.',
            ],
            [
                'code' => 'PR_LOCK_PREVENT_RETROACTIVE_MUTATION',
                'severity' => 'BLOCKER',
                'description' => 'Khi ky payroll da lock, khong cho sua du lieu tac dong net salary neu khong co rollback policy.',
            ],
        ],
    ],
    'role_matrix' => [
        [
            'role' => 'EMPLOYEE',
            'responsibilities' => ['View schedule', 'Check-in/check-out', 'Submit request'],
        ],
        [
            'role' => 'MANAGER',
            'responsibilities' => ['Planning schedule', 'Approve level 1 request', 'Review abnormal attendance'],
        ],
        [
            'role' => 'HR',
            'responsibilities' => ['Validate policy compliance', 'Publish schedule', 'Confirm timesheet'],
        ],
        [
            'role' => 'PAYROLL',
            'responsibilities' => ['Calculate payroll', 'Review deductions/allowances', 'Close payroll period'],
        ],
        [
            'role' => 'SYSTEM_ADMIN',
            'responsibilities' => ['Configure rules', 'Configure permission', 'Audit log and integration'],
        ],
    ],
    'permission_matrix' => [
        [
            'workflow' => 'schedule',
            'state' => 'DRAFT',
            'allowed_actions' => [
                'MANAGER' => ['ASSIGN_SHIFT', 'OVERRIDE_SHIFT', 'SUBMIT_OR_PUBLISH'],
                'HR' => ['ASSIGN_SHIFT', 'OVERRIDE_SHIFT', 'SUBMIT_OR_PUBLISH'],
            ],
        ],
        [
            'workflow' => 'schedule',
            'state' => 'PUBLISHED',
            'allowed_actions' => [
                'MANAGER' => ['VIEW_ONLY', 'REQUEST_ADJUSTMENT'],
                'HR' => ['ADJUST_WITH_AUDIT', 'LOCK'],
            ],
        ],
        [
            'workflow' => 'request',
            'state' => 'PENDING_APPROVAL',
            'allowed_actions' => [
                'MANAGER' => ['APPROVE', 'REJECT'],
                'HR' => ['APPROVE', 'REJECT'],
            ],
        ],
        [
            'workflow' => 'payroll',
            'state' => 'APPROVED',
            'allowed_actions' => [
                'PAYROLL' => ['MARK_PAID'],
                'SYSTEM_ADMIN' => ['OVERRIDE_WITH_AUDIT'],
            ],
        ],
        [
            'workflow' => 'payroll',
            'state' => 'CLOSED',
            'allowed_actions' => [
                'PAYROLL' => ['VIEW_ONLY'],
                'SYSTEM_ADMIN' => ['ROLLBACK_WITH_AUDIT'],
            ],
        ],
    ],
    'state_mappings' => [
        'request' => [
            'db_to_canonical' => [
                'NHÁP' => 'DRAFT',
                'CHỜ_DUYỆT' => 'SUBMITTED',
                'ĐANG_XỬ_LÝ' => 'PENDING_APPROVAL',
                'CHỜ_GIÁM_ĐỐC_DUYỆT' => 'PENDING_APPROVAL',
                'CHỜ_XÁC_NHẬN_HR' => 'PENDING_APPROVAL',
                'ĐÃ_DUYỆT' => 'APPROVED',
                'TỪ_CHỐI' => 'REJECTED',
                'ĐÃ_HỦY' => 'CANCELLED',
                'HOÀN_THÀNH' => 'APPLIED_TO_ATTENDANCE',
            ],
        ],
        'payroll' => [
            'db_to_canonical' => [
                'OPEN' => 'DRAFT_PAYROLL',
                'CALCULATING' => 'DRAFT_PAYROLL',
                'REVIEWING' => 'REVIEWED',
                'APPROVED' => 'APPROVED',
                'PAID' => 'PAID',
                'CLOSED' => 'CLOSED',
            ],
        ],
        'schedule' => [
            'db_to_canonical' => [
                'DRAFT' => 'DRAFT',
                'PUBLISHED' => 'PUBLISHED',
                'ADJUSTED_AFTER_PUBLISH' => 'ADJUSTED_AFTER_PUBLISH',
                'LOCKED' => 'LOCKED',
            ],
        ],
    ],
    'lock_policies' => [
        'payroll_terminal_statuses' => ['PAID', 'CLOSED'],
        'block_schedule_mutation_when_payroll_locked' => true,
        'block_payroll_mutation_when_closed' => true,
    ],
    'backend_phase_priority' => [
        [
            'phase' => 'PHASE_1',
            'scope' => [
                'Org and employee structure',
                'Shift definition and scheduling assignment',
                'Approval workflow',
                'Leave request foundation',
            ],
        ],
        [
            'phase' => 'PHASE_2',
            'scope' => [
                'Attendance logs and reconciliation',
                'OT request',
                'Time adjustment',
            ],
        ],
        [
            'phase' => 'PHASE_3',
            'scope' => [
                'Timesheet calculation',
                'Policy engine',
                'Payroll hooks',
            ],
        ],
        [
            'phase' => 'PHASE_4',
            'scope' => [
                'Reports and dashboard',
                'Notification optimization',
                'Auto scheduling improvements',
            ],
        ],
    ],
    'be_gate_checklist' => [
        'Entity model',
        'Workflow states',
        'Role permission matrix',
        'Blocking vs warning rule catalog',
        'Schedule versioning',
        'Audit log coverage',
        'Period lock strategy',
        'Schedule-attendance relationship',
        'Timesheet-payroll relationship',
    ],
];
