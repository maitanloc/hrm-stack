const { NodeSSH } = require('node-ssh');
const fs = require('fs');
const path = require('path');

async function deploy() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS 157.66.46.75 ...');
    
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected to VPS!');
        
        console.log('Ensure target directory exists...');
        await ssh.execCommand('mkdir -p /var/www/hrm-stack');
        
        console.log('Uploading hrm-stack.tar.gz...');
        const localTarPath = path.join(__dirname, '../hrm-stack.tar.gz');
        await ssh.putFile(localTarPath, '/var/www/hrm-stack/hrm-stack.tar.gz');
        console.log('Upload complete.');
        
        console.log('Extracting on VPS...');
        const extractResult = await ssh.execCommand('tar -xzf hrm-stack.tar.gz', { cwd: '/var/www/hrm-stack' });
        if (extractResult.stderr) {
            console.log('Extraction warnings/err:', extractResult.stderr);
        }
        
        console.log('Installing Docker and Docker Compose (if needed)...');
        await ssh.execCommand('if ! command -v docker &> /dev/null; then curl -fsSL https://get.docker.com -o get-docker.sh && sh get-docker.sh; fi');
        await ssh.execCommand('apt-get update && apt-get install -y docker-compose-plugin');

        console.log('Setting up env for production...');
        // Copy to root for docker compose
        await ssh.execCommand('cp .env.deploy .env', { cwd: '/var/www/hrm-stack' });
        // Copy to BE for the PHP app (mounted as volume)
        await ssh.execCommand('cp .env.deploy BE/.env', { cwd: '/var/www/hrm-stack' });

        console.log('Stopping existing services and WIPING VOLUMES for clean install...');
        await ssh.execCommand('docker compose down -v', { cwd: '/var/www/hrm-stack' });
        console.log('Running docker compose up -d --build...');
        const dockerRes = await ssh.execCommand('docker compose --env-file .env up -d --build', { cwd: '/var/www/hrm-stack' });
        console.log('Docker output:\n', dockerRes.stdout);

        console.log('Running Database Import/Migration on VPS...');
        const importRes = await ssh.execCommand('sh import-db.sh', { cwd: '/var/www/hrm-stack' });
        console.log('Import output:\n', importRes.stdout);
        if (importRes.stderr) console.log('Import stderr:\n', importRes.stderr);

        console.log('FORCE FIX: Granting MySQL permissions on VPS...');
        // Đưa script fix vào container mysql và chạy
        await ssh.execCommand('docker cp BE/scripts/fix-mysql-auth.sh hrm-mysql:/tmp/fix-auth.sh', { cwd: '/var/www/hrm-stack' });
        const fixAuthRes = await ssh.execCommand('docker exec hrm-mysql bash /tmp/fix-auth.sh', { cwd: '/var/www/hrm-stack' });
        console.log('Fix Auth Output:\n', fixAuthRes.stdout);
        if (fixAuthRes.stderr) console.log('Fix Auth Error:\n', fixAuthRes.stderr);

        console.log('Running DB repair script on VPS...');
        await ssh.execCommand('docker exec hrm-be php scripts/fix_encoding_final.php', { cwd: '/var/www/hrm-stack' });

        console.log('Deployment completed successfully! Check at: anhsinhvienfpoly.click');

        ssh.dispose();
    } catch(err) {
        console.error('Deployment failed:', err);
    }
}

deploy();
