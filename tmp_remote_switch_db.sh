set -e
cd /opt/hrm-stack

# switch app DB to hrm_db (the DB hrm_user can access and already has full data)
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=hrm_db/' .env.deploy
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=hrm_db/' be.env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=hrm_db/' BE/.env

echo '--- effective env files'
echo '[.env.deploy]'; sed -n '1,20p' .env.deploy
echo '[be.env]'; sed -n '1,20p' be.env
echo '[BE/.env]'; sed -n '1,20p' BE/.env

docker compose --env-file .env.deploy up -d --build hrm-be hrm-fe hrm-proxy
sleep 2

echo '--- runtime env in hrm-be'
docker inspect hrm-be --format='{{range .Config.Env}}{{println .}}{{end}}' | grep '^DB_'

echo '--- login endpoint smoke (expect !=500)'
code=$(curl -s -o /tmp/login_resp.json -w '%{http_code}' -X POST https://anhsinhvienfpoly.click/api/v1/auth/login -H 'Content-Type: application/json' -d '{"company_email":"invalid@test.com","password":"x"}')
echo "http_code=$code"
head -c 300 /tmp/login_resp.json; echo
