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

        console.log('--- PHP LINT INSIDE CONTAINER ---');
        // We use find inside the container
        const res = await ssh.execCommand('docker exec hrm-be sh -c "find . -name \'*.php\' -exec php -l {} +"');
        console.log(res.stdout);
        console.log(res.stderr);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
