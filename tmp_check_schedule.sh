#!/usr/bin/env bash
set -e
EMAIL="an.nguyen@company.com"
PASS="NV0001"
LOGIN=$(curl -k -s --resolve anhsinhvienfpoly.click:443:127.0.0.1 -H "Content-Type: application/json" -d "{\"company_email\":\"$EMAIL\",\"password\":\"$PASS\"}" https://anhsinhvienfpoly.click/api/v1/auth/login)
TOKEN=$(echo "$LOGIN" | python3 -c 'import sys,json;d=json.loads(sys.stdin.read());print((d.get("data") or {}).get("access_token") or "")')
if [ -z "$TOKEN" ]; then
  echo "LOGIN_FAILED"
  echo "$LOGIN"
  exit 1
fi
RESP=$(curl -k -s --resolve anhsinhvienfpoly.click:443:127.0.0.1 -H "Authorization: Bearer $TOKEN" "https://anhsinhvienfpoly.click/api/v1/team-schedule?from_date=2026-04-14&to_date=2026-04-20")
echo "$RESP" | python3 -c 'import sys,json;resp=json.loads(sys.stdin.read());rows=(resp.get("data") or []);print("row_count",len(rows));print("meta",resp.get("meta"));print("unique_names",len(set([(r.get("employee") or {}).get("full_name") for r in rows if isinstance(r,dict)])))'
