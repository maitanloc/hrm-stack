# BE-First Implementation Playbook (2026-04-17)

## Objective
Khoi backend theo dung thu tu nghiep vu cho HRM workflow-heavy:
1. Domain model
2. Workflow/state machine
3. Rule catalog + blocker/warning
4. API + permission boundary
5. FE consume theo use case da khoa
6. Scenario integration test

## Locked Artifacts
- Domain model source of truth: `config/hrm_workflow.php > domain_model`
- Workflow states: `config/hrm_workflow.php > entity_states`
- Rule catalog: `config/hrm_workflow.php > rule_catalog`
- Permission matrix: `config/hrm_workflow.php > permission_matrix`
- State mappings and lock policy: `config/hrm_workflow.php > state_mappings`, `lock_policies`

## Runtime Enforcement Implemented
- Transition guard service: `app/Services/WorkflowTransitionGuardService.php`
  - validate request/leave/payroll state changes by canonical state machine.
- Payroll lock guard:
  - block schedule mutations in payroll `PAID/CLOSED` date range.
  - block payroll detail/breakdown/adjustment mutation in locked periods.
- Workflow audit log:
  - write event/transition via `app/Services/WorkflowAuditService.php`
  - persistence model `app/Models/WorkflowAuditLog.php`
  - SQL patch `scripts/20260417_workflow_audit_logs.sql`

## Governance Endpoints
- `GET /api/v1/workflow-governance/catalog?section=domain|permission|mapping|lock_policy`
- `GET /api/v1/workflow-governance/audit-logs`

## Suggested Next Scenario Tests
1. Publish schedule for date range in closed payroll period -> must return `422`.
2. Update leave status with invalid step order -> must return `422`.
3. Close payroll from non-`PAID` status -> must return `422`.
4. Update salary detail when period `CLOSED` -> must return `422`.
5. Valid transition chain request (`NHAP -> CHO_DUYET -> DANG_XU_LY -> DA_DUYET`) -> success + audit rows.

## Quick Run Commands
- Offline gate smoke (khong can DB): `php scripts/be_first_gate_smoke.php`
- Apply audit table patch: `powershell -ExecutionPolicy Bypass -File .\scripts\apply_workflow_audit_patch.ps1 -HostName 127.0.0.1 -Port 3306 -Database HRM_SYSTEM -Username <user> -Password <pass>`
- Full scenario runner (co API): `powershell -ExecutionPolicy Bypass -File .\scripts\run_be_first_scenarios.ps1 -BaseUrl http://127.0.0.1:8080/api/v1 -Email hai.do@company.com -Password NV0009`
