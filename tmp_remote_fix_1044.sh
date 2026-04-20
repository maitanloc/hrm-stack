set -e
cd /opt/hrm-stack

echo '--- current runtime DB env'
docker inspect hrm-be --format='{{range .Config.Env}}{{println .}}{{end}}' | grep '^DB_'

echo '--- try connect hrm_user to HRM_SYSTEM'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -D HRM_SYSTEM -e "SELECT 1;"' || true

echo '--- try connect hrm_user to hrm_db'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -D hrm_db -e "SELECT 1;"' || true

echo '--- list DBs visible by hrm_user'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -Nse "SHOW DATABASES;"' || true

echo '--- try grant by hrm_user (if has grant option)'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -e "GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO '\''hrm_user'\''@'\''%\''; FLUSH PRIVILEGES;"' || true

echo '--- retest HRM_SYSTEM'
docker compose --env-file .env.deploy exec -T mysql sh -lc 'mysql -uhrm_user -pchange_user_password -D HRM_SYSTEM -e "SELECT COUNT(*) FROM information_schema.tables;"' || true
