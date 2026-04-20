cd /opt/hrm-stack
set -e

mysqlq(){
  docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "$1"
}

echo "-- employees stats --"
mysqlq "SELECT COUNT(*) total, SUM(full_name IS NULL OR TRIM(full_name)='') empty_name, SUM(full_name LIKE '%?%') has_qmark, SUM(full_name LIKE 'Nh?n vi?n %') fallback_name FROM employees;"

echo "-- sample bad employee names --"
mysqlq "SELECT employee_id, employee_code, full_name FROM employees WHERE full_name IS NULL OR TRIM(full_name)='' OR full_name LIKE '%?%' OR full_name LIKE 'Nh?n vi?n %' ORDER BY employee_id LIMIT 20;"

echo "-- leave request join sample --"
mysqlq "SELECT lr.leave_request_id, lr.employee_id, e.employee_code, e.full_name, lt.leave_type_name, r.status FROM leave_requests lr JOIN employees e ON e.employee_id=lr.employee_id JOIN leave_types lt ON lt.leave_type_id=lr.leave_type_id JOIN requests r ON r.request_id=lr.request_id ORDER BY lr.leave_request_id DESC LIMIT 15;"

echo "-- request_types sample --"
mysqlq "SELECT request_type_id, request_type_code, request_type_name, request_category FROM request_types ORDER BY request_type_id LIMIT 10;"

echo "-- leave_types sample --"
mysqlq "SELECT leave_type_id, leave_type_code, leave_type_name FROM leave_types ORDER BY leave_type_id LIMIT 10;"

echo "-- salary details sample --"
mysqlq "SELECT sd.salary_detail_id, sd.employee_id, e.employee_code, e.full_name, sd.period_id, sd.basic_salary, sd.net_salary FROM salary_details sd JOIN employees e ON e.employee_id=sd.employee_id ORDER BY sd.salary_detail_id DESC LIMIT 15;"
