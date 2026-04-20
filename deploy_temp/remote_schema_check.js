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

        console.log('--- SHIFT_ASSIGNMENTS SCHEMA ---');
        const res1 = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -D HRM_SYSTEM -e "DESCRIBE shift_assignments;"');
        console.log(res1.stdout);

        console.log('\n--- SHIFT_TYPES SCHEMA ---');
        const res2 = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -D HRM_SYSTEM -e "DESCRIBE shift_types;"');
        console.log(res2.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Check failed:', err);
    }
}
check();
