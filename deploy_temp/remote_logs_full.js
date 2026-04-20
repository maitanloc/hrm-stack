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

        console.log('--- RECENT PHP ERRORS ---');
        // We can grep for "PHP Fatal error" or "Uncaught" in the logs
        const logRes = await ssh.execCommand('docker logs hrm-be 2>&1 | grep -iE "fatal|error|exception" | tail -n 20');
        console.log(logRes.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Log fetch failed:', err);
    }
}
getLogs();
