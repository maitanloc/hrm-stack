#!/usr/bin/env bash
set -e
cd /opt/hrm-stack
MYSQL_ROOT_PASSWORD=$(grep '^MYSQL_ROOT_PASSWORD=' .env.deploy | cut -d= -f2-)
docker compose --env-file .env.deploy exec -T mysql mysql --default-character-set=utf8mb4 -uroot -p"$MYSQL_ROOT_PASSWORD" -Nse "SHOW COLUMNS FROM HRM_SYSTEM.employees LIKE 'status'; SHOW COLUMNS FROM HRM_SYSTEM.employees LIKE 'gender';"
