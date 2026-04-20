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

        console.log('--- EMPLOYEES IN DEPT 1 ---');
        const res = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -D HRM_SYSTEM -Nse "SELECT COUNT(*) FROM employment_histories WHERE department_id=1 AND is_current=1;"');
        console.log(res.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
