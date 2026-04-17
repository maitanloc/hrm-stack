# VPS Docker Deploy (BE + FE + MySQL + Caddy)

## 1) Clone repo on VPS

```bash
sudo mkdir -p /opt
cd /opt
git clone <YOUR_GITHUB_REPO_URL> hrm-stack
cd /opt/hrm-stack/deploy/vps
```

## 2) Install Docker and Compose plugin (Ubuntu)

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER
```

Đăng xuất rồi đăng nhập lại một lần để dùng `docker` không cần `sudo`.

## 3) Chuẩn bị file môi trường

```bash
cp .env.deploy.example .env.deploy
cp be.env.example be.env
```

Đặt cùng mật khẩu DB ở hai file:
- `.env.deploy` -> `MYSQL_ROOT_PASSWORD`
- `be.env` -> `DB_PASSWORD`

Đặt thêm:
- `JWT_SECRET` mạnh trong `be.env`
- `ATTENDANCE_PRECHECK_SECRET` mạnh trong `be.env`

## 4) Khởi động stack

```bash
docker compose --env-file .env.deploy up -d --build
```

## 5) Import dữ liệu + migration + quyền

```bash
chmod +x import-db.sh
./import-db.sh
```

Script đã tự chạy:
- Import `SQL_hackathon v4.sql`, `data.sql`
- Các migration attendance risk
- Hardening quyền để tránh lỗi `Permission denied: ATTENDANCE_VIEW (access)`

## 6) Kiểm tra nhanh

```bash
docker compose ps
curl -s http://127.0.0.1/api/v1/health
```

Kiểm tra route chấm công đã lên đúng (kết quả kỳ vọng `401` hoặc `422`, không phải `404`):

```bash
curl -i -X POST http://127.0.0.1/api/v1/attendance/precheck -H "Content-Type: application/json" -d '{}'
curl -i -X POST http://127.0.0.1/api/v1/attendance/pre-check -H "Content-Type: application/json" -d '{}'
curl -i -X POST http://127.0.0.1/api/v1/attendance/clock-in -H "Content-Type: application/json" -d '{}'
curl -i -X POST http://127.0.0.1/api/v1/attendance/clock-out -H "Content-Type: application/json" -d '{}'
```

Nếu response là `404`, chạy lại:

```bash
cd /opt/hrm-stack
chmod +x import-db.sh
docker compose --env-file .env.deploy up -d --build --remove-orphans
./import-db.sh
docker compose --env-file .env.deploy restart hrm-be hrm-fe hrm-proxy
```

Truy cập:
- `http://<IP_VPS>`
- `http://<IP_VPS>/api/v1/health`
- hoặc domain đã trỏ DNS.

## 7) DNS và firewall

- Mở TCP `80` và `443`.
- Nếu dùng domain, A record phải trỏ về VPS.
