const fs = require('fs');
const path = require('path');

// Replaces mockDB terminology and snake_case API with new camelCase
const replacements = [
  { regex: /from\s+['"]@\/data\/mockDB\.js['"]/g, replace: "from '@/mock-data/index.js'" },
  { regex: /from\s+['"]\.\.\/data\/mockDB\.js['"]/g, replace: "from '@/mock-data/index.js'" },
  { regex: /from\s+['"]@\/data\/seedData\.js['"]/g, replace: "from '@/mock-data/index.js'" },
  
  // API replacements
  { regex: /employeesAPI\.getAll\(\)/g, replace: "mockEmployees" },
  { regex: /requestsAPI\.getAll\(\)/g, replace: "mockLeaveRequests" },
  { regex: /departmentsAPI\.getAll\(\)/g, replace: "mockDepartments" },
  { regex: /positionsAPI\.getAll\(\)/g, replace: "mockPositions" },
  { regex: /contractsAPI\.getAll\(\)/g, replace: "mockContracts" },
  { regex: /assetsAPI\.getAll\(\)/g, replace: "mockAssets" },
  { regex: /salariesAPI\.getAll\(\)/g, replace: "mockSalaryDetails" },
  { regex: /attendancesAPI\.getAll\(\)/g, replace: "mockAttendances" },
  { regex: /candidatesAPI\.getAll\(\)/g, replace: "mockCandidates" },
  { regex: /requestTypesAPI\.getAll\(\)/g, replace: "mockRequestTypes" },

  // Add more API stubs that might just be used
  { regex: /employeesAPI/g, replace: "mockEmployees" },
  { regex: /requestsAPI/g, replace: "mockLeaveRequests" },
  { regex: /departmentsAPI/g, replace: "mockDepartments" },
  { regex: /positionsAPI/g, replace: "mockPositions" },
  { regex: /contractsAPI/g, replace: "mockContracts" },
  { regex: /assetsAPI/g, replace: "mockAssets" },
  { regex: /salariesAPI/g, replace: "mockSalaryDetails" },
  { regex: /attendancesAPI/g, replace: "mockAttendances" },
  { regex: /candidatesAPI/g, replace: "mockCandidates" },
  { regex: /requestTypesAPI/g, replace: "mockRequestTypes" },

  // Snake Case to Camel Case mappings
  { regex: /\.employee_id\b/g, replace: ".employeeId" },
  { regex: /\.full_name\b/g, replace: ".fullName" },
  { regex: /\.employee_code\b/g, replace: ".employeeCode" },
  { regex: /\.department_id\b/g, replace: ".departmentId" },
  { regex: /\.position_id\b/g, replace: ".positionId" },
  { regex: /\.avatar_url\b/g, replace: ".avatarUrl" },
  
  { regex: /\.request_id\b/g, replace: ".requestId" },
  { regex: /\.request_type_id\b/g, replace: ".requestTypeId" },
  { regex: /\.requester_id\b/g, replace: ".requesterId" },
  { regex: /\.request_date\b/g, replace: ".requestDate" },
  { regex: /\.start_date\b/g, replace: ".startDate" },
  { regex: /\.end_date\b/g, replace: ".endDate" },

  { regex: /\.asset_id\b/g, replace: ".assetId" },
  { regex: /\.asset_code\b/g, replace: ".assetCode" },
  { regex: /\.asset_name\b/g, replace: ".assetName" },
  { regex: /\.assigned_to\b/g, replace: ".assignedTo" },
  { regex: /\.purchase_date\b/g, replace: ".purchaseDate" },

  { regex: /\.contract_id\b/g, replace: ".contractId" },
  { regex: /\.contract_code\b/g, replace: ".contractCode" },
  { regex: /\.contract_type\b/g, replace: ".contractType" },
  { regex: /\.basic_salary\b/g, replace: ".basicSalary" },

  { regex: /\.department_code\b/g, replace: ".departmentCode" },
  { regex: /\.department_name\b/g, replace: ".departmentName" },
  { regex: /\.manager_id\b/g, replace: ".managerId" },

  { regex: /\.position_code\b/g, replace: ".positionCode" },
  { regex: /\.position_name\b/g, replace: ".positionName" },

  { regex: /\.candidate_id\b/g, replace: ".candidateId" },
  { regex: /\.applied_position_id\b/g, replace: ".appliedPositionId" },
  { regex: /\.apply_date\b/g, replace: ".applyDate" },
  { regex: /\.cv_url\b/g, replace: ".cvUrl" },

  { regex: /\.attendance_id\b/g, replace: ".attendanceId" },
  { regex: /\.attendance_date\b/g, replace: ".attendanceDate" },
  { regex: /\.check_in_time\b/g, replace: ".checkInTime" },
  { regex: /\.check_out_time\b/g, replace: ".checkOutTime" },

  { regex: /\.salary_id\b/g, replace: ".salaryId" },
  { regex: /\.net_salary\b/g, replace: ".netSalary" },

  { regex: /request_type_name/g, replace: "requestTypeName" },
  
  // JSON structural fixes (e.g. employee.department = { departmentId... } in new schema)
  // This is too complex for regex so we only replace direct property accesses where it makes sense, and let some things break visibly to fix manually.
];

function processDirectory(dirPath) {
  const entries = fs.readdirSync(dirPath, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(dirPath, entry.name);

    if (entry.isDirectory()) {
      processDirectory(fullPath);
    } else if (entry.isFile() && (fullPath.endsWith('.vue') || fullPath.endsWith('.js'))) {
      let content = fs.readFileSync(fullPath, 'utf8');
      let modified = false;

      // Only refactor files that import mockDB to stay safe
      if (content.includes('@/data/mockDB.js') || content.includes('../data/mockDB.js') || content.includes('@/data/seedData.js')) {
        for (const replacement of replacements) {
          if (replacement.regex.test(content)) {
            content = content.replace(replacement.regex, replacement.replace);
            modified = true;
          }
        }
      }

      if (modified) {
        fs.writeFileSync(fullPath, content, 'utf8');
        console.log(`Refactored: ${fullPath}`);
      }
    }
  }
}

const targetDir = path.join(__dirname, 'src');
processDirectory(targetDir);
console.log('Mass regex replacement complete.');
