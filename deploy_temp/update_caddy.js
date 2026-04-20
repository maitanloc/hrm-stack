const { NodeSSH } = require('node-ssh');

async function updateCaddy() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS to update Caddy...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected.');

        const domain = 'anhsinhvientpoly.click';
        const caddyfileContent = `
{
    email maitanloc@gmail.com
}

# Listen on both HTTP (IP/internal) and the Domain
:80, ${domain} {
    encode gzip

    # API Routes
    @api path /api/*
    handle @api {
        # Note: In docker, hrm-be volume is mapped to /var/www/html
        root * /var/www/html/public
        file_server
        php_fastcgi hrm-be:9000
    }

    # Frontend
    handle {
        reverse_proxy hrm-fe:80
    }
}
`;

        const targetPath = '/var/www/hrm-stack/docker/proxy/Caddyfile';
        console.log('Updating Caddyfile...');
        await ssh.execCommand(`echo "${caddyfileContent.replace(/"/g, '\\"')}" > ${targetPath}`);

        console.log('Restarting Caddy...');
        await ssh.execCommand('docker compose restart hrm-proxy', { cwd: '/var/www/hrm-stack' });

        console.log('Done.');
        ssh.dispose();
    } catch (err) {
        console.error('Update Failed:', err);
    }
}

updateCaddy();
