#!/usr/bin/env bash
set -e
cd /opt/hrm-stack
MYSQL_ROOT_PASSWORD=$(grep '^MYSQL_ROOT_PASSWORD=' .env.deploy | cut -d= -f2-)
docker compose --env-file .env.deploy exec -T mysql mysql --default-character-set=utf8mb4 -uroot -p"$MYSQL_ROOT_PASSWORD" HRM_SYSTEM -e "INSERT INTO employees (employee_code, full_name, gender, company_email, status, hire_date, seniority_start_date) VALUES ('TMPTEST01','Tmp Test','NAM','tmp.test01@company.com','?ANG_L?M_VI?C', CURDATE(), CURDATE()); DELETE FROM employees WHERE employee_code='TMPTEST01';"
