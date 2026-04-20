const { NodeSSH } = require('node-ssh');

async function debug() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });

        console.log('--- TESTING CONNECTION FROM BE CONTAINER ---');
        // We use a simplified script to avoid complex shell escaping issues
        await ssh.execCommand('cat <<PHP > /var/www/hrm-stack/BE/test_db_vps.php
<?php
try {
    $pdo = new PDO("mysql:host=mysql;dbname=HRM_SYSTEM;charset=utf8mb4", "hrm_user", "hrm_pass_2026_secure");
    echo "SUCCESS";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage();
}
PHP', { cwd: '/var/www/hrm-stack' });
        
        const res = await ssh.execCommand('docker exec hrm-be php test_db_vps.php');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Debug failed:', err);
    }
}
debug();
