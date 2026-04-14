import fs from 'fs';
import path from 'path';

const dbPath = './db.json';
const empsPath = './src/mock-data/employees.json';
const deptsPath = './src/mock-data/departments.json';

try {
  const db = JSON.parse(fs.readFileSync(dbPath, 'utf8'));
  const emps = JSON.parse(fs.readFileSync(empsPath, 'utf8'));
  const depts = JSON.parse(fs.readFileSync(deptsPath, 'utf8'));

  // Sync employees
  db.employees = emps.map(e => ({
    id: String(e.employeeId),
    employeeId: e.employeeId, // Keep original number too just in case
    name: e.fullName,
    fullName: e.fullName,
    email: e.companyEmail,
    companyEmail: e.companyEmail,
    role: (e.role || 'staff').toLowerCase(),
    deptId: String(e.department?.departmentId || e.departmentId || ''),
    managerId: String(e.managerId || ''), // Critical for notifications
    position: e.position?.positionName || e.position || 'Nhân viên',
    password: e.password || '123456',
    employeeCode: e.employeeCode
  }));

  // Sync departments
  db.departments = depts.map(d => ({
    id: String(d.departmentId),
    departmentId: d.departmentId,
    departmentName: d.departmentName,
    name: d.departmentName || d.name,
    manager: d.managerName || d.manager || 'N/A',
    count: emps.filter(e => (e.department?.departmentId || e.departmentId) === d.departmentId).length
  }));

  // Fix attendance employeeId to be strings
  if (db.attendances) {
    db.attendances.forEach(a => {
       if (typeof a.employeeId === 'string' && a.employeeId.startsWith('NV')) {
          const num = parseInt(a.employeeId.replace('NV', ''));
          if (!isNaN(num)) a.employeeId = String(num);
       } else if (typeof a.employeeId === 'number') {
          a.employeeId = String(a.employeeId);
       }
    });
  }

  fs.writeFileSync(dbPath, JSON.stringify(db, null, 2), 'utf8');
  console.log('SYNC SUCCESSFUL');
} catch (err) {
  console.error('SYNC ERROR:', err.message);
  process.exit(1);
}
