console.error('Deprecated: deploy_temp/clean_deploy.js is blocked because it bypasses utf8mb4-safe import validation. Use import-db.sh or deploy/vps/import-db.sh instead.');
process.exit(1);

const { NodeSSH } = require('node-ssh');

async function cleanStart() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS for clean start...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected.');

        console.log('Stopping and removing containers + volumes...');
        await ssh.execCommand('docker compose down -v', { cwd: '/var/www/hrm-stack' });

        console.log('Starting fresh...');
        await ssh.execCommand('docker compose up -d --build', { cwd: '/var/www/hrm-stack' });

        console.log('Waiting for MySQL to initialize (15s)...');
        await new Promise(resolve => setTimeout(resolve, 15000));

        console.log('Verifying connection by creating database and granting privileges (as root)...');
        const resInit = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "CREATE DATABASE IF NOT EXISTS hrm_db; GRANT ALL PRIVILEGES ON hrm_db.* TO \'hrm_user\'@\'%\'; FLUSH PRIVILEGES;"');
        console.log('Init output:', resInit.stdout, 'Err:', resInit.stderr);

        console.log('Importing data.sql (root to avoid permission issues during import)...');
        const resImport = await ssh.execCommand('cat BE/data.sql | docker exec -i hrm-mysql mysql -uroot -pchange_root_password hrm_db', { cwd: '/var/www/hrm-stack' });
        console.log('Import output:', resImport.stdout, 'Err:', resImport.stderr);

        console.log('Final verification check (tables count):');
        const resTables = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password hrm_db -e "SHOW TABLES;"');
        console.log('Tables found:\n', resTables.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Error during clean start:', err);
    }
}

cleanStart();
