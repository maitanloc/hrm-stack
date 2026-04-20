const { NodeSSH } = require('node-ssh');
const path = require('path');

async function run() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });

        const testScript = `<?php
require_once 'bootstrap.php';
try {
    \$emp = new App\\Models\\Employee();
    \$res = \$emp->paginateList(0, 1000, null, null, 1);
    echo "PAGINATE SUCCESS: " . count(\$res['items']) . " employees found";
} catch (Throwable \$e) {
    echo "PAGINATE FAILED: " . \$e->getMessage();
}
?>`;
        await ssh.execCommand('echo "' + testScript.replace(/"/g, '\\"') + '" > /var/www/hrm-stack/BE/test_paginate.php');
        const res = await ssh.execCommand('docker exec hrm-be php test_paginate.php');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Remote run failed:', err);
    }
}
run();
