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

        console.log('Uploading test script...');
        await ssh.putFile(path.join(__dirname, '../BE/test_paginate_vps.php'), '/var/www/hrm-stack/BE/test_paginate_vps.php');
        
        console.log('Running test in container...');
        const res = await ssh.execCommand('docker exec hrm-be php test_paginate_vps.php');
        console.log('RESULT:', res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Remote run failed:', err);
    }
}
run();
