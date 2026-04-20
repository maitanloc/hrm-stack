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

        console.log('--- FILE EXISTENCE ---');
        const lsRes = await ssh.execCommand('ls -l /var/www/hrm-stack/BE/app/Controllers/Api/V1/WorkforceController.php');
        console.log(lsRes.stdout);

        console.log('\n--- CODE AROUND LINE 467 ---');
        const res = await ssh.execCommand('sed -n "460,480p" /var/www/hrm-stack/BE/app/Controllers/Api/V1/WorkforceController.php');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
