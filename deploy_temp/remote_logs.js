const { NodeSSH } = require('node-ssh');

async function getLogs() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });

        console.log('--- BACKEND ERROR LOGS ---');
        // Check docker logs for the hrm-be container
        const logRes = await ssh.execCommand('docker logs hrm-be --tail 100');
        console.log(logRes.stdout);
        console.log(logRes.stderr);

        ssh.dispose();
    } catch (err) {
        console.error('Log fetch failed:', err);
    }
}
getLogs();
