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

        console.log('--- CONTENT OF .env INSIDE CONTAINER ---');
        const catRes = await ssh.execCommand('docker exec hrm-be cat .env');
        console.log(catRes.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Debug failed:', err);
    }
}
debug();
