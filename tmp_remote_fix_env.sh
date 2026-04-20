set -e
cd /opt/hrm-stack

# Ensure app env vars are explicit for compose runtime
if ! grep -q '^DB_DATABASE=' .env.deploy; then echo 'DB_DATABASE=HRM_SYSTEM' >> .env.deploy; fi
if ! grep -q '^DB_USERNAME=' .env.deploy; then echo 'DB_USERNAME=hrm_user' >> .env.deploy; fi
if ! grep -q '^DB_PASSWORD=' .env.deploy; then echo 'DB_PASSWORD=change_user_password' >> .env.deploy; fi
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=HRM_SYSTEM/' .env.deploy
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=hrm_user/' .env.deploy
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=change_user_password/' .env.deploy

# Fix backend local .env which app bootstrap reads
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=HRM_SYSTEM/' BE/.env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=hrm_user/' BE/.env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=change_user_password/' BE/.env

# Keep be.env aligned too (for alt compose)
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=HRM_SYSTEM/' be.env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=hrm_user/' be.env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=change_user_password/' be.env

echo '--- .env.deploy'
sed -n '1,30p' .env.deploy
echo '--- BE/.env'
sed -n '1,20p' BE/.env

docker compose --env-file .env.deploy up -d --build hrm-be hrm-fe hrm-proxy
sleep 2

echo '--- runtime env in hrm-be'
docker inspect hrm-be --format='{{range .Config.Env}}{{println .}}{{end}}' | grep '^DB_'

echo '--- API smoke'
curl -s -o /dev/null -w '%{http_code}\n' https://anhsinhvienfpoly.click/api/v1/auth/me

echo '--- be logs tail'
docker compose --env-file .env.deploy logs --tail=25 hrm-be
