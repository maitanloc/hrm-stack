# Database Audit 2026-04-16

## Scope
Audit source:
- Live MySQL schema and data counts on VPS (`HRM_SYSTEM`)
- Base schema files: `SQL_hackathon v4.sql`, `data.sql`
- Existing migrations/seeds in `BE/scripts`

## Requested Tables vs Actual Schema
Requested names not present exactly as-is:
- `users` -> actual auth/account entity is `employees`
- `payrolls` -> actual payroll entities are `salary_periods`, `salary_details`, `salary_breakdowns`, `payroll_adjustments`
- `benefits_insurance` -> actual entity is `social_insurance_info`
- `leaves` -> actual entities are `leave_balances`, `leave_requests`, `leave_transactions`
- `trainings` -> not found in current schema
- `performances` -> not found in current schema
- `recruitments` -> actual entities are `recruitment_positions`, `recruitment_candidates`, `interview_schedules`
- `resignations` -> not found in current schema

## High-Level Row Counts
Core populated tables:
- `employees`: 20
- `departments`: 16
- `positions`: 15
- `roles`: 6
- `permissions`: 25
- `contracts`: 10
- `attendances`: 21
- `salary_periods`: 3
- `salary_details`: 7
- `leave_balances`: 30
- `leave_requests`: 5
- `notifications`: 14
- `news`: 6
- `risk_alerts`: 110

Tables currently empty or nearly empty and good candidates for demo seed:
- `asset_categories`: 0
- `asset_locations`: 0
- `suppliers`: 0
- `assets`: 0
- `asset_assignments`: 0
- `service_tickets`: 0
- `service_ticket_updates`: 0
- `recruitment_positions`: 0
- `recruitment_candidates`: 0
- `interview_schedules`: 0
- `overtime_requests`: 0
- `payroll_adjustments`: 0
- `policies`: 0
- `policy_acknowledgments`: 0
- `contract_change_logs`: 0
- `news_reads`: 0

## Coverage Gaps Against Existing Employees
Current missing coverage per employee:
- employees without current `employment_histories`: 11
- employees without `contracts`: 10
- employees without `salary_details`: 13
- employees without `social_insurance_info`: 10
- employees without `identity_documents`: 10
- employees without `qualifications`: 10
- employees without `certificates`: 12

## Important NOT NULL / Constraint Notes
Representative required columns that the seed script must satisfy:

### Master data
- `departments.department_code`, `departments.department_name`
- `positions.position_code`, `positions.position_name`
- `roles.role_code`, `roles.role_name`
- `permissions.permission_code`, `permissions.permission_name`

### Employee core
- `employees.employee_code`, `employees.full_name`
- `employment_histories.employee_id`, `department_id`, `position_id`, `start_date`
- `employee_roles.employee_id`, `role_id`, `effective_date`

### Contract / payroll
- `contracts.contract_code`, `employee_id`, `contract_type_id`, `sign_date`, `effective_date`, `basic_salary`, `gross_salary`
- `salary_periods.period_code`, `period_name`, `year`, `start_date`, `end_date`
- `salary_details.period_id`, `employee_id`, `basic_salary`, `gross_salary`, `net_salary`
- `salary_breakdowns.salary_detail_id`, `item_type`, `item_name`, `amount`

### Leave / request
- `request_types.request_type_code`, `request_type_name`, `category`
- `requests.request_code`, `request_type_id`, `requester_id`, `request_date`
- `leave_requests.request_id`, `leave_type_id`, `employee_id`, `from_date`, `to_date`, `number_of_days`
- `leave_balances.employee_id`, `leave_type_id`, `year`, `total_days`

### Insurance / identity / qualification
- `social_insurance_info.employee_id`, `social_insurance_number`, `health_insurance_number`
- `identity_documents.employee_id`, `document_type_id`, `document_number`
- `qualifications.employee_id`, `qualification_type_id`, `qualification_name`
- `certificates.employee_id`, `certificate_name`

### Asset / internal service / recruitment
- `asset_categories.category_code`, `category_name`
- `asset_locations.location_code`, `location_name`
- `suppliers.supplier_code`, `supplier_name`
- `assets.asset_code`, `asset_name`
- `asset_assignments.asset_id`, `employee_id`, `assigned_by`, `assigned_date`
- `service_categories.category_code`, `category_name`
- `service_tickets.ticket_code`, `requester_id`, `category_id`, `title`
- `recruitment_positions.position_code`, `position_name`
- `recruitment_candidates.candidate_code`, `full_name`, `recruitment_position_id`, `applied_at`
- `interview_schedules.candidate_id`, `interview_date`, `interview_time`

### Policy / communication
- `policies.policy_code`, `policy_name`, `policy_type`
- `policy_acknowledgments.policy_id`, `employee_id`
- `notifications.notification_type`, `title`, `content`
- `news.news_code`, `title`

## Foreign Keys That Seed Must Respect
Representative FK chains:
- `employment_histories.employee_id -> employees.employee_id`
- `employment_histories.department_id -> departments.department_id`
- `employment_histories.position_id -> positions.position_id`
- `contracts.employee_id -> employees.employee_id`
- `contracts.contract_type_id -> contract_types.contract_type_id`
- `salary_details.period_id -> salary_periods.period_id`
- `salary_details.employee_id -> employees.employee_id`
- `salary_details.contract_id -> contracts.contract_id`
- `requests.request_type_id -> request_types.request_type_id`
- `requests.requester_id -> employees.employee_id`
- `leave_requests.request_id -> requests.request_id`
- `leave_requests.leave_type_id -> leave_types.leave_type_id`
- `asset_assignments.asset_id -> assets.asset_id`
- `asset_assignments.employee_id -> employees.employee_id`
- `service_tickets.requester_id -> employees.employee_id`
- `service_tickets.category_id -> service_categories.category_id`
- `recruitment_candidates.recruitment_position_id -> recruitment_positions.recruitment_position_id`
- `interview_schedules.candidate_id -> recruitment_candidates.candidate_id`
- `policy_acknowledgments.policy_id -> policies.policy_id`
- `policy_acknowledgments.employee_id -> employees.employee_id`

## Seeding Strategy
Safe seed strategy implemented in `BE/scripts/20260416_seed_demo_data_safe.sql`:
- Do not alter schema
- Do not delete real data
- Use demo markers / codes such as `DEMO-*`, `DMHD-*`, `[DEMO]`
- Fill missing employee-linked coverage first
- Populate empty workflow tables with idempotent `INSERT ... SELECT ... WHERE NOT EXISTS`
- Provide matching cleanup script `BE/scripts/20260416_reset_demo_data_safe.sql`
