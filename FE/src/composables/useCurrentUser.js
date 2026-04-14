/**
 * useCurrentUser — Composable trung tâm cung cấp thông tin user đang đăng nhập.
 * Đọc session/local storage (ghi lúc Login) và ưu tiên dữ liệu thật từ auth payload.
 */
import { computed } from 'vue';
import { AUTH_USER_KEY } from '@/services/runtimeConfig.js';
import { getSessionItem } from '@/services/session.js';

export function useCurrentUser() {
  const pickFirst = (...values) => {
    for (const value of values) {
      if (value === null || value === undefined) continue;
      if (typeof value === 'string' && value.trim() === '') continue;
      return value;
    }
    return '';
  };

  const toOptionalNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
  };

  // Các key cơ bản được lưu lúc login
  const storedId    = getSessionItem('userId');        // employeeId (number dạng string)
  const storedName  = getSessionItem('userName');
  const storedEmail = getSessionItem('userEmail');
  const storedRole  = getSessionItem('userRole');      // 'employee' | 'manager' | 'director' | 'admin'
  const storedDept  = getSessionItem('userDeptId');
  const storedDeptName = getSessionItem('userDeptName');
  const storedPosition = getSessionItem('userPosition');
  const storedPhone = getSessionItem('userPhone');
  const storedAuthUser = (() => {
    try {
      const raw = getSessionItem(AUTH_USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  })();

  // --- Tìm bản ghi đầy đủ trong mock-data ---
  const fullProfile = computed(() => {
    const authEmployeeId = storedAuthUser?.employee_id ?? storedAuthUser?.employeeId;
    const authEmail = storedAuthUser?.company_email ?? storedAuthUser?.companyEmail ?? storedEmail;

    if (storedAuthUser && (authEmployeeId || authEmail)) {
      const departmentId = pickFirst(
        storedAuthUser.department_id,
        storedAuthUser.departmentId,
        storedDept
      );
      const departmentName = pickFirst(
        storedAuthUser.department_name,
        storedAuthUser.departmentName,
        storedDeptName
      );
      const positionName = pickFirst(
        storedAuthUser.position_name,
        storedAuthUser.positionName,
        storedPosition
      );

      return {
        employeeId: pickFirst(storedAuthUser.employee_id, storedAuthUser.employeeId),
        employeeCode: pickFirst(storedAuthUser.employee_code, storedAuthUser.employeeCode),
        fullName: pickFirst(storedAuthUser.full_name, storedAuthUser.fullName, storedName),
        companyEmail: pickFirst(storedAuthUser.company_email, storedAuthUser.companyEmail, storedEmail),
        phoneNumber: pickFirst(storedAuthUser.phone_number, storedAuthUser.phoneNumber, storedPhone),
        dateOfBirth: pickFirst(storedAuthUser.date_of_birth, storedAuthUser.dateOfBirth),
        gender: pickFirst(storedAuthUser.gender),
        hireDate: pickFirst(storedAuthUser.hire_date, storedAuthUser.hireDate),
        status: pickFirst(storedAuthUser.status, 'ĐANG_LÀM_VIỆC'),
        managerId: pickFirst(storedAuthUser.manager_id, storedAuthUser.managerId, ''),
        managerName: pickFirst(storedAuthUser.manager_name, storedAuthUser.managerName, ''),
        departmentId,
        department: {
          departmentId,
          departmentName,
        },
        position: {
          positionName,
        },
        role: pickFirst(storedRole?.toUpperCase?.(), 'EMPLOYEE'),
        baseLeaveDays: pickFirst(storedAuthUser.base_leave_days, storedAuthUser.baseLeaveDays, 12),
      };
    }

    if (!storedId) return null;
    return {
      employeeId: storedId,
      employeeCode: getSessionItem('userCode') || '',
      fullName: storedName || '',
      companyEmail: storedEmail || '',
      phoneNumber: storedPhone || '',
      dateOfBirth: '',
      gender: '',
      hireDate: '',
      status: 'ĐANG_LÀM_VIỆC',
      managerId: '',
      managerName: '',
      department: {
        departmentId: storedDept || '',
        departmentName: storedDeptName || '',
      },
      position: {
        positionName: storedPosition || '',
      },
      role: storedRole?.toUpperCase?.() || 'EMPLOYEE',
      baseLeaveDays: 12,
    };
  });

  // --- Các thuộc tính tiện dụng ---
  const employeeId   = computed(() => {
    const fromProfile = toOptionalNumber(fullProfile.value?.employeeId);
    if (fromProfile !== null) return fromProfile;
    return toOptionalNumber(storedId);
  });
  const employeeCode = computed(() => fullProfile.value?.employeeCode ?? '');
  const fullName     = computed(() => fullProfile.value?.fullName ?? storedName ?? '');
  const email        = computed(() => fullProfile.value?.companyEmail ?? storedEmail ?? '');
  const phone        = computed(() => fullProfile.value?.phoneNumber ?? '');
  const gender       = computed(() => fullProfile.value?.gender ?? '');
  const dateOfBirth  = computed(() => fullProfile.value?.dateOfBirth ?? '');
  const hireDate     = computed(() => fullProfile.value?.hireDate ?? '');
  const status       = computed(() => fullProfile.value?.status ?? 'ĐANG_LÀM_VIỆC');
  const role         = computed(() => storedRole ?? 'employee');
  const deptId       = computed(() => {
    const fromProfile = toOptionalNumber(fullProfile.value?.department?.departmentId);
    if (fromProfile !== null) return fromProfile;
    return toOptionalNumber(storedDept);
  });
  const deptName     = computed(() => fullProfile.value?.department?.departmentName ?? '');
  const positionName = computed(() => fullProfile.value?.position?.positionName ?? '');
  const baseLeaveDays = computed(() => fullProfile.value?.baseLeaveDays ?? 12);

  const managerId = computed(() => fullProfile.value?.managerId);
  const managerName = computed(() => fullProfile.value?.managerName || 'Chưa xác định');
  const managerAvatar = computed(() => {
    const mail = fullProfile.value?.managerEmail || '';
    return mail ? `https://i.pravatar.cc/150?u=${encodeURIComponent(mail)}` : 'https://i.pravatar.cc/150?u=none';
  });

  // Avatar: dùng pravatar dựa trên email để tự tạo ảnh khác nhau cho từng user
  const avatar = computed(() => {
    const mail = email.value;
    return `https://i.pravatar.cc/150?u=${encodeURIComponent(mail)}`;
  });

  // Ngày gia nhập dạng hiển thị dd/MM/yyyy
  const hireDateFormatted = computed(() => {
    if (!hireDate.value) return '';
    const d = new Date(hireDate.value);
    if (isNaN(d.getTime())) return hireDate.value;
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
  });

  // Ngày sinh dạng dd/MM/yyyy
  const dobFormatted = computed(() => {
    if (!dateOfBirth.value) return '';
    const d = new Date(dateOfBirth.value);
    if (isNaN(d.getTime())) return dateOfBirth.value;
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
  });

  // Thời gian gắn bó
  const tenure = computed(() => {
    if (!hireDate.value) return '';
    const start = new Date(hireDate.value);
    const now   = new Date();
    const months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());
    const years  = Math.floor(months / 12);
    const rem    = months % 12;
    if (years === 0) return `${rem} tháng`;
    if (rem === 0)   return `${years} năm`;
    return `${years} năm ${rem} tháng`;
  });

  return {
    fullProfile,
    employeeId,
    employeeCode,
    fullName,
    email,
    phone,
    gender,
    dateOfBirth,
    dobFormatted,
    hireDate,
    hireDateFormatted,
    status,
    role,
    deptId,
    deptName,
    positionName,
    baseLeaveDays,
    avatar,
    tenure,
    managerName,
    managerAvatar,
  };
}
