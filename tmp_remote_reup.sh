set -e
cd /opt/hrm-stack
sed -n '1,80p' be.env | grep -E '^DB_'
docker compose --env-file .env.deploy up -d --build
sleep 2
docker compose --env-file .env.deploy logs --tail=80
