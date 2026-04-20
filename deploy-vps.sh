#!/bin/bash
set -e

echo "=== HRM Stack VPS Deployment ==="

# Create deploy directory
sudo mkdir -p /opt
cd /opt

# Clone repo if it doesn't exist
if [ ! -d "hrm-stack" ]; then
    echo "📥 Cloning repository..."
    git clone https://github.com/maitanloc/hrm-stack.git
    cd hrm-stack
else
    echo "📦 Updating repository..."
    cd hrm-stack
fi

# Fetch latest changes
git fetch origin main
git checkout main
git pull origin main

# Navigate to VPS deploy directory
cd deploy/vps

# Setup environment files if they don't exist
if [ ! -f ".env.deploy" ]; then
    echo "⚙️  Setting up environment files..."
    cp .env.deploy.example .env.deploy
fi

if [ ! -f "be.env" ]; then
    cp be.env.example be.env
fi

# Start Docker deployment
echo "🐳 Starting Docker deployment..."
docker compose --env-file .env.deploy down 2>/dev/null || true
docker compose --env-file .env.deploy up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 10

# Go back to root directory and run migrations
cd /opt/hrm-stack
chmod +x import-db.sh
echo "🗄️  Running database migrations and setup..."
./import-db.sh

# Check status
echo ""
echo "✅ Deployment completed!"
echo ""
docker compose --env-file deploy/vps/.env.deploy ps
echo ""
echo "🔍 Testing endpoints..."
curl -s http://127.0.0.1/api/v1/health | head -c 200
echo ""
echo ""
echo "📝 Next steps:"
echo "1. Login to VPS: ssh root@157.66.46.75"
echo "2. Check logs: docker compose --env-file deploy/vps/.env.deploy logs -f hrm-be"
echo "3. Access: http://157.66.46.75 or https://anhsinhvienfpoly.click"
