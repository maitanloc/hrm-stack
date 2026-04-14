# Run `hrm` locally, connect to VPS

## 1) Open tunnel to VPS MySQL

```bash
chmod +x deploy/local-hrm/start-tunnel.sh
deploy/local-hrm/start-tunnel.sh 157.66.46.75 root
```

Keep this terminal running.

## 2) Start `hrm` in local Docker

```bash
cd /Users/vhozang/Hackathon/hrm
cp /Users/vhozang/Hackathon/deploy/local-hrm/.env.vps.example .env.vps
```

Edit `.env.vps`, then run:

```bash
docker compose --env-file .env.vps -f compose.yaml -f /Users/vhozang/Hackathon/deploy/local-hrm/compose.vps.override.yaml up -d --build ollama backend
```

## 3) Test

```bash
curl http://localhost:8000/health
```
