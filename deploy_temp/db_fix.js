console.error('Deprecated: deploy_temp/db_fix.js is blocked because it bypasses utf8mb4-safe import validation. Use import-db.sh or deploy/vps/import-db.sh instead.');
process.exit(1);

const { NodeSSH } = require('node-ssh');

async function fix() {
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

        console.log('Ensuring BE folder has .env...');
        await ssh.execCommand('cp .env BE/.env', { cwd: '/var/www/hrm-stack' });

        console.log('Importing database data into hrm_db (removing USE statement)...');
        // We read data.sql, remove the USE line, and pipe to mysql
        await ssh.execCommand('sed "s/USE HRM_SYSTEM;//g" BE/data.sql | docker exec -i hrm-mysql mysql -uhrm_user -pchange_user_password hrm_db', { cwd: '/var/www/hrm-stack' });
        
        console.log('Restarting backend to refresh connection...');
        await ssh.execCommand('docker compose restart hrm-be', { cwd: '/var/www/hrm-stack' });

        console.log('Verifying tables in hrm_db...');
        const res = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password hrm_db -e "show tables;"');
        console.log('Tables found:\n', res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Error during fix:', err);
    }
}

fix();
