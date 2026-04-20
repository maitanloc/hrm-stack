#!/usr/bin/env bash
set -euo pipefail

EMAIL="hai.do@company.com"
PASS="NV0009"
EMPLOYEE_ID=4
ATT_DATE="$(date -d '+1 day' +%F)"
CHECKIN_AT="$(date '+%F %T')"

LOGIN_JSON=$(curl -s -X POST "http://127.0.0.1/api/v1/auth/login" \
  -d "company_email=${EMAIL}&password=${PASS}")
TOKEN=$(printf '%s' "$LOGIN_JSON" | python3 -c 'import sys, json; d=json.load(sys.stdin); print(d.get("data", {}).get("access_token", ""))')
if [ -z "$TOKEN" ]; then
  echo "LOGIN_FAIL"
  echo "$LOGIN_JSON"
  exit 1
fi

BODY=$(printf '{"employee_id":%d,"attendance_date":"%s","check_in_time":"%s","status":"CHỜ_DUYỆT","check_in_method":"MOBILE"}' \
  "$EMPLOYEE_ID" "$ATT_DATE" "$CHECKIN_AT")

RESP=$(curl -s -X POST "http://127.0.0.1/api/v1/attendances" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d "$BODY")

echo "$RESP"
