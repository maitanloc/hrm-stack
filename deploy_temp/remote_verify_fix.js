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

        console.log('Running build test in container...');
        const res = await ssh.execCommand('docker exec hrm-be php test_build_vps.php');
        console.log('RESULT:', res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Remote run failed:', err);
    }
}
run();
