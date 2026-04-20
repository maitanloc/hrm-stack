set -e
cd /opt/hrm-stack
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=hrm_user/' be.env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=change_user_password/' be.env
docker compose --env-file .env.deploy up -d hrm-be
sleep 2
docker compose --env-file .env.deploy logs --tail=50 hrm-be
