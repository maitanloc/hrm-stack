#!/bin/bash
MYSQL="mysql -uroot -pchange_root_password HRM_SYSTEM"

echo "=== DEPARTMENTS ==="
$MYSQL -e "SELECT * FROM departments ORDER BY department_id" 2>/dev/null

echo "=== EMPLOYEES ==="
$MYSQL -e "SELECT employee_id, employee_code, full_name, phone_number, company_email, status, hire_date FROM employees ORDER BY employee_id" 2>/dev/null

echo "=== ROLES ==="
$MYSQL -e "SELECT * FROM roles ORDER BY role_id" 2>/dev/null

echo "=== EMPLOYEE_ROLES ==="
$MYSQL -e "SELECT * FROM employee_roles ORDER BY id" 2>/dev/null

echo "=== POSITIONS ==="
$MYSQL -e "SELECT * FROM positions ORDER BY position_id" 2>/dev/null

echo "=== CONTRACTS ==="
$MYSQL -e "SELECT contract_id, contract_code, employee_id, contract_type_id, position_id, department_id, basic_salary, status FROM contracts ORDER BY contract_id" 2>/dev/null

echo "=== SHIFT_TYPES ==="
$MYSQL -e "SELECT * FROM shift_types ORDER BY shift_type_id" 2>/dev/null

echo "=== SHIFT_SCHEDULES ==="
$MYSQL -e "SELECT * FROM shift_schedules ORDER BY schedule_id" 2>/dev/null

echo "=== SHIFT_SCHEDULE_DETAILS ==="
$MYSQL -e "SELECT * FROM shift_schedule_details ORDER BY detail_id" 2>/dev/null

echo "=== SHIFT_ASSIGNMENTS ==="
$MYSQL -e "SELECT * FROM shift_assignments ORDER BY assignment_id" 2>/dev/null

echo "=== ATTENDANCES ==="
$MYSQL -e "SELECT attendance_id, employee_id, attendance_date, shift_type_id, check_in_time, check_out_time, late_minutes, early_leave_minutes, overtime_hours, status FROM attendances ORDER BY attendance_id" 2>/dev/null

echo "=== LEAVE_TYPES ==="
$MYSQL -e "SELECT * FROM leave_types ORDER BY leave_type_id" 2>/dev/null

echo "=== LEAVE_REQUESTS ==="
$MYSQL -e "SELECT * FROM leave_requests ORDER BY leave_request_id" 2>/dev/null

echo "=== LEAVE_BALANCES ==="
$MYSQL -e "SELECT * FROM leave_balances ORDER BY balance_id" 2>/dev/null

echo "=== NOTIFICATIONS ==="
$MYSQL -e "SELECT * FROM notifications ORDER BY notification_id" 2>/dev/null

echo "=== SALARY_PERIODS ==="
$MYSQL -e "SELECT * FROM salary_periods ORDER BY period_id" 2>/dev/null

echo "=== SALARY_DETAILS ==="
$MYSQL -e "SELECT salary_detail_id, period_id, employee_id, basic_salary, gross_salary, net_salary, total_allowances, total_deductions, overtime_pay, final_amount FROM salary_details ORDER BY salary_detail_id" 2>/dev/null

echo "=== HOLIDAYS ==="
$MYSQL -e "SELECT * FROM holidays ORDER BY holiday_id" 2>/dev/null

echo "=== ALLOWANCES ==="
$MYSQL -e "SELECT * FROM allowances ORDER BY allowance_id" 2>/dev/null

echo "=== DEDUCTIONS ==="
$MYSQL -e "SELECT * FROM deductions ORDER BY deduction_id" 2>/dev/null

echo "=== NEWS ==="
$MYSQL -e "SELECT * FROM news" 2>/dev/null

echo "=== REQUESTS TABLE ==="
$MYSQL -e "DESCRIBE requests" 2>/dev/null
$MYSQL -e "SELECT * FROM requests ORDER BY request_id" 2>/dev/null

echo "=== EMPLOYEE ACCOUNTS ==="
$MYSQL -e "SELECT employee_id, employee_code, full_name, company_email, password_hash IS NOT NULL as has_password FROM employees ORDER BY employee_id" 2>/dev/null

echo "=== DESCRIBE employee_roles ==="
$MYSQL -e "DESCRIBE employee_roles" 2>/dev/null

echo "=== DESCRIBE departments ==="
$MYSQL -e "DESCRIBE departments" 2>/dev/null

echo "=== DONE ==="
