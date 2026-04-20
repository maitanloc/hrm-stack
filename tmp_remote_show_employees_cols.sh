cd /opt/hrm-stack
set -e
docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "SHOW COLUMNS FROM employees;"
