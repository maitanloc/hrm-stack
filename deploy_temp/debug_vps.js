const { NodeSSH } = require('node-ssh');

async function debugVps() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('--- DOCKER STATUS ---');
        const ps = await ssh.execCommand('docker ps -a');
        console.log(ps.stdout);

        console.log('\n--- PROXY LOGS (Caddy) ---');
        const caddyLogs = await ssh.execCommand('docker logs hrm-proxy --tail 20');
        console.log(caddyLogs.stdout);
        console.log(caddyLogs.stderr);

        console.log('\n--- BACKEND LOGS ---');
        const beLogs = await ssh.execCommand('docker logs hrm-be --tail 20');
        console.log(beLogs.stdout);
        console.log(beLogs.stderr);

        console.log('\n--- PORT CHECK ---');
        const ports = await ssh.execCommand('netstat -tulpn | grep -E "80|443"');
        console.log(ports.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Debug failed:', err);
    }
}
debugVps();
