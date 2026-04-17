#!/usr/bin/env sh
set -eu

if [ ! -f ".env.deploy" ]; then
  echo "Missing .env.deploy (copy from .env.deploy.example first)"
  exit 1
fi

MYSQL_ROOT_PASSWORD="$(awk -F= '/^MYSQL_ROOT_PASSWORD=/{print $2}' .env.deploy | tr -d '\r')"
if [ -z "${MYSQL_ROOT_PASSWORD}" ]; then
  echo "MYSQL_ROOT_PASSWORD is empty in .env.deploy"
  exit 1
fi

TARGET_DB="$(awk -F= '/^DB_DATABASE=/{print $2}' .env.deploy | tr -d '\r')"
if [ -z "${TARGET_DB}" ]; then
  TARGET_DB="HRM_SYSTEM"
fi

compose() {
  docker compose --env-file .env.deploy "$@"
}

import_sql_if_exists() {
  file_path="$1"
  if [ -f "$file_path" ]; then
    compose exec -T mysql mysql --default-character-set=utf8mb4 -uroot "-p${MYSQL_ROOT_PASSWORD}" "${TARGET_DB}" < "$file_path"
  else
    echo "Skip missing migration: $file_path"
  fi
}

BE_SERVICE="be"
if ! compose config --services | grep -qx "be"; then
  if compose config --services | grep -qx "hrm-be"; then
    BE_SERVICE="hrm-be"
  else
    echo "Cannot detect backend service name (expected be or hrm-be)."
    exit 1
  fi
fi

echo "Wait for MySQL to be ready..."
attempt=0
until compose exec -T mysql mysqladmin ping -h127.0.0.1 -uroot "-p${MYSQL_ROOT_PASSWORD}" --silent >/dev/null 2>&1
do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 60 ]; then
    echo "MySQL is not ready after 120 seconds."
    exit 1
  fi
  sleep 2
done

echo "Create database if needed..."
compose exec -T mysql mysql --default-character-set=utf8mb4 -uroot "-p${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

BASE_EXISTS="$(compose exec -T mysql mysql -N -uroot "-p${MYSQL_ROOT_PASSWORD}" -D "${TARGET_DB}" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TARGET_DB}' AND table_name='nationalities';" | tr -d '\r' || echo 0)"
if [ "${BASE_EXISTS}" = "0" ]; then
  echo "Import base schema/data..."
  compose exec -T mysql mysql --default-character-set=utf8mb4 -uroot "-p${MYSQL_ROOT_PASSWORD}" "${TARGET_DB}" < "SQL_hackathon v4.sql"
  compose exec -T mysql mysql --default-character-set=utf8mb4 -uroot "-p${MYSQL_ROOT_PASSWORD}" "${TARGET_DB}" < "data.sql"
else
  echo "Base schema already exists, skip SQL_hackathon v4.sql and data.sql."
fi

echo "Import idempotent migrations..."
import_sql_if_exists "BE/scripts/20260415_attendance_risk_flow.sql"
import_sql_if_exists "BE/scripts/20260415_attendance_secondary_slots.sql"
import_sql_if_exists "BE/scripts/20260415_attendance_method_columns.sql"
import_sql_if_exists "BE/scripts/20260415_attendance_performance_indexes.sql"
import_sql_if_exists "BE/scripts/20260415_company_geo_policy.sql"
import_sql_if_exists "BE/scripts/20260415_role_permission_hardening.sql"
import_sql_if_exists "BE/scripts/20260415_fix_vietnamese_mojibake.sql"
import_sql_if_exists "BE/scripts/20260416_phase1_workforce_schedule.sql"
import_sql_if_exists "BE/scripts/20260417_fix_core_text_and_enums.sql"

echo "Apply permission seed..."
if [ -f "BE/scripts/grant_portal_permissions.php" ]; then
  compose exec -T "${BE_SERVICE}" php scripts/grant_portal_permissions.php
else
  echo "Skip missing permission seed: BE/scripts/grant_portal_permissions.php"
fi

echo "Done."
