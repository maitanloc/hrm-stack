const { NodeSSH } = require('node-ssh');

async function debug() {
    const ssh = new NodeSSH();
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });

        console.log('--- BACKEND ENV ---');
        const envRes = await ssh.execCommand('docker exec hrm-be env | grep DB_', { cwd: '/var/www/hrm-stack' });
        console.log(envRes.stdout);

        console.log('\n--- MYSQL USERS ---');
        const userRes = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -e "SELECT User, Host FROM mysql.user;"', { cwd: '/var/www/hrm-stack' });
        console.log(userRes.stdout);

        console.log('\n--- MYSQL GRANTS ---');
        const grantRes = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -e "SHOW GRANTS FOR \'hrm_user\'@\'%\';"', { cwd: '/var/www/hrm-stack' });
        console.log(grantRes.stdout);

        console.log('\n--- DATABASES ---');
        const dbRes = await ssh.execCommand('docker exec hrm-mysql mysql -uroot -phrm_root_2026_secure -e "SHOW DATABASES;"', { cwd: '/var/www/hrm-stack' });
        console.log(dbRes.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Debug failed:', err);
    }
}
debug();
