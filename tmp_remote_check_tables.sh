cd /opt/hrm-stack
set -e

docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "SELECT table_name FROM information_schema.tables WHERE table_schema='hrm_db' AND table_name IN ('shift_assignment_overrides','shift_assignments','shift_schedules','leave_requests','requests','employees','salary_details','request_types','leave_types') ORDER BY table_name;"
