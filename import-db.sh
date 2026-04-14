#!/usr/bin/env sh
set -eu

if [ ! -f ".env.deploy" ]; then
  echo "Missing .env.deploy (copy from .env.deploy.example first)"
  exit 1
fi

MYSQL_ROOT_PASSWORD="$(awk -F= '/^MYSQL_ROOT_PASSWORD=/{print $2}' .env.deploy)"
if [ -z "${MYSQL_ROOT_PASSWORD}" ]; then
  echo "MYSQL_ROOT_PASSWORD is empty in .env.deploy"
  exit 1
fi

echo "Create database if needed..."
docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS HRM_SYSTEM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Import base schema/data..."
docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" HRM_SYSTEM < "SQL_hackathon v4.sql"
docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" HRM_SYSTEM < "data.sql"

echo "Import migrations..."
for f in \
  20260313_payroll_adjustments.sql \
  20260315_employee_password_auth.sql \
  20260315_contract_change_logs.sql \
  20260315_dynamic_frontend_modules.sql \
  20260324_recruitment_cv_and_interview_review.sql \
  20260325_recruitment_manager_reviews.sql \
  20260326_leave_request_workflow_statuses.sql \
  20260326_seed_full_user_demo_data.sql
do
  docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" HRM_SYSTEM < "BE/scripts/$f"
done

echo "Done."
