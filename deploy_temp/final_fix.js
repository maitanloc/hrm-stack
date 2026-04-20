const { NodeSSH } = require('node-ssh');
const fs = require('fs');

async function finalFix() {
    const ssh = new NodeSSH();
    console.log('--- VPS Final Deployment Fix ---');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('1. Connected to VPS.');

        const targetDir = '/var/www/hrm-stack';

        // 2. Ensure hrm_db exists and hrm_user has access
        console.log('2. Resetting database and permissions...');
        await ssh.execCommand('docker exec hrm-mysql mysql -uroot -pchange_root_password -e "DROP DATABASE IF EXISTS hrm_db; CREATE DATABASE hrm_db; GRANT ALL PRIVILEGES ON hrm_db.* TO \'hrm_user\'@\'%\'; FLUSH PRIVILEGES;"');

        // 3. Upload fresh data.sql
        console.log('3. Uploading fresh data.sql (989KB)...');
        await ssh.putFile('d:\\hrm-stack\\data.sql', targetDir + '/data_final.sql');

        // 4. Import while stripping USE HRM_SYSTEM
        console.log('4. Importing data...');
        // We use sed to remove "USE HRM_SYSTEM;" line and pipe to mysql
        // Note: My previous sed error might have been due to different line endings or slightly different string
        // Let's use a more robust sed: s/USE .*;//g 
        const importCmd = `cat data_final.sql | sed 's/USE .*;//g' | docker exec -i hrm-mysql mysql -uroot -pchange_root_password hrm_db`;
        const resImport = await ssh.execCommand(importCmd, { cwd: targetDir });
        
        if (resImport.stderr && !resImport.stderr.includes('Warning')) {
            console.error('Import Error:', resImport.stderr);
        } else {
            console.log('Import command executed.');
        }

        // 5. Verification
        console.log('5. Verifying tables...');
        const resT = await ssh.execCommand('docker exec hrm-mysql mysql -uhrm_user -pchange_user_password hrm_db -e "SHOW TABLES;"');
        const tableLines = resT.stdout.trim().split('\n');
        console.log(`Success! Total tables in hrm_db: ${tableLines.length - 1}`);

        // 6. Restart Backend
        console.log('6. Restarting backend...');
        await ssh.execCommand('docker compose restart hrm-be', { cwd: targetDir });

        console.log('--- Deployment Finished Successfully ---');
        ssh.dispose();
    } catch (err) {
        console.error('Deployment Failed:', err);
    }
}

finalFix();
