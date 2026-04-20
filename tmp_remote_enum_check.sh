cd /opt/hrm-stack
set -e
mysqlq(){ docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "$1"; }

echo "-- holidays.holiday_type --"
mysqlq "SHOW COLUMNS FROM holidays LIKE 'holiday_type';"

echo "-- attendances.status --"
mysqlq "SHOW COLUMNS FROM attendances LIKE 'status';"

echo "-- attendances.check_in_method --"
mysqlq "SHOW COLUMNS FROM attendances LIKE 'check_in_method';"

echo "-- leave_requests.from_session --"
mysqlq "SHOW COLUMNS FROM leave_requests LIKE 'from_session';"
