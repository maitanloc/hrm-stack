const { NodeSSH } = require('node-ssh');

async function check() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });

        console.log('--- MODELS ON VPS ---');
        const res = await ssh.execCommand('docker exec hrm-be ls app/Models');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
