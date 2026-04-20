#!/usr/bin/env bash
set -e

# Lấy mật khẩu root từ environment
ROOT_PASS=${MYSQL_ROOT_PASSWORD:-hrm_root_2026_secure}
TARGET_DB=${MYSQL_DATABASE:-HRM_SYSTEM}
APP_USER=${MYSQL_USER:-hrm_user}
APP_PASS=${MYSQL_PASSWORD:-hrm_pass_2026_secure}

echo "--- Fixing MySQL Permissions for $APP_USER on $TARGET_DB ---"

# Thử dùng mật khẩu hrm_root_2026_secure hoặc change_root_password (dự phòng)
mysql -uroot -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON \`$TARGET_DB\`.* TO '$APP_USER'@'%'; FLUSH PRIVILEGES;" || \
mysql -uroot -p"change_root_password" -e "GRANT ALL PRIVILEGES ON \`$TARGET_DB\`.* TO '$APP_USER'@'%'; FLUSH PRIVILEGES;"

echo "--- Verifying permissions ---"
mysql -u"$APP_USER" -p"$APP_PASS" -D "$TARGET_DB" -e "SHOW TABLES;"
