const { NodeSSH } = require('node-ssh');

async function manageRoles() {
    const ssh = new NodeSSH();
    console.log('Connecting to VPS...');
    try {
        await ssh.connect({
            host: '157.66.46.75',
            username: 'root',
            password: 'danh2008',
            port: 22
        });
        console.log('Connected.');

        const dbConfig = '-uhrm_user -pchange_user_password HRM_SYSTEM';

        console.log('\n--- Checking Roles for huong.pham@company.com ---');
        const checkSql = `
            SELECT e.employee_id, e.full_name, e.employee_code, r.role_code, r.role_name
            FROM employees e
            LEFT JOIN employee_roles er ON e.employee_id = er.employee_id AND er.is_active = 1
            LEFT JOIN roles r ON er.role_id = r.role_id
            WHERE e.company_email = 'huong.pham@company.com';
        `.trim().replace(/\s+/g, ' ');
        
        const res1 = await ssh.execCommand(`docker exec hrm-mysql mysql ${dbConfig} -e "${checkSql}"`);
        console.log(res1.stdout || 'Employee not found or no roles.');

        console.log('\n--- Creating New Role: TEST_ROLE ---');
        const createSql = `
            INSERT INTO roles (role_code, role_name, description, is_system_role)
            VALUES ('TEST_ROLE', 'Vai trò Kiểm thử', 'Phần quyền dành riêng cho việc test của User', 0)
            ON DUPLICATE KEY UPDATE role_name = 'Vai trò Kiểm thử';
        `.trim().replace(/\s+/g, ' ');
        
        await ssh.execCommand(`docker exec hrm-mysql mysql ${dbConfig} -e "${createSql}"`);
        
        console.log('Role TEST_ROLE has been created/verified.');

        console.log('\n--- Listing all roles for verification ---');
        const res2 = await ssh.execCommand(`docker exec hrm-mysql mysql ${dbConfig} -e "SELECT role_code, role_name FROM roles;"`);
        console.log(res2.stdout);

        ssh.dispose();
    } catch (err) {
        console.error('Operation failed:', err);
    }
}

manageRoles();
