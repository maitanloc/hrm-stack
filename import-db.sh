#!/usr/bin/env sh
set -eu

ENV_FILE=".env"
if [ ! -f "${ENV_FILE}" ]; then
  if [ -f ".env.deploy" ]; then
    ENV_FILE=".env.deploy"
  else
    echo "Missing .env/.env.deploy (copy from .env.example first)"
    exit 1
  fi
fi

MYSQL_ROOT_PASSWORD="$(awk -F= '/^MYSQL_ROOT_PASSWORD=/{print $2}' "${ENV_FILE}" | tr -d '\r')"
if [ -z "${MYSQL_ROOT_PASSWORD}" ]; then
  echo "MYSQL_ROOT_PASSWORD is empty in ${ENV_FILE}"
  exit 1
fi

TARGET_DB="$(awk -F= '/^DB_DATABASE=/{print $2}' "${ENV_FILE}" | tr -d '\r')"
if [ -z "${TARGET_DB}" ]; then
  TARGET_DB="HRM_SYSTEM"
fi

compose() {
  docker compose --env-file "${ENV_FILE}" "$@"
}

fail_reseed_if_text_corrupted() {
  file_path="$1"

  if command -v python3 >/dev/null 2>&1; then
    if ! python3 -c "import pathlib, re, sys; raw = pathlib.Path(sys.argv[1]).read_text(encoding='utf-8'); patterns = [r'Ã', r'Â', r'Æ', r'Ä', r'Ð', r'Ñ', r'áº', r'á»', r'�']; score = sum(len(re.findall(pattern, raw)) for pattern in patterns); question_marks = raw.count('?'); sys.exit(3 if score >= 20 or (score >= 5 and question_marks >= 20) else 0)" "$file_path"; then
      echo "Blocked import: base data file appears text-corrupted and is unsafe for reseed: $file_path"
      exit 1
    fi
    return 0
  fi

  if command -v python >/dev/null 2>&1; then
    if ! python -c "import io, re, sys; raw = io.open(sys.argv[1], 'r', encoding='utf-8').read(); patterns = [u'Ã', u'Â', u'Æ', u'Ä', u'Ð', u'Ñ', u'áº', u'á»', u'�']; score = sum(len(re.findall(pattern, raw)) for pattern in patterns); question_marks = raw.count('?'); sys.exit(3 if score >= 20 or (score >= 5 and question_marks >= 20) else 0)" "$file_path"; then
      echo "Blocked import: base data file appears text-corrupted and is unsafe for reseed: $file_path"
      exit 1
    fi
    return 0
  fi

  if command -v php >/dev/null 2>&1; then
    if ! php -r '$raw = file_get_contents($argv[1]); if ($raw === false || !mb_check_encoding($raw, "UTF-8")) { exit(1); } $patterns = ["/Ã/u", "/Â/u", "/Æ/u", "/Ä/u", "/Ð/u", "/Ñ/u", "/áº/u", "/á»/u", "/�/u"]; $score = 0; foreach ($patterns as $pattern) { $count = preg_match_all($pattern, $raw, $matches); if ($count === false) { exit(1); } $score += $count; } $questionMarks = substr_count($raw, "?"); if ($score >= 20 || ($score >= 5 && $questionMarks >= 20)) { exit(3); }' "$file_path"; then
      echo "Blocked import: base data file appears text-corrupted and is unsafe for reseed: $file_path"
      exit 1
    fi
    return 0
  fi

  echo "Cannot inspect text quality for $file_path. Install python, python3, or php+mbstring."
  exit 1
}

require_utf8_file() {
  file_path="$1"

  if [ ! -f "$file_path" ]; then
    echo "Missing SQL file: $file_path"
    exit 1
  fi

  if command -v iconv >/dev/null 2>&1; then
    if ! iconv -f UTF-8 -t UTF-8 "$file_path" >/dev/null 2>&1; then
      echo "Invalid UTF-8 SQL file: $file_path"
      exit 1
    fi
    return 0
  fi

  if command -v python3 >/dev/null 2>&1; then
    if ! python3 -c "import pathlib, sys; pathlib.Path(sys.argv[1]).read_text(encoding='utf-8')" "$file_path" >/dev/null 2>&1; then
      echo "Invalid UTF-8 SQL file: $file_path"
      exit 1
    fi
    return 0
  fi

  if command -v python >/dev/null 2>&1; then
    if ! python -c "import io, sys; io.open(sys.argv[1], 'r', encoding='utf-8').read()" "$file_path" >/dev/null 2>&1; then
      echo "Invalid UTF-8 SQL file: $file_path"
      exit 1
    fi
    return 0
  fi

  if command -v php >/dev/null 2>&1; then
    if ! php -r '$raw = file_get_contents($argv[1]); if ($raw === false || !mb_check_encoding($raw, "UTF-8")) { fwrite(STDERR, "invalid\n"); exit(1); }' "$file_path" >/dev/null 2>&1; then
      echo "Invalid UTF-8 SQL file: $file_path"
      exit 1
    fi
    return 0
  fi

  echo "Cannot validate UTF-8 for $file_path. Install iconv, python, or php+mbstring."
  exit 1
}

import_sql_file() {
  file_path="$1"
  target_db="$2"

  require_utf8_file "$file_path"
  echo "Importing $file_path into ${target_db} with utf8mb4-safe client/session..."

  {
    printf '%s\n' "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;"
    printf '%s\n' "SET character_set_client = utf8mb4;"
    printf '%s\n' "SET character_set_connection = utf8mb4;"
    printf '%s\n' "SET character_set_results = utf8mb4;"
    cat "$file_path"
  } | compose exec -T mysql mysql \
    --default-character-set=utf8mb4 \
    --binary-mode \
    -uroot "-p${MYSQL_ROOT_PASSWORD}" \
    "${target_db}"
}

require_clean_base_sql_file() {
  file_path="$1"

  require_utf8_file "$file_path"
  fail_reseed_if_text_corrupted "$file_path"
}

echo "Validate approved base SQL files..."
require_utf8_file "SQL_hackathon v4.sql"
require_clean_base_sql_file "data.sql"

import_sql_if_exists() {
  file_path="$1"
  if [ -f "$file_path" ]; then
    import_sql_file "$file_path" "${TARGET_DB}"
  else
    echo "Skip missing migration: $file_path"
  fi
}

BE_SERVICE="be"
if compose config --services | grep -qx "be"; then
  :
elif compose config --services | grep -qx "hrm-be"; then
  BE_SERVICE="hrm-be"
else
  echo "Cannot detect backend service name (expected be or hrm-be)."
  exit 1
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

echo "Ensure user permissions..."
MYSQL_USER="$(awk -F= '/^MYSQL_USER=/{print $2}' "${ENV_FILE}" | tr -d '\r')"
MYSQL_PASSWORD="$(awk -F= '/^MYSQL_PASSWORD=/{print $2}' "${ENV_FILE}" | tr -d '\r')"
compose exec -T mysql mysql -uroot "-p${MYSQL_ROOT_PASSWORD}" -e "CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}'; GRANT ALL PRIVILEGES ON \`${TARGET_DB}\`.* TO '${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"

BASE_EXISTS="$(compose exec -T mysql mysql -N -uroot "-p${MYSQL_ROOT_PASSWORD}" -D "${TARGET_DB}" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TARGET_DB}' AND table_name='nationalities';" | tr -d '\r' || echo 0)"
if [ "${BASE_EXISTS}" = "0" ]; then
  echo "Import base schema/data..."
  import_sql_file "SQL_hackathon v4.sql" "${TARGET_DB}"
  import_sql_file "data.sql" "${TARGET_DB}"
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
import_sql_if_exists "BE/scripts/20260416_phase1_workforce_schedule.sql"

echo "Apply permission seed..."
if [ -f "BE/scripts/grant_portal_permissions.php" ]; then
  compose exec -T "${BE_SERVICE}" php scripts/grant_portal_permissions.php
else
  echo "Skip missing permission seed: BE/scripts/grant_portal_permissions.php"
fi

echo "Done."
