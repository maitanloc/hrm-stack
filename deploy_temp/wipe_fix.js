console.error('Deprecated: deploy_temp/wipe_fix.js is blocked because it bypasses utf8mb4-safe import validation. Use import-db.sh or deploy/vps/import-db.sh instead.');
process.exit(1);

const { NodeSSH } = require('node-ssh');

async function wipeAndFix() {
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

        console.log('1. Wiping containers and volumes for a clean start...');
        await ssh.execCommand('docker compose down -v', { cwd: '/var/www/hrm-stack' });

        console.log('2. Starting services fresh with current .env...');
        await ssh.execCommand('docker compose up -d', { cwd: '/var/www/hrm-stack' });

        console.log('3. Waiting for MySQL to initialize (30s)...');
        await new Promise(r => setTimeout(r, 30000));

        console.log('4. Importing data...');
        // Copy to container and source
        await ssh.execCommand('docker cp BE/data.sql hrm-mysql:/tmp/data.sql', { cwd: '/var/www/hrm-stack' });
        const importRes = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "source /tmp/data.sql"');
        console.log('Import Result:', importRes.stdout || 'Done');
        if (importRes.stderr) console.error('Import Error:', importRes.stderr);

        console.log('5. Granting permissions...');
        await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO \'hrm_user\'@\'%\'; FLUSH PRIVILEGES;"');

        console.log('6. Restarting backend...');
        await ssh.execCommand('docker compose restart hrm-be', { cwd: '/var/www/hrm-stack' });

        console.log('7. Final Verification...');
        const tableRes = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM -e "show tables;"');
        console.log('Tables found:\n', tableRes.stdout || 'None');

        ssh.dispose();
    } catch (err) {
        console.error('Wipe and fix failed:', err);
    }
}

wipeAndFix();
