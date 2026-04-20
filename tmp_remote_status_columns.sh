cd /opt/hrm-stack
set -e

docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "SELECT table_name, column_type FROM information_schema.columns WHERE table_schema='hrm_db' AND column_name='status' AND table_name IN ('requests','attendances','overtime_requests','leave_requests','shift_assignments','contracts','notifications') ORDER BY table_name;"
