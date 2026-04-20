const { NodeSSH } = require('node-ssh');

async function fix() {
    const ssh = new NodeSSH();
    console.log('Fixing deployment...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        
        console.log('Stopping and removing orphan containers...');
        const cleanup1 = await ssh.execCommand('docker compose down --remove-orphans', { cwd: '/var/www/hrm-stack' });
        console.log(cleanup1.stdout, cleanup1.stderr);
        
        // Just in case there are lingering containers from old naming
        const cleanup2 = await ssh.execCommand('docker rm -f hrm-be hrm-fe hrm-proxy hrm-mysql');
        console.log(cleanup2.stdout, cleanup2.stderr);

        console.log('Re-launching application...');
        const upRes = await ssh.execCommand('docker compose up -d --build --remove-orphans', { cwd: '/var/www/hrm-stack' });
        console.log('Docker UP OUT:', upRes.stdout);
        console.log('Docker UP ERR:', upRes.stderr);
        
        ssh.dispose();
    } catch(err) {
        console.error(err);
    }
}
fix();
