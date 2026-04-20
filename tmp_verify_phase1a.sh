#!/bin/sh
# Test login via PHP built-in function from mysql container
mysql -uroot -pchange_root_password HRM_SYSTEM -e "
SELECT 'TEST: employee_roles per employee' AS test;
SELECT er.employee_id, e.employee_code, e.full_name, 
       GROUP_CONCAT(r.role_code SEPARATOR ', ') AS roles
FROM employee_roles er
JOIN employees e ON e.employee_id = er.employee_id
JOIN roles r ON r.role_id = er.role_id
WHERE er.is_active = 1
GROUP BY er.employee_id, e.employee_code, e.full_name
ORDER BY er.employee_id;

SELECT 'TEST: permissions per role' AS test;
SELECT r.role_code, COUNT(*) as perm_count
FROM role_permissions rp
JOIN roles r ON r.role_id = rp.role_id
GROUP BY r.role_code
ORDER BY r.role_id;

SELECT 'TEST: employment_histories current' AS test;
SELECT eh.employee_id, e.employee_code, e.full_name, d.department_name, p.position_name
FROM employment_histories eh
JOIN employees e ON e.employee_id = eh.employee_id
JOIN departments d ON d.department_id = eh.department_id
JOIN positions p ON p.position_id = eh.position_id
WHERE eh.is_current = 1
ORDER BY eh.employee_id;

SELECT 'TEST: password status' AS test;
SELECT employee_id, employee_code, company_email, 
       CASE WHEN password_hash IS NOT NULL AND password_hash != '' THEN 'HAS_PASSWORD' ELSE 'NO_PASSWORD_USE_EMP_CODE' END AS auth_method
FROM employees
ORDER BY employee_id;

SELECT 'TEST: departments with managers' AS test;
SELECT d.department_id, d.department_code, d.department_name, d.manager_id, e.full_name AS manager_name
FROM departments d
LEFT JOIN employees e ON e.employee_id = d.manager_id
WHERE d.parent_department_id IS NULL
ORDER BY d.department_id;
" 2>/dev/null
