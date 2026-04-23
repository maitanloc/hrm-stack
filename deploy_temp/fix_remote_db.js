console.error('Deprecated: deploy_temp/fix_remote_db.js is blocked because it bypasses utf8mb4-safe import validation. Use import-db.sh or deploy/vps/import-db.sh instead.');
process.exit(1);

const { NodeSSH } = require('node-ssh');

async function cleanStart() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS for clean start (Database Fix)...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected.');

        console.log('Starting fresh (already removed volumes mostly, just ensuring hrm_db is ready)...');
        // No need to down -v again if it was just done, but let's ensure it's up
        await ssh.execCommand('docker compose up -d', { cwd: '/var/www/hrm-stack' });

        console.log('Waiting for MySQL (5s)...');
        await new Promise(resolve => setTimeout(resolve, 5000));

        console.log('Creating database and granting privileges...');
        await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS hrm_db; GRANT ALL PRIVILEGES ON hrm_db.* TO \'hrm_user\'@\'%\'; FLUSH PRIVILEGES;"');

        console.log('Importing data.sql with USE statement replacement...');
        // We replace USE HRM_SYSTEM; with USE hrm_db; to match backend config
        const resImport = await ssh.execCommand("sed 's/USE HRM_SYSTEM;/USE hrm_db;/g' BE/data.sql | docker exec -i hrm-mysql mysql -uroot -pchange_root_password hrm_db", { cwd: '/var/www/hrm-stack' });
        console.log('Import output:', resImport.stdout, 'Err:', resImport.stderr);

        console.log('Final verification check (tables count):');
        const resTables = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password hrm_db -e "SHOW TABLES;"');
        console.log('Tables found:\n', resTables.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Error during database fix:', err);
    }
}

cleanStart();
