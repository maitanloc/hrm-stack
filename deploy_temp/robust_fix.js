console.error('Deprecated: deploy_temp/robust_fix.js is blocked because it bypasses utf8mb4-safe import validation. Use import-db.sh or deploy/vps/import-db.sh instead.');
process.exit(1);

const { NodeSSH } = require('node-ssh');

async function robustFix() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected.');

        console.log('1. Fixing .env location and content...');
        // Ensure .env exists in BE folder
        await ssh.execCommand('cp .env BE/.env', { cwd: '/var/www/hrm-stack' });
        
        // Let's force DB_DATABASE to HRM_SYSTEM because that is what data.sql uses
        await ssh.execCommand("sed -i 's/DB_DATABASE=.*/DB_DATABASE=HRM_SYSTEM/' BE/.env", { cwd: '/var/www/hrm-stack' });
        await ssh.execCommand("sed -i 's/MYSQL_DATABASE=.*/MYSQL_DATABASE=HRM_SYSTEM/' .env", { cwd: '/var/www/hrm-stack' });

        console.log('2. Importing data into HRM_SYSTEM...');
        // We run the data.sql which includes "CREATE DATABASE IF NOT EXISTS HRM_SYSTEM" and "USE HRM_SYSTEM"
        // We use root to ensure we can create databases
        await ssh.execCommand('docker exec -i hrm-mysql mysql -uroot -pchange_root_password < BE/data.sql', { cwd: '/var/www/hrm-stack' });
        
        console.log('3. Granting permissions to hrm_user on HRM_SYSTEM...');
        await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO \'hrm_user\'@\'%\'; FLUSH PRIVILEGES;"');

        console.log('4. Restarting services...');
        await ssh.execCommand('docker compose up -d', { cwd: '/var/www/hrm-stack' });
        await ssh.execCommand('docker compose restart hrm-be', { cwd: '/var/www/hrm-stack' });

        console.log('5. Verification...');
        const verifyDB = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM -e "show tables;"');
        console.log('Tables in HRM_SYSTEM:\n', verifyDB.stdout || 'None found (Error?)');
        
        const logs = await ssh.execCommand('docker logs --tail 10 hrm-be');
        console.log('Recent BE logs:\n', logs.stdout || logs.stderr);

        ssh.dispose();
    } catch (err) {
        console.error('Robust fix failed:', err);
    }
}

robustFix();
