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

        console.log('--- STORAGE ERROR LOGS ---');
        const logRes = await ssh.execCommand('docker exec hrm-be ls -R storage/logs');
        console.log(logRes.stdout);
        
        const catRes = await ssh.execCommand('docker exec hrm-be tail -n 50 storage/logs/app.log');
        console.log(catRes.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Log fetch failed:', err);
    }
}
getLogs();
