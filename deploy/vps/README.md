# VPS Docker Deploy (BE + FE + MySQL + HTTPS)

This folder contains all files you need to upload to your VPS for deployment.

## 1) Folder structure on VPS

Create `/opt/hrm-stack` and upload:

- `BE/`
- `FE/`
- `SQL_hackathon v4.sql`
- `data.sql`
- all files in this `deploy/vps/` folder

Final structure (example):

```text
/opt/hrm-stack
  BE/
  FE/
  SQL_hackathon v4.sql
  data.sql
  docker-compose.yml
  Dockerfile.be
  Dockerfile.fe
  Caddyfile
  nginx-fe.conf
  be.env
  .env.deploy
  import-db.sh
```

## 2) Prepare env files

Copy and edit:

```bash
cp .env.deploy.example .env.deploy
cp be.env.example be.env
```

Set the same DB password in both files:

- `.env.deploy` -> `MYSQL_ROOT_PASSWORD=...`
- `be.env` -> `DB_PASSWORD=...`

Also set a strong `JWT_SECRET` in `be.env`.

## 3) Start stack

```bash
docker compose --env-file .env.deploy up -d --build
```

## 4) Import database

```bash
chmod +x import-db.sh
./import-db.sh
```

## 5) Verify

```bash
docker compose ps
curl -s http://127.0.0.1/api/v1/health
```

Open:

- `https://anhsinhvienfpoly.click`
- `https://anhsinhvienfpoly.click/api/v1/health`

## 6) DNS and firewall

- Domain A record must point to your VPS IP.
- Open TCP `80` and `443`.

## 7) Notes

- Do not upload `FE/node_modules` and `FE/dist`.
- If Caddy cannot issue SSL, check DNS and firewall first.
