#!/usr/bin/env bash
set -e
cd /opt/hrm-stack
MYSQL_ROOT_PASSWORD=$(grep '^MYSQL_ROOT_PASSWORD=' .env.deploy | cut -d= -f2-)
docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -Nse "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='HRM_SYSTEM' AND TABLE_NAME='employees' AND COLUMN_NAME='gender';"
