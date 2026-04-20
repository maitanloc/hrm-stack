#!/usr/bin/env bash
set -e
EMAIL='an.nguyen@company.com'
PASS='NV0001'
LOGIN=$(curl -k -s --resolve anhsinhvienfpoly.click:443:127.0.0.1 -H 'Content-Type: application/json' -d "{\"company_email\":\"$EMAIL\",\"password\":\"$PASS\"}" https://anhsinhvienfpoly.click/api/v1/auth/login)
TOKEN=$(echo "$LOGIN" | sed -n 's/.*"access_token":"\([^"]*\)".*/\1/p')
if [ -z "$TOKEN" ]; then
  echo 'LOGIN_PARSE_FAILED'
  echo "$LOGIN"
  exit 1
fi
RESP=$(curl -k -s --resolve anhsinhvienfpoly.click:443:127.0.0.1 -H "Authorization: Bearer $TOKEN" 'https://anhsinhvienfpoly.click/api/v1/team-schedule?from_date=2026-04-14&to_date=2026-04-20')
echo "$RESP" | python3 -c 'import sys,json;d=json.loads(sys.stdin.read());rows=d.get("data") or [];print("rows",len(rows));print("meta",d.get("meta"));print("names",len(set([(r.get("employee") or {}).get("full_name") for r in rows if isinstance(r,dict)])))'
