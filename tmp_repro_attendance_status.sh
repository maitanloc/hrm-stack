#!/usr/bin/env bash
set -euo pipefail

EMAIL="huong.pham@company.com"
PASS="NV0004"

LOGIN_JSON=$(curl -s -X POST "http://127.0.0.1/api/v1/auth/login" \
  -d "company_email=${EMAIL}&password=${PASS}")

TOKEN=$(printf '%s' "$LOGIN_JSON" | python3 -c 'import sys, json; d=json.load(sys.stdin); print(d.get("data", {}).get("access_token", ""))')
if [ -z "$TOKEN" ]; then
  echo "LOGIN_FAIL"
  echo "$LOGIN_JSON"
  exit 1
fi

PRECHECK_BODY='{"employee_id":4,"device_id":"web-test-01","platform":"ANDROID","lat":10.9440833,"lng":106.8816667,"accuracy_m":25,"attendance_type":"CHECKIN","client_time":"2026-04-16 08:20:00","app_version":"mobile-web-1.1.0"}'
NOW_TS=$(date +"%Y-%m-%d %H:%M:%S")
PRECHECK_BODY="{\"employee_id\":4,\"device_id\":\"web-test-01\",\"platform\":\"ANDROID\",\"lat\":10.9440833,\"lng\":106.8816667,\"accuracy_m\":25,\"attendance_type\":\"CHECKIN\",\"client_time\":\"${NOW_TS}\",\"app_version\":\"mobile-web-1.1.0\"}"

BOOTSTRAP_BODY='{"employee_id":4,"device_id":"web-test-01","platform":"ANDROID","lat":10.9440833,"lng":106.8816667,"accuracy_m":25}'
BOOTSTRAP_JSON=$(curl -s -X POST "http://127.0.0.1/api/v1/attendance/bootstrap" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d "$BOOTSTRAP_BODY")
echo "BOOTSTRAP_JSON=$BOOTSTRAP_JSON"

PRECHECK_JSON=$(curl -s -X POST "http://127.0.0.1/api/v1/attendance/precheck" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d "$PRECHECK_BODY")

P_TOKEN=$(printf '%s' "$PRECHECK_JSON" | python3 -c 'import sys, json; d=json.load(sys.stdin); print(d.get("data", {}).get("precheck_token", ""))')
echo "PRECHECK_JSON=$PRECHECK_JSON"

if [ -z "$P_TOKEN" ]; then
  echo "NO_PRECHECK_TOKEN"
  exit 1
fi

NOW_TS_2=$(date +"%Y-%m-%d %H:%M:%S")
CHECKIN_BODY=$(printf '{"employee_id":4,"precheck_token":"%s","client_time":"%s"}' "$P_TOKEN" "$NOW_TS_2")
CHECKIN_JSON=$(curl -s -X POST "http://127.0.0.1/api/v1/attendance/checkin" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d "$CHECKIN_BODY")

echo "CHECKIN_JSON=$CHECKIN_JSON"
