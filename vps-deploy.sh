#!/bin/bash
set -e

echo "========================================"
echo "  HRM Stack VPS Deployment Script"
echo "========================================"
echo "IP: 157.66.46.75"
echo "User: root"
echo "Date: $(date)"
echo ""

# Navigate to deployment directory
cd /opt || mkdir -p /opt; cd /opt

# Clone or update repository
if [ ! -d "hrm-stack" ]; then
    echo "📥 Cloning hrm-stack repository..."
    git clone https://github.com/maitanloc/hrm-stack.git
    cd hrm-stack
else
    echo "📦 Updating existing repository..."
    cd hrm-stack
    git fetch origin
fi

# Update to latest main branch
echo "🔄 Checking out main branch..."
git checkout main
git pull origin main

echo "✅ Repository updated successfully"
echo ""

# Navigate to VPS deploy directory
cd deploy/vps

# Setup environment files
echo "⚙️  Configuring environment files..."
if [ ! -f ".env.deploy" ]; then
    cp .env.deploy.example .env.deploy
    echo "  ✓ Created .env.deploy"
fi

if [ ! -f "be.env" ]; then
    cp be.env.example be.env
    echo "  ✓ Created be.env"
fi

echo ""
echo "🐳 Starting Docker Compose..."
docker compose --env-file .env.deploy down 2>/dev/null || true
docker compose --env-file .env.deploy up -d --build

echo "⏳ Waiting for services to start (15 seconds)..."
sleep 15

echo ""
echo "🗄️  Running database migrations..."
cd /opt/hrm-stack
chmod +x import-db.sh
./import-db.sh

echo ""
echo "✅ =============== DEPLOYMENT COMPLETE ==============="
echo ""
echo "📊 Service Status:"
docker compose --env-file deploy/vps/.env.deploy ps

echo ""
echo "🔍 Testing API health endpoint..."
curl -s http://127.0.0.1/api/v1/health | head -c 200 && echo "" || echo "⚠️  API not responding yet"

echo ""
echo "================== ACCESS YOUR APP =================="
echo "🌐 Frontend: http://157.66.46.75"
echo "🌐 Domain: https://anhsinhvienfpoly.click"
echo "📡 API: http://157.66.46.75/api/v1"
echo "✅ Health: curl http://157.66.46.75/api/v1/health"
echo ""
echo "📝 Useful Commands:"
echo "   View logs: docker compose -f deploy/vps/docker-compose.yml logs -f hrm-be"
echo "   Restart: docker compose -f deploy/vps/docker-compose.yml restart"
echo "   Stop: docker compose -f deploy/vps/docker-compose.yml down"
echo "====================================================="
