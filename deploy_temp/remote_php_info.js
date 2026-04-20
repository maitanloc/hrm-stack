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

        console.log('--- PHP LIMITS ---');
        const res = await ssh.execCommand('docker exec hrm-be php -r "echo \'memory_limit: \' . ini_get(\'memory_limit\') . PHP_EOL; echo \'max_execution_time: \' . ini_get(\'max_execution_time\') . PHP_EOL;"');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
