cd /opt/hrm-stack
set -e
mysqlq(){ docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "$1"; }
echo "-- requests.status enum --"
mysqlq "SHOW COLUMNS FROM requests LIKE 'status';"
echo "-- requests status counts --"
mysqlq "SELECT status, COUNT(*) FROM requests GROUP BY status ORDER BY COUNT(*) DESC;"
