# HRM Workflow Audit And BE-First Roadmap (2026-04-17)

## 1) Scope

Tai lieu nay duoc tao de ra soat he thong hien tai va doi chieu voi workflow doanh nghiep:

`Master Data -> Scheduling -> Publish -> Attendance -> Requests -> Reconciliation -> Timesheet -> Payroll -> Approval -> Reporting`

## 2) Audit Summary

### 2.1 Da co trong he thong

- CRUD module HRM core (employee, department, contract, position, request, leave, attendance, payroll, communication).
- Co endpoint phan ca, publish lich, warning co ban.
- Co attendance result layer de tong hop theo ngay.
- Co payroll period va close period.

### 2.2 Khoang trong so voi workflow muc tieu

- Chua co catalog workflow trung tam de thong nhat state machine cho `schedule/request/timesheet/payroll`.
- Rule dang phan tan, chua co rule engine dung chung cho validate transition va readiness.
- Publish schedule hien tai chu yeu chan unassigned, chua co tap trung blocker/warning theo catalog.
- Chua co API governance de FE/BA/QA ra soat workflow va transition theo mot nguon chuan.

## 3) What Was Implemented In This Iteration

### 3.1 Workflow Catalog (backend source of truth)

- Them file config: `config/hrm_workflow.php`.
- Bao gom:
  - Chuoi workflow tong the.
  - State machine cho `schedule`, `request`, `timesheet`, `payroll`.
  - Rule catalog theo module.
  - Role matrix.
  - Phase priority cho BE-first implementation.

### 3.2 Rule Engine Layer

- Them service `WorkflowCatalogService`:
  - Doc catalog.
  - Tra ve section (`states/rules/roles/phases/checklist/workflow_chain`).
  - Tra ve transitions theo entity.

- Them service `WorkflowRuleEngineService`:
  - Validate transition theo state machine.
  - Validate readiness truoc publish schedule theo blocker/warning.

### 3.3 Governance APIs

Them controller `WorkflowGovernanceController` va routes:

- `GET /api/v1/workflow-governance/overview`
- `GET /api/v1/workflow-governance/catalog`
- `GET /api/v1/workflow-governance/transitions?entity=schedule`
- `POST /api/v1/workflow-governance/validate-transition`
- `POST /api/v1/workflow-governance/validate-schedule-publish`

### 3.4 Integration Into Existing Flow

- Nang cap `WorkforceController::publishSchedule`:
  - Ho tro `strict_mode`.
  - Dung rule engine trung tam de pre-check readiness.
  - Tra ve thong tin readiness day du trong response.

## 4) Recommended Next Steps (BE-first)

1. Them persisted state record cho schedule workflow theo period (`draft/submitted/reviewed/approved/published/locked`) de tranh chi suy dien tu log.
2. Bo sung timesheet aggregate service su dung `attendance_results + leave + overtime + policy`.
3. Bo sung approval_history event cho request/payroll transition de audit truy vet day du.
4. Chot policy lock/rollback cho payroll da close.
5. Sau khi data contract on dinh, day manh FE theo role-based use case.
