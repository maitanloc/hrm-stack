set -e
cd /opt/hrm-stack

echo '--- hrm_db tables count'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"hrm_db\";"'

echo '--- sample core tables in hrm_db'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -Nse "SELECT table_name FROM information_schema.tables WHERE table_schema=\"hrm_db\" AND table_name IN (\"employees\",\"departments\",\"attendances\",\"leave_requests\",\"salary_details\",\"request_types\",\"notifications\",\"roles\",\"positions\");"'

echo '--- row counts core in hrm_db'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -D hrm_db -Nse "SELECT \"employees\", COUNT(*) FROM employees UNION ALL SELECT \"departments\", COUNT(*) FROM departments UNION ALL SELECT \"attendances\", COUNT(*) FROM attendances UNION ALL SELECT \"leave_requests\", COUNT(*) FROM leave_requests UNION ALL SELECT \"salary_details\", COUNT(*) FROM salary_details UNION ALL SELECT \"notifications\", COUNT(*) FROM notifications;"'
