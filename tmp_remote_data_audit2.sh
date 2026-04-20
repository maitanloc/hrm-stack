cd /opt/hrm-stack
set -e
mysqlq(){ docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "$1"; }
echo "-- request_types columns --"
mysqlq "SHOW COLUMNS FROM request_types;"
echo "-- request_types sample --"
mysqlq "SELECT request_type_id, request_type_code, request_type_name, type_code, description FROM request_types ORDER BY request_type_id;"
echo "-- leave_types columns --"
mysqlq "SHOW COLUMNS FROM leave_types;"
echo "-- leave_types sample --"
mysqlq "SELECT leave_type_id, leave_type_code, leave_type_name, paid_leave, requires_document FROM leave_types ORDER BY leave_type_id;"
echo "-- departments sample --"
mysqlq "SELECT department_id, department_name FROM departments ORDER BY department_id LIMIT 20;"
echo "-- roles sample --"
mysqlq "SELECT role_id, role_code, role_name FROM roles ORDER BY role_id;"
