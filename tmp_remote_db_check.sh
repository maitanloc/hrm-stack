set -e
cd /opt/hrm-stack
set +e
echo 'try root no pw'
docker compose --env-file .env.deploy exec -T mysql mysql -uroot --batch --skip-column-names --execute="SELECT 1;"
echo "rc_root_no_pw=$?"
echo 'try root from .env.deploy'
ROOT_PW="$(sed -n 's/^MYSQL_ROOT_PASSWORD=//p' .env.deploy | tr -d '\r')"
docker compose --env-file .env.deploy exec -T mysql mysql -uroot -p"$ROOT_PW" --batch --skip-column-names --execute="SELECT 1;"
echo "rc_root_envpw=$?"
echo 'try hrm_user default'
docker compose --env-file .env.deploy exec -T mysql mysql -uhrm_user -pchange_user_password --batch --skip-column-names --execute="SELECT 1;"
echo "rc_hrm_user_default=$?"
