import { BE_API_BASE, LEGACY_JSON_SERVER_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { getSessionItem, handleUnauthorized } from '@/services/session.js';

const LEGACY_HOSTS = new Set(['localhost:3000', '127.0.0.1:3000']);

const jsonResponse = (data, status = 200) =>
  new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json; charset=utf-8' },
  });

const toInt = (value, fallback = null) => {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const normalizeKey = (value) =>
  String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .replace(/\s+/g, '_')
    .toUpperCase();

const isPrivilegedRole = () => {
  const roleRaw = String(getSessionItem('userRole') || '').trim().toLowerCase();
  return roleRaw === 'admin' || roleRaw === 'hr';
};

const normalizeLeaveStatusToBe = (value) => {
  const key = normalizeKey(value);
  if (key === 'PENDING') return 'CHỜ_DUYỆT';
  if (key === 'APPROVED') return 'ĐÃ_DUYỆT';
  if (key === 'REJECTED') return 'TỪ_CHỐI';
  if (key === 'IN_PROGRESS') return 'ĐANG_XỬ_LÝ';
  if (key === 'WAIT_HR_CONFIRM' || key === 'CHO_XAC_NHAN_HR') return 'CHỜ_XÁC_NHẬN_HR';
  if (key === 'WAIT_DIRECTOR' || key === 'CHO_GIAM_DOC_DUYET') return 'CHỜ_GIÁM_ĐỐC_DUYỆT';
  return value ?? 'CHỜ_DUYỆT';
};

const normalizeAttendanceStatusToBe = (value) => {
  const key = normalizeKey(value);
  // DB enum: CHỜ_DUYỆT, ĐÃ_DUYỆT, TỪ_CHỐI, NHẬP_THỦ_CÔNG
  if (key === 'CHO_DUYET' || key === 'PENDING') return 'CHỜ_DUYỆT';
  if (key === 'DA_DUYET' || key === 'APPROVED') return 'ĐÃ_DUYỆT';
  if (key === 'TU_CHOI' || key === 'REJECTED') return 'TỪ_CHỐI';
  if (key === 'NHAP_THU_CONG' || key === 'MANUAL') return 'NHẬP_THỦ_CÔNG';

  // Legacy UI statuses
  if (key === 'ONTIME' || key === 'ON_TIME' || key === 'PRESENT') return 'CHỜ_DUYỆT';
  if (key === 'LATE') return 'CHỜ_DUYỆT';
  if (key === 'ABSENT') return 'TỪ_CHỐI';

  return 'CHỜ_DUYỆT';
};

const normalizeAttendanceStatusToFe = (value) => {
  const key = normalizeKey(value);
  if (['CHO_DUYET', 'DA_DUYET', 'NHAP_THU_CONG'].includes(key)) return 'ontime';
  if (['TU_CHOI'].includes(key)) return 'absent';
  if (['PRESENT', 'ON_TIME', 'ONTIME', 'DUNG_GIO'].includes(key)) return 'ontime';
  if (['LATE', 'MUON'].includes(key)) return 'late';
  if (['ABSENT', 'VANG'].includes(key)) return 'absent';
  return String(value ?? 'ontime').toLowerCase();
};

const normalizeContractStatusToFe = (value) => {
  const key = normalizeKey(value);
  if (['DANG_HIEU_LUC', 'CO_HIEU_LUC', 'HIEU_LUC'].includes(key)) return 'HIỆU_LỰC';
  if (['SAP_HET_HAN', 'HET_HAN', 'EXPIRED'].includes(key)) return 'SẮP_HẾT_HẠN';
  if (['DA_THANH_LY', 'DA_CHAM_DUT', 'TERMINATED'].includes(key)) return 'ĐÃ_CHẤM_DỨT';
  if (['CHO_HIEU_LUC', 'PENDING'].includes(key)) return 'CHỜ_HIỆU_LỰC';
  return 'HIỆU_LỰC';
};

const normalizeContractStatusToBe = (value) => {
  const key = normalizeKey(value);
  if (['DA_THANH_LY', 'DA_CHAM_DUT', 'TERMINATED'].includes(key)) return 'ĐÃ_CHẤM_DỨT';
  if (['CHO_HIEU_LUC', 'PENDING'].includes(key)) return 'CHỜ_HIỆU_LỰC';
  if (['SAP_HET_HAN', 'HET_HAN', 'EXPIRED'].includes(key)) return 'HẾT_HẠN';
  return 'CÓ_HIỆU_LỰC';
};

const normalizeAssetStatusToFe = (value) => {
  const key = normalizeKey(value);
  if (['CO_SAN', 'AVAILABLE', 'TRONG_KHO'].includes(key)) return 'TRONG_KHO';
  if (['DA_CAP_PHAT', 'ASSIGNED', 'DANG_SU_DUNG'].includes(key)) return 'ĐANG_SỬ_DỤNG';
  if (['DANG_BAO_TRI', 'MAINTENANCE', 'HONG', 'BROKEN'].includes(key)) return 'HỎNG';
  if (['THANH_LY', 'LIQUIDATED'].includes(key)) return 'ĐÃ_THANH_LÝ';
  return 'TRONG_KHO';
};

const normalizeAssetStatusToBe = (value) => {
  const key = normalizeKey(value);
  if (['TRONG_KHO', 'AVAILABLE'].includes(key)) return 'CÓ_SẴN';
  if (['DANG_SU_DUNG', 'ASSIGNED'].includes(key)) return 'ĐÃ_CẤP_PHÁT';
  if (['HONG', 'MAINTENANCE'].includes(key)) return 'HỎNG';
  if (['DA_THANH_LY', 'LIQUIDATED'].includes(key)) return 'THANH_LÝ';
  return 'CÓ_SẴN';
};

const normalizeTransferStatusToFe = (value) => {
  const key = normalizeKey(value);
  if (['TRANSFERRED', 'PAID', 'DA_THANH_TOAN'].includes(key)) return 'Đã thanh toán';
  if (['DELETED', 'CANCELLED', 'DA_XOA'].includes(key)) return 'Đã xóa';
  return 'Chờ thanh toán';
};

const normalizeTransferStatusToBe = (value) => {
  const key = normalizeKey(value);
  if (['DA_THANH_TOAN', 'TRANSFERRED', 'PAID'].includes(key)) return 'TRANSFERRED';
  if (['DA_XOA', 'DELETED', 'CANCELLED'].includes(key)) return 'DELETED';
  return 'PENDING';
};

const mapEmployee = (item = {}) => {
  const employeeId = item.employee_id ?? item.employeeId ?? null;
  const departmentId = item.department_id ?? item.departmentId ?? null;
  const departmentName = item.department_name ?? item.departmentName ?? '';
  const positionId = item.position_id ?? item.positionId ?? null;
  const positionName = item.position_name ?? item.positionName ?? '';
  return {
    ...item,
    id: employeeId,
    employeeId,
    employee_id: employeeId,
    employeeCode: item.employee_code,
    employee_code: item.employee_code,
    fullName: item.full_name,
    full_name: item.full_name,
    name: item.full_name,
    companyEmail: item.company_email,
    company_email: item.company_email,
    phoneNumber: item.phone_number,
    phone_number: item.phone_number,
    hireDate: item.hire_date ?? null,
    hire_date: item.hire_date ?? null,
    dateOfBirth: item.date_of_birth ?? null,
    date_of_birth: item.date_of_birth ?? null,
    gender: item.gender ?? null,
    status: item.status ?? null,
    departmentId,
    department_id: departmentId,
    deptId: departmentId,
    department: {
      departmentId,
      department_id: departmentId,
      departmentName,
      department_name: departmentName,
    },
    position: {
      positionId,
      position_id: positionId,
      positionName,
      position_name: positionName,
    },
  };
};

const mapEmployeePayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  const employeeCode = payload.employee_code ?? payload.employeeCode;
  const fullName = payload.full_name ?? payload.fullName ?? payload.name;
  const companyEmail = payload.company_email ?? payload.companyEmail;
  const hireDate = payload.hire_date ?? payload.hireDate;
  const phoneNumber = payload.phone_number ?? payload.phoneNumber;
  const dateOfBirth = payload.date_of_birth ?? payload.dateOfBirth;
  const gender = payload.gender;
  const status = payload.status;
  const nationalityId = payload.nationality_id ?? payload.nationalityId;
  const departmentId = payload.department_id ?? payload.departmentId ?? payload.deptId;
  const positionId = payload.position_id ?? payload.positionId;
  const password = payload.password;

  if (employeeCode !== undefined) mapped.employee_code = String(employeeCode).trim();
  if (fullName !== undefined) mapped.full_name = String(fullName).trim();
  if (companyEmail !== undefined) mapped.company_email = String(companyEmail).trim();
  if (hireDate !== undefined) mapped.hire_date = String(hireDate).slice(0, 10);
  if (phoneNumber !== undefined) mapped.phone_number = String(phoneNumber).trim();
  if (dateOfBirth !== undefined && String(dateOfBirth).trim() !== '') {
    mapped.date_of_birth = String(dateOfBirth).slice(0, 10);
  }
  if (gender !== undefined) mapped.gender = String(gender).trim();
  if (status !== undefined) mapped.status = String(status).trim();

  const nationalityInt = toInt(nationalityId);
  if (nationalityInt !== null) mapped.nationality_id = nationalityInt;
  const departmentInt = toInt(departmentId);
  if (departmentInt !== null) mapped.department_id = departmentInt;
  const positionInt = toInt(positionId);
  if (positionInt !== null) mapped.position_id = positionInt;

  if (password !== undefined && String(password).trim() !== '') {
    mapped.password = String(password);
  } else if (isCreate && mapped.employee_code) {
    mapped.password = mapped.employee_code;
  }

  return mapped;
};

const mapDepartment = (item = {}) => ({
  ...item,
  id: item.department_id,
  departmentId: item.department_id,
  department_id: item.department_id,
  name: item.department_name,
  departmentName: item.department_name,
  department_name: item.department_name,
  manager: item.manager_name ?? null,
});

const mapPosition = (item = {}) => ({
  ...item,
  id: item.position_id,
  positionId: item.position_id,
  position_id: item.position_id,
  positionName: item.position_name,
  position_name: item.position_name,
  name: item.position_name,
});

const mapContract = (item = {}) => ({
  ...item,
  id: item.contract_id,
  contractId: item.contract_id,
  contract_id: item.contract_id,
  contract_no: item.contract_code ?? item.contract_number ?? '',
  contract_code: item.contract_code ?? '',
  contract_number: item.contract_number ?? '',
  employee_id: item.employee_id,
  employeeId: item.employee_id,
  employee_name: item.employee_name ?? '',
  contract_type_id: item.contract_type_id,
  contractTypeId: item.contract_type_id,
  contract_type: item.contract_type_code ?? item.contract_type_name ?? '',
  contractType: item.contract_type_code ?? item.contract_type_name ?? '',
  sign_date: stripDate(item.sign_date),
  start_date: stripDate(item.effective_date),
  end_date: stripDate(item.expiry_date),
  salary: Number(item.basic_salary ?? 0),
  basic_salary: Number(item.basic_salary ?? 0),
  status: normalizeContractStatusToFe(item.ui_status ?? item.status),
});

const mapAsset = (item = {}) => ({
  ...item,
  id: item.asset_id,
  assetId: item.asset_id,
  asset_id: item.asset_id,
  code: item.asset_code ?? '',
  assetCode: item.asset_code ?? '',
  name: item.asset_name ?? '',
  assetName: item.asset_name ?? '',
  category: item.category_name ?? item.category ?? 'Khác',
  category_name: item.category_name ?? item.category ?? 'Khác',
  status: normalizeAssetStatusToFe(item.status),
});

const mapAssetAssignment = (item = {}) => ({
  ...item,
  id: item.assignment_id,
  assignmentId: item.assignment_id,
  assignment_id: item.assignment_id,
  assetId: item.asset_id,
  asset_id: item.asset_id,
  employeeId: item.employee_id,
  employee_id: item.employee_id,
  status: item.status ?? '',
  assigned_date: stripDate(item.assigned_date ?? item.created_at),
});

const mapRequestType = (item = {}) => ({
  ...item,
  id: item.request_type_id,
  requestTypeId: item.request_type_id,
  request_type_id: item.request_type_id,
  requestTypeName: item.request_type_name,
  request_type_name: item.request_type_name,
  category: item.category ?? item.module ?? 'NGHỈ_PHÉP',
});

const mapContractType = (item = {}) => ({
  ...item,
  id: item.contract_type_id,
  contractTypeId: item.contract_type_id,
  contract_type_id: item.contract_type_id,
  contractTypeCode: item.contract_type_code ?? '',
  contract_type_code: item.contract_type_code ?? '',
  contractTypeName: item.contract_type_name ?? '',
  contract_type_name: item.contract_type_name ?? '',
});

const mapSalaryDetail = (item = {}) => ({
  ...item,
  id: item.salary_detail_id,
  salaryId: item.salary_detail_id,
  salary_id: item.salary_detail_id,
  periodId: item.period_id,
  period_id: item.period_id,
  periodCode: item.period_code ?? '',
  period_code: item.period_code ?? '',
  periodName: item.period_name ?? '',
  period_name: item.period_name ?? '',
  periodStatus: item.period_status ?? '',
  period_status: item.period_status ?? '',
  periodMonthKey: item.period_month_key ?? '',
  period_month_key: item.period_month_key ?? '',
  year: toInt(String(item.period_month_key ?? '').slice(0, 4), null),
  month: toInt(String(item.period_month_key ?? '').slice(5, 7), null),
  employeeId: item.employee_id,
  employee_id: item.employee_id,
  employeeCode: item.employee_code ?? '',
  employee_code: item.employee_code ?? '',
  employeeName: item.full_name ?? item.employee_name ?? '',
  employee_name: item.full_name ?? item.employee_name ?? '',
  fullName: item.full_name ?? item.employee_name ?? '',
  full_name: item.full_name ?? item.employee_name ?? '',
  contractId: item.contract_id ?? null,
  contract_id: item.contract_id ?? null,
  basicSalary: Number(item.basic_salary ?? 0),
  basic_salary: Number(item.basic_salary ?? 0),
  grossSalary: Number(item.gross_salary ?? 0),
  gross_salary: Number(item.gross_salary ?? 0),
  netSalary: Number(item.net_salary ?? 0),
  net_salary: Number(item.net_salary ?? 0),
  totalAllowances: Number(item.total_allowances ?? 0),
  total_allowances: Number(item.total_allowances ?? 0),
  totalDeductions: Number(item.total_deductions ?? 0),
  total_deductions: Number(item.total_deductions ?? 0),
  overtimePay: Number(item.overtime_pay ?? 0),
  overtime_pay: Number(item.overtime_pay ?? 0),
  bonus: Number(item.bonus ?? 0),
  penalty: Number(item.penalty ?? 0),
  personalIncomeTax: Number(item.personal_income_tax ?? 0),
  personal_income_tax: Number(item.personal_income_tax ?? 0),
  transferStatus: item.transfer_status ?? 'PENDING',
  transfer_status: item.transfer_status ?? 'PENDING',
  status: normalizeTransferStatusToFe(item.transfer_status),
});

const mapSalaryPeriod = (item = {}) => ({
  ...item,
  id: item.period_id,
  periodId: item.period_id,
  period_id: item.period_id,
  periodCode: item.period_code ?? '',
  period_code: item.period_code ?? '',
  periodName: item.period_name ?? '',
  period_name: item.period_name ?? '',
  periodType: item.period_type ?? 'MONTHLY',
  period_type: item.period_type ?? 'MONTHLY',
  year: toInt(item.year, null),
  month: toInt(item.month, null),
  startDate: stripDate(item.start_date),
  start_date: stripDate(item.start_date),
  endDate: stripDate(item.end_date),
  end_date: stripDate(item.end_date),
  paymentDate: stripDate(item.payment_date),
  payment_date: stripDate(item.payment_date),
  standardWorkingDays: toInt(item.standard_working_days, 0) || 0,
  standard_working_days: toInt(item.standard_working_days, 0) || 0,
  status: String(item.status ?? 'OPEN').toUpperCase(),
});

const stripDate = (value) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  return text.includes('T') ? text.slice(0, 10) : text.slice(0, 10);
};

const stripTime = (value) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  if (text.includes('T')) {
    const tail = text.split('T')[1] || '';
    const hhmmss = tail.slice(0, 8);
    return hhmmss.length === 5 ? `${hhmmss}:00` : hhmmss;
  }
  if (text.includes(' ')) {
    const tail = text.split(' ').pop() || '';
    const hhmmss = tail.slice(0, 8);
    return hhmmss.length === 5 ? `${hhmmss}:00` : hhmmss;
  }
  if (/^\d{2}:\d{2}$/.test(text)) return `${text}:00`;
  if (/^\d{2}:\d{2}:\d{2}/.test(text)) return text.slice(0, 8);
  return '';
};

const parseAttendanceMeta = (notesValue) => {
  const raw = String(notesValue ?? '').trim();
  if (!raw) return {};
  try {
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === 'object') return parsed;
  } catch {
    return {};
  }
  return {};
};

const toAttendanceDateTime = (value, attendanceDate) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test(text)) {
    return text.replace('T', ' ').slice(0, 19);
  }
  if (/^\d{2}:\d{2}(:\d{2})?$/.test(text)) {
    const normalizedTime = text.length === 5 ? `${text}:00` : text;
    return `${attendanceDate} ${normalizedTime}`;
  }
  return text;
};

const mapAttendance = (item = {}) => {
  const attendanceDate = stripDate(item.attendance_date);
  const meta = parseAttendanceMeta(item.notes);
  const checkIn2Raw = item.check_in_time_2 ?? meta.checkIn2 ?? meta.check_in_time_2 ?? '';
  const checkOut2Raw = item.check_out_time_2 ?? meta.checkOut2 ?? meta.check_out_time_2 ?? '';

  return {
    ...item,
    id: item.attendance_id,
    attendanceId: item.attendance_id,
    employeeId: item.employee_id,
    attendanceDate,
    attendance_date: attendanceDate,
    date: attendanceDate,
    checkIn1: stripTime(item.check_in_time),
    checkOut1: stripTime(item.check_out_time),
    checkIn2: stripTime(checkIn2Raw),
    checkOut2: stripTime(checkOut2Raw),
    status: normalizeAttendanceStatusToFe(item.status),
  };
};

const mapLeaveRequest = (item = {}) => {
  const status = item.request_status ?? item.status ?? 'CHỜ_DUYỆT';
  return {
    ...item,
    id: item.leave_request_id,
    leaveRequestId: item.leave_request_id,
    leave_request_id: item.leave_request_id,
    requestId: item.request_id,
    request_id: item.request_id,
    requesterId: item.employee_id,
    requester_id: item.employee_id,
    employeeId: item.employee_id,
    employee_id: item.employee_id,
    requestTypeId: item.request_type_id ?? item.leave_type_id ?? 1,
    request_type_id: item.request_type_id ?? item.leave_type_id ?? 1,
    leaveTypeId: item.leave_type_id,
    leave_type_id: item.leave_type_id,
    startDate: stripDate(item.from_date),
    endDate: stripDate(item.to_date),
    from_date: stripDate(item.from_date),
    to_date: stripDate(item.to_date),
    days: Number(item.number_of_days ?? 0),
    number_of_days: Number(item.number_of_days ?? 0),
    status,
    requestStatus: status,
    request_status: status,
    reason: item.request_reason ?? item.reason ?? item.request_notes ?? item.notes ?? '',
    notes: item.request_notes ?? item.notes ?? item.request_reason ?? item.reason ?? '',
    requesterName: item.employee_name ?? '',
    employeeName: item.employee_name ?? '',
    requestDate: stripDate(item.created_at ?? item.from_date),
    request_date: stripDate(item.created_at ?? item.from_date),
  };
};

const mapNotification = (item = {}) => ({
  ...item,
  id: item.notification_id,
  notificationId: item.notification_id,
  userId: item.receiver_id,
  receiverId: item.receiver_id,
  senderId: item.sender_id,
  type: String(item.notification_type ?? '').toLowerCase() || 'info',
  title: item.title,
  desc: item.content,
  content: item.content,
  isRead: Boolean(item.is_read),
  time: item.created_at ?? item.read_date ?? '',
});

const mapServiceCategory = (item = {}) => ({
  ...item,
  id: item.category_id,
  categoryId: item.category_id,
  code: item.category_code,
  name: item.category_name,
  categoryName: item.category_name,
});

const mapServiceTicket = (item = {}) => ({
  ...item,
  id: item.ticket_id,
  ticketId: item.ticket_id,
  employeeId: item.requester_id,
  requesterId: item.requester_id,
  categoryId: item.category_id,
  category: item.category_name ?? '',
  type: item.category_name ?? '',
  desc: item.description ?? '',
  date: stripDate(item.created_at),
  note: '',
});

const buildUrlWithQuery = (basePath, query = {}) => {
  const params = new URLSearchParams();
  Object.entries(query).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    params.set(key, String(value));
  });
  const qs = params.toString();
  return qs ? `${basePath}?${qs}` : basePath;
};

const applyJsonServerSorting = (items, searchParams) => {
  if (!Array.isArray(items)) return items;
  const sortKey = searchParams.get('_sort');
  if (sortKey) {
    const order = String(searchParams.get('_order') || 'asc').toLowerCase();
    items.sort((a, b) => {
      const av = a?.[sortKey];
      const bv = b?.[sortKey];
      if (av === bv) return 0;
      if (av === undefined || av === null) return 1;
      if (bv === undefined || bv === null) return -1;
      if (av > bv) return order === 'desc' ? -1 : 1;
      return order === 'desc' ? 1 : -1;
    });
  }

  const limit = toInt(searchParams.get('_limit'));
  if (limit !== null && limit > 0) {
    return items.slice(0, limit);
  }
  return items;
};

const extractPayloadData = (payload) => (payload && typeof payload === 'object' ? payload.data : null);

const beRequest = async (nativeFetch, path, { method = 'GET', query = {}, body } = {}) => {
  const headers = {};
  const token = getAccessToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  let response;
  try {
    response = await nativeFetch(
      `${BE_API_BASE}${buildUrlWithQuery(path, query)}`,
      {
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
      }
    );
  } catch (error) {
    const networkError = new Error(`Cannot connect BE API (${BE_API_BASE})`);
    networkError.status = 503;
    networkError.code = 'be_unreachable';
    networkError.cause = error;
    throw networkError;
  }

  const text = await response.text();
  let payload = {};
  try {
    payload = text ? JSON.parse(text) : {};
  } catch {
    payload = { message: text };
  }

  if (!response.ok) {
    const message = payload?.message || `Backend error ${response.status}`;
    const requestError = new Error(message);
    requestError.status = response.status;
    requestError.code = payload?.error || 'backend_error';
    requestError.payload = payload;
    if (response.status === 401) {
      handleUnauthorized();
    }
    throw requestError;
  }

  return { status: response.status, payload };
};

const resolveLegacyPath = (url) => {
  const chunks = url.pathname.split('/').filter(Boolean);
  return {
    resource: chunks[0] || '',
    id: chunks[1] || null,
    action: chunks[2] || null,
  };
};

const mapAttendancePayloadToBe = (payload = {}) => {
  const attendanceDate = stripDate(payload.attendanceDate || payload.attendance_date || payload.date || new Date().toISOString().slice(0, 10));
  const mapped = {};
  if (payload.employeeId !== undefined || payload.employee_id !== undefined) {
    mapped.employee_id = toInt(payload.employeeId ?? payload.employee_id);
  }
  if (attendanceDate) {
    mapped.attendance_date = attendanceDate;
  }
  if (payload.checkIn1 || payload.check_in_time) {
    mapped.check_in_time = toAttendanceDateTime(payload.checkIn1 ?? payload.check_in_time, attendanceDate);
  }
  if (payload.checkOut1 || payload.check_out_time) {
    mapped.check_out_time = toAttendanceDateTime(payload.checkOut1 ?? payload.check_out_time, attendanceDate);
  }

  const checkIn2 = payload.checkIn2 ?? payload.check_in_time_2;
  const checkOut2 = payload.checkOut2 ?? payload.check_out_time_2;
  if (checkIn2) {
    mapped.check_in_time_2 = toAttendanceDateTime(checkIn2, attendanceDate);
  }
  if (checkOut2) {
    mapped.check_out_time_2 = toAttendanceDateTime(checkOut2, attendanceDate);
  }
  if (payload.notes !== undefined) {
    mapped.notes = payload.notes;
  }
  if (payload.status !== undefined) {
    mapped.status = normalizeAttendanceStatusToBe(payload.status);
  }
  return mapped;
};

const mapLeavePayloadToBe = (payload = {}) => {
  const requesterId = toInt(payload.requesterId ?? payload.requester_id ?? payload.employeeId ?? payload.employee_id);
  const requestTypeId = toInt(payload.requestTypeId ?? payload.request_type_id ?? 1, 1);
  const leaveTypeRaw = toInt(payload.leaveTypeId ?? payload.leave_type_id ?? requestTypeId ?? 1, 1);
  const leaveTypeId = leaveTypeRaw && leaveTypeRaw > 0 && leaveTypeRaw < 99 ? leaveTypeRaw : 1;
  const fromDate = payload.startDate || payload.from_date || payload.request_date || new Date().toISOString().slice(0, 10);
  const toDate = payload.endDate || payload.to_date || fromDate;
  const days = Number(payload.days ?? payload.number_of_days ?? 1);

  return {
    requester_id: requesterId,
    employee_id: requesterId,
    request_type_id: requestTypeId,
    request_date: payload.request_date || new Date().toISOString().slice(0, 10),
    leave_type_id: leaveTypeId,
    from_date: fromDate,
    to_date: toDate,
    number_of_days: Number.isFinite(days) && days > 0 ? days : 1,
    reason: payload.reason ?? payload.notes ?? '',
    status: normalizeLeaveStatusToBe(payload.status ?? 'CHỜ_DUYỆT'),
    is_urgent: Boolean(payload.is_urgent ?? false),
  };
};

const mapNotificationPayloadToBe = (payload = {}) => ({
  notification_type: payload.notification_type || payload.type || 'SYSTEM',
  title: payload.title || 'Thông báo hệ thống',
  content: payload.content || payload.desc || '',
  receiver_id: toInt(payload.receiverId ?? payload.receiver_id ?? payload.userId),
  sender_id: toInt(payload.senderId ?? payload.sender_id ?? getSessionItem('userId')),
  is_read: Boolean(payload.is_read ?? payload.isRead ?? false),
  priority: payload.priority || payload.type || 'TRUNG_BÌNH',
  action_url: payload.action_url || null,
});

const mapServiceTicketPayloadToBe = (payload = {}) => ({
  requester_id: toInt(payload.requesterId ?? payload.employeeId ?? getSessionItem('userId')),
  category_id: toInt(payload.categoryId ?? payload.category_id ?? 1, 1),
  title: payload.title || 'Yêu cầu hỗ trợ',
  description: payload.description || payload.desc || '',
  priority: payload.priority || 'MEDIUM',
  status: payload.status || undefined,
});

const mapDepartmentPayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  if (isCreate) {
    const code = payload.department_code ?? payload.departmentCode ?? payload.code;
    if (code !== undefined) mapped.department_code = String(code).trim();
  }
  const name = payload.department_name ?? payload.departmentName ?? payload.name;
  if (name !== undefined) mapped.department_name = String(name).trim();
  const parentId = toInt(payload.parent_department_id ?? payload.parent_id ?? payload.parentId);
  if (parentId !== null) mapped.parent_department_id = parentId;
  const managerId = toInt(payload.manager_id ?? payload.managerId);
  if (managerId !== null) mapped.manager_id = managerId;
  if (payload.description !== undefined) mapped.description = String(payload.description ?? '');
  if (payload.status !== undefined) mapped.status = Boolean(payload.status);
  return mapped;
};

const mapPositionPayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  if (isCreate) {
    const code = payload.position_code ?? payload.positionCode ?? payload.code;
    if (code !== undefined) mapped.position_code = String(code).trim();
  }
  const name = payload.position_name ?? payload.positionName ?? payload.name ?? payload.title;
  if (name !== undefined) mapped.position_name = String(name).trim();
  const group = payload.position_group ?? payload.group;
  if (group !== undefined) mapped.position_group = String(group).trim();
  const level = payload.position_level ?? payload.level;
  if (level !== undefined) mapped.position_level = String(level).trim();
  if (payload.job_description !== undefined) mapped.job_description = String(payload.job_description ?? '');
  if (payload.requirements !== undefined) mapped.requirements = String(payload.requirements ?? '');
  if (payload.status !== undefined) mapped.status = Boolean(payload.status);
  return mapped;
};

const mapContractPayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  const employeeId = toInt(payload.employee_id ?? payload.employeeId);
  const contractTypeId = toInt(payload.contract_type_id ?? payload.contractTypeId);
  const contractCode = payload.contract_code ?? payload.contractCode ?? payload.contract_no ?? payload.contract_number;
  const contractNumber = payload.contract_number ?? payload.contract_no ?? payload.contractCode;
  const signDate = payload.sign_date ?? payload.signDate;
  const startDate = payload.effective_date ?? payload.start_date ?? payload.startDate;
  const endDate = payload.expiry_date ?? payload.end_date ?? payload.endDate;
  const salary = Number(payload.basic_salary ?? payload.salary ?? 0);

  if (isCreate) {
    if (employeeId !== null) mapped.employee_id = employeeId;
    if (contractTypeId !== null) mapped.contract_type_id = contractTypeId;
  } else {
    if (employeeId !== null) mapped.employee_id = employeeId;
    if (contractTypeId !== null) mapped.contract_type_id = contractTypeId;
  }
  if (contractCode !== undefined) mapped.contract_code = String(contractCode).trim();
  if (contractNumber !== undefined) mapped.contract_number = String(contractNumber).trim();
  if (signDate !== undefined) mapped.sign_date = stripDate(signDate);
  if (startDate !== undefined) mapped.effective_date = stripDate(startDate);
  if (endDate !== undefined && String(endDate).trim() !== '') mapped.expiry_date = stripDate(endDate);
  if (Number.isFinite(salary) && salary > 0) {
    mapped.basic_salary = salary;
    mapped.gross_salary = salary;
  }
  if (payload.status !== undefined) mapped.status = normalizeContractStatusToBe(payload.status);
  if (payload.position_id !== undefined || payload.positionId !== undefined) {
    const positionId = toInt(payload.position_id ?? payload.positionId);
    if (positionId !== null) mapped.position_id = positionId;
  }
  if (payload.department_id !== undefined || payload.departmentId !== undefined) {
    const departmentId = toInt(payload.department_id ?? payload.departmentId);
    if (departmentId !== null) mapped.department_id = departmentId;
  }
  return mapped;
};

const mapAssetPayloadToBe = (payload = {}) => {
  const mapped = {};
  const code = payload.asset_code ?? payload.assetCode ?? payload.code;
  const name = payload.asset_name ?? payload.assetName ?? payload.name;
  if (code !== undefined) mapped.asset_code = String(code).trim();
  if (name !== undefined) mapped.asset_name = String(name).trim();
  const categoryId = toInt(payload.category_id ?? payload.categoryId);
  if (categoryId !== null) mapped.category_id = categoryId;
  if (payload.status !== undefined) mapped.status = normalizeAssetStatusToBe(payload.status);
  if (payload.serial_number !== undefined) mapped.serial_number = String(payload.serial_number).trim();
  if (payload.inventory_number !== undefined) mapped.inventory_number = String(payload.inventory_number).trim();
  if (payload.purchase_date !== undefined) mapped.purchase_date = stripDate(payload.purchase_date);
  if (payload.purchase_price !== undefined) mapped.purchase_price = Number(payload.purchase_price) || 0;
  return mapped;
};

const mapAssetAssignmentPayloadToBe = (payload = {}) => {
  const assetId = toInt(payload.asset_id ?? payload.assetId);
  const employeeId = toInt(payload.employee_id ?? payload.employeeId);
  return {
    asset_id: assetId,
    employee_id: employeeId,
    assigned_date: payload.assigned_date ?? new Date().toISOString().slice(0, 10),
    expected_return_date: payload.expected_return_date ?? null,
    status: payload.status ?? 'ĐANG_SỬ_DỤNG',
    assignment_notes: payload.assignment_notes ?? '',
  };
};

const mapSalaryDetailPayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  const periodId = toInt(payload.period_id ?? payload.periodId);
  const employeeId = toInt(payload.employee_id ?? payload.employeeId);
  const contractId = toInt(payload.contract_id ?? payload.contractId);
  const basicSalary = Number(payload.basic_salary ?? payload.basicSalary ?? payload.baseSalary);
  const grossSalary = Number(payload.gross_salary ?? payload.grossSalary ?? payload.totalIncome);
  const totalAllowances = Number(payload.total_allowances ?? payload.totalAllowances);
  const totalDeductions = Number(payload.total_deductions ?? payload.totalDeductions ?? payload.deduction);
  const netSalaryRaw = payload.net_salary ?? payload.netSalary;

  if (isCreate && periodId !== null) mapped.period_id = periodId;
  if (isCreate && employeeId !== null) mapped.employee_id = employeeId;
  if (contractId !== null) mapped.contract_id = contractId;

  if (Number.isFinite(basicSalary)) mapped.basic_salary = basicSalary;
  if (Number.isFinite(grossSalary)) {
    mapped.gross_salary = grossSalary;
  } else if (Number.isFinite(basicSalary) && Number.isFinite(totalAllowances)) {
    mapped.gross_salary = basicSalary + totalAllowances;
  }

  if (Number.isFinite(totalAllowances)) mapped.total_allowances = totalAllowances;
  if (Number.isFinite(totalDeductions)) mapped.total_deductions = totalDeductions;

  if (payload.overtime_pay !== undefined || payload.overtimePay !== undefined) {
    const overtimePay = Number(payload.overtime_pay ?? payload.overtimePay);
    if (Number.isFinite(overtimePay)) mapped.overtime_pay = overtimePay;
  }
  if (payload.bonus !== undefined) {
    const bonus = Number(payload.bonus);
    if (Number.isFinite(bonus)) mapped.bonus = bonus;
  }
  if (payload.penalty !== undefined) {
    const penalty = Number(payload.penalty);
    if (Number.isFinite(penalty)) mapped.penalty = penalty;
  }
  if (payload.personal_income_tax !== undefined || payload.personalIncomeTax !== undefined) {
    const tax = Number(payload.personal_income_tax ?? payload.personalIncomeTax);
    if (Number.isFinite(tax)) mapped.personal_income_tax = tax;
  }

  const netSalary = Number(netSalaryRaw);
  if (Number.isFinite(netSalary)) {
    mapped.net_salary = netSalary;
  } else if (Number.isFinite(mapped.gross_salary) && Number.isFinite(totalDeductions)) {
    mapped.net_salary = mapped.gross_salary - totalDeductions;
  }

  const transferStatusRaw = payload.transfer_status ?? payload.transferStatus ?? payload.status;
  if (transferStatusRaw !== undefined) mapped.transfer_status = normalizeTransferStatusToBe(transferStatusRaw);
  if (payload.notes !== undefined) mapped.notes = String(payload.notes ?? '');
  if (payload.bank_account !== undefined || payload.bankAccount !== undefined) {
    mapped.bank_account = String(payload.bank_account ?? payload.bankAccount ?? '');
  }
  if (payload.bank_name !== undefined || payload.bankName !== undefined) {
    mapped.bank_name = String(payload.bank_name ?? payload.bankName ?? '');
  }
  return mapped;
};

const mapSalaryPeriodPayloadToBe = (payload = {}, { isCreate = false } = {}) => {
  const mapped = {};
  const year = toInt(payload.year);
  const month = toInt(payload.month);
  const monthPadded = month !== null ? String(month).padStart(2, '0') : '';

  if (isCreate) {
    mapped.period_code = String(
      payload.period_code
      ?? payload.periodCode
      ?? (year !== null && month !== null ? `SP-${year}-${monthPadded}` : '')
    ).trim();
    mapped.period_name = String(
      payload.period_name
      ?? payload.periodName
      ?? (year !== null && month !== null ? `Salary Period ${monthPadded}/${year}` : '')
    ).trim();
    mapped.period_type = String(payload.period_type ?? payload.periodType ?? 'MONTHLY').trim();
  } else {
    if (payload.period_name !== undefined || payload.periodName !== undefined) {
      mapped.period_name = String(payload.period_name ?? payload.periodName ?? '').trim();
    }
    if (payload.period_type !== undefined || payload.periodType !== undefined) {
      mapped.period_type = String(payload.period_type ?? payload.periodType ?? 'MONTHLY').trim();
    }
  }

  if (year !== null) mapped.year = year;
  if (month !== null) mapped.month = month;
  if (payload.start_date !== undefined || payload.startDate !== undefined) {
    mapped.start_date = stripDate(payload.start_date ?? payload.startDate);
  }
  if (payload.end_date !== undefined || payload.endDate !== undefined) {
    mapped.end_date = stripDate(payload.end_date ?? payload.endDate);
  }
  if (payload.payment_date !== undefined || payload.paymentDate !== undefined) {
    mapped.payment_date = stripDate(payload.payment_date ?? payload.paymentDate);
  }
  if (payload.standard_working_days !== undefined || payload.standardWorkingDays !== undefined) {
    const days = toInt(payload.standard_working_days ?? payload.standardWorkingDays);
    if (days !== null) mapped.standard_working_days = days;
  }
  if (payload.status !== undefined) mapped.status = String(payload.status).toUpperCase();
  if (payload.notes !== undefined) mapped.notes = String(payload.notes ?? '');
  return mapped;
};

const handleEmployees = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};

  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/employees/${id}`);
      return jsonResponse(mapEmployee(extractPayloadData(payload) || {}));
    }

    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 500) || 500,
      department_id: url.searchParams.get('departmentId') || url.searchParams.get('deptId') || undefined,
      q: url.searchParams.get('q') || undefined,
      status: url.searchParams.get('status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/employees', { query });
    const items = (extractPayloadData(payload) || []).map(mapEmployee);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }

  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/employees', {
      method: 'POST',
      body: mapEmployeePayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapEmployee(extractPayloadData(payload) || {}), status);
  }

  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('employee id is required');
    const { payload } = await beRequest(nativeFetch, `/employees/${id}`, {
      method: 'PATCH',
      body: mapEmployeePayloadToBe(rawBody),
    });
    return jsonResponse(mapEmployee(extractPayloadData(payload) || {}));
  }

  if (method === 'DELETE') {
    if (!id) throw new Error('employee id is required');
    await beRequest(nativeFetch, `/employees/${id}`, { method: 'DELETE' });
    return jsonResponse({});
  }

  return null;
};

const handleDepartments = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/departments/${id}`);
      return jsonResponse(mapDepartment(extractPayloadData(payload) || {}));
    }
    const { payload } = await beRequest(nativeFetch, '/departments', {
      query: { page: 1, per_page: toInt(url.searchParams.get('_limit'), 300) || 300 },
    });
    const items = (extractPayloadData(payload) || []).map(mapDepartment);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/departments', {
      method: 'POST',
      body: mapDepartmentPayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapDepartment(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('department id is required');
    const { payload } = await beRequest(nativeFetch, `/departments/${id}`, {
      method: 'PATCH',
      body: mapDepartmentPayloadToBe(rawBody),
    });
    return jsonResponse(mapDepartment(extractPayloadData(payload) || {}));
  }
  if (method === 'DELETE') {
    if (!id) throw new Error('department id is required');
    await beRequest(nativeFetch, `/departments/${id}`, { method: 'DELETE' });
    return jsonResponse({});
  }
  return null;
};

const handlePositions = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/positions/${id}`);
      return jsonResponse(mapPosition(extractPayloadData(payload) || {}));
    }
    const { payload } = await beRequest(nativeFetch, '/positions', {
      query: { page: 1, per_page: toInt(url.searchParams.get('_limit'), 300) || 300 },
    });
    const items = (extractPayloadData(payload) || []).map(mapPosition);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/positions', {
      method: 'POST',
      body: mapPositionPayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapPosition(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('position id is required');
    const { payload } = await beRequest(nativeFetch, `/positions/${id}`, {
      method: 'PATCH',
      body: mapPositionPayloadToBe(rawBody),
    });
    return jsonResponse(mapPosition(extractPayloadData(payload) || {}));
  }
  if (method === 'DELETE') {
    if (!id) throw new Error('position id is required');
    await beRequest(nativeFetch, `/positions/${id}`, { method: 'DELETE' });
    return jsonResponse({});
  }
  return null;
};

const handleRequestTypes = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  if (method !== 'GET') return null;
  if (id) {
    const { payload } = await beRequest(nativeFetch, `/request-types/${id}`);
    return jsonResponse(mapRequestType(extractPayloadData(payload) || {}));
  }
  const { payload } = await beRequest(nativeFetch, '/request-types', {
    query: { page: 1, per_page: toInt(url.searchParams.get('_limit'), 300) || 300 },
  });
  const items = (extractPayloadData(payload) || []).map(mapRequestType);
  return jsonResponse(applyJsonServerSorting(items, url.searchParams));
};

const handleSalaryDetails = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};

  if (id) {
    if (method === 'GET') {
      const { payload } = await beRequest(nativeFetch, `/salary-details/${id}`);
      return jsonResponse(mapSalaryDetail(extractPayloadData(payload) || {}));
    }
    if (method === 'PATCH' || method === 'PUT') {
      const { payload } = await beRequest(nativeFetch, `/salary-details/${id}`, {
        method: 'PATCH',
        body: mapSalaryDetailPayloadToBe(rawBody),
      });
      return jsonResponse(mapSalaryDetail(extractPayloadData(payload) || {}));
    }
    if (method === 'DELETE') {
      const { payload } = await beRequest(nativeFetch, `/salary-details/${id}`, {
        method: 'PATCH',
        body: { transfer_status: 'DELETED' },
      });
      return jsonResponse(mapSalaryDetail(extractPayloadData(payload) || {}));
    }
    return null;
  }

  if (method === 'GET') {
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 500) || 500,
      period_id: url.searchParams.get('periodId') || url.searchParams.get('period_id') || undefined,
      employee_id: url.searchParams.get('employeeId') || url.searchParams.get('employee_id') || undefined,
      transfer_status: url.searchParams.get('transferStatus') || url.searchParams.get('transfer_status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/salary-details', { query });
    const items = (extractPayloadData(payload) || []).map(mapSalaryDetail);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }

  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/salary-details', {
      method: 'POST',
      body: mapSalaryDetailPayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapSalaryDetail(extractPayloadData(payload) || {}), status);
  }

  return null;
};

const handleSalaryPeriods = async (nativeFetch, id, url, init, action = null) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};

  if (method === 'POST' && id && action === 'close') {
    const { payload, status } = await beRequest(nativeFetch, `/salary-periods/${id}/close`, {
      method: 'POST',
      body: rawBody,
    });
    return jsonResponse(extractPayloadData(payload) || payload, status);
  }

  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/salary-periods/${id}`);
      return jsonResponse(mapSalaryPeriod(extractPayloadData(payload) || {}));
    }
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 200) || 200,
      year: url.searchParams.get('year') || undefined,
      status: url.searchParams.get('status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/salary-periods', { query });
    const items = (extractPayloadData(payload) || []).map(mapSalaryPeriod);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }

  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/salary-periods', {
      method: 'POST',
      body: mapSalaryPeriodPayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapSalaryPeriod(extractPayloadData(payload) || {}), status);
  }

  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('salary period id is required');
    const { payload } = await beRequest(nativeFetch, `/salary-periods/${id}`, {
      method: 'PATCH',
      body: mapSalaryPeriodPayloadToBe(rawBody),
    });
    return jsonResponse(mapSalaryPeriod(extractPayloadData(payload) || {}));
  }

  return null;
};

const handleAttendances = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/attendances/${id}`);
      return jsonResponse(mapAttendance(extractPayloadData(payload) || {}));
    }
    const date = url.searchParams.get('date') || url.searchParams.get('attendanceDate');
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 1000) || 1000,
      employee_id: url.searchParams.get('employeeId') || undefined,
      date_from: date || undefined,
      date_to: date || undefined,
      status: url.searchParams.get('status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/attendances', { query });
    const items = (extractPayloadData(payload) || []).map(mapAttendance);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/attendances', {
      method: 'POST',
      body: mapAttendancePayloadToBe(rawBody),
    });
    return jsonResponse(mapAttendance(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('attendance id is required');
    const { payload } = await beRequest(nativeFetch, `/attendances/${id}`, {
      method: 'PATCH',
      body: mapAttendancePayloadToBe(rawBody),
    });
    return jsonResponse(mapAttendance(extractPayloadData(payload) || {}));
  }
  if (method === 'DELETE') {
    if (!id) throw new Error('attendance id is required');
    await beRequest(nativeFetch, `/attendances/${id}`, { method: 'DELETE' });
    return jsonResponse({});
  }
  return null;
};

const handleLeaveRequests = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/leave-requests/${id}`);
      return jsonResponse(mapLeaveRequest(extractPayloadData(payload) || {}));
    }
    const queryStatus = url.searchParams.get('status');
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 1000) || 1000,
      employee_id: url.searchParams.get('requesterId') || url.searchParams.get('employeeId') || undefined,
      status: queryStatus ? normalizeLeaveStatusToBe(queryStatus) : undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/leave-requests', { query });
    const items = (extractPayloadData(payload) || []).map(mapLeaveRequest);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/leave-requests', {
      method: 'POST',
      body: mapLeavePayloadToBe(rawBody),
    });
    const created = extractPayloadData(payload) || {};
    const leaf = created.leave_request ? { ...created.leave_request, ...created.request } : created;
    return jsonResponse(mapLeaveRequest(leaf), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('leave request id is required');
    const patchBody = { ...rawBody };
    if (patchBody.status) patchBody.status = normalizeLeaveStatusToBe(patchBody.status);
    const { payload } = await beRequest(nativeFetch, `/leave-requests/${id}`, {
      method: 'PATCH',
      body: patchBody,
    });
    return jsonResponse(mapLeaveRequest(extractPayloadData(payload) || {}));
  }
  if (method === 'DELETE') {
    if (!id) throw new Error('leave request id is required');
    await beRequest(nativeFetch, `/leave-requests/${id}`, { method: 'DELETE' });
    return jsonResponse({});
  }
  return null;
};

const handleNotifications = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    const requestedReceiverId = url.searchParams.get('userId') || url.searchParams.get('receiverId') || null;
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 100) || 100,
      // Non-privileged users should not query another receiver_id
      // to avoid backend 403 due stale localStorage/user switch.
      receiver_id: isPrivilegedRole() ? (requestedReceiverId || undefined) : undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/notifications', { query });
    let items = (extractPayloadData(payload) || []).map(mapNotification);
    if (id) {
      const picked = items.find((item) => String(item.id) === String(id)) || null;
      return jsonResponse(picked);
    }
    items = applyJsonServerSorting(items, url.searchParams);
    return jsonResponse(items);
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/notifications', {
      method: 'POST',
      body: mapNotificationPayloadToBe(rawBody),
    });
    return jsonResponse(mapNotification(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('notification id is required');
    const body = mapNotificationPayloadToBe(rawBody);
    if (rawBody.isRead !== undefined || rawBody.is_read !== undefined) {
      body.is_read = Boolean(rawBody.isRead ?? rawBody.is_read);
    }
    const { payload } = await beRequest(nativeFetch, `/notifications/${id}`, {
      method: 'PATCH',
      body,
    });
    return jsonResponse(mapNotification(extractPayloadData(payload) || {}));
  }
  return null;
};

const handleServiceCategories = async (nativeFetch, _id, _url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  if (method !== 'GET') return null;
  const { payload } = await beRequest(nativeFetch, '/service-categories');
  const items = (extractPayloadData(payload) || []).map(mapServiceCategory);
  return jsonResponse(items);
};

const handleServiceTickets = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 500) || 500,
      requester_id: url.searchParams.get('requesterId') || url.searchParams.get('employeeId') || undefined,
      status: url.searchParams.get('status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/service-tickets', { query });
    let items = (extractPayloadData(payload) || []).map(mapServiceTicket);
    items = applyJsonServerSorting(items, url.searchParams);
    if (id) {
      const target = items.find((item) => String(item.id) === String(id)) || null;
      return jsonResponse(target);
    }
    return jsonResponse(items);
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/service-tickets', {
      method: 'POST',
      body: mapServiceTicketPayloadToBe(rawBody),
    });
    return jsonResponse(mapServiceTicket(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('ticket id is required');
    const { payload } = await beRequest(nativeFetch, `/service-tickets/${id}`, {
      method: 'PATCH',
      body: mapServiceTicketPayloadToBe(rawBody),
    });
    return jsonResponse(mapServiceTicket(extractPayloadData(payload) || {}));
  }
  return null;
};

const handleContracts = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/contracts/${id}`);
      return jsonResponse(mapContract(extractPayloadData(payload) || {}));
    }
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 500) || 500,
      employee_id: url.searchParams.get('employeeId') || undefined,
      status: url.searchParams.get('status') || undefined,
      q: url.searchParams.get('q') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/contracts', { query });
    const items = (extractPayloadData(payload) || []).map(mapContract);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/contracts', {
      method: 'POST',
      body: mapContractPayloadToBe(rawBody, { isCreate: true }),
    });
    return jsonResponse(mapContract(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('contract id is required');
    const { payload } = await beRequest(nativeFetch, `/contracts/${id}`, {
      method: 'PATCH',
      body: mapContractPayloadToBe(rawBody),
    });
    return jsonResponse(mapContract(extractPayloadData(payload) || {}));
  }
  return null;
};

const handleContractTypes = async (nativeFetch, _id, _url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  if (method !== 'GET') return null;
  const { payload } = await beRequest(nativeFetch, '/contract-types');
  const items = (extractPayloadData(payload) || []).map(mapContractType);
  return jsonResponse(items);
};

const handleAssets = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    if (id) {
      const { payload } = await beRequest(nativeFetch, `/assets/${id}`);
      return jsonResponse(mapAsset(extractPayloadData(payload) || {}));
    }
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 500) || 500,
      status: url.searchParams.get('status') || undefined,
      q: url.searchParams.get('q') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/assets', { query });
    const items = (extractPayloadData(payload) || []).map(mapAsset);
    return jsonResponse(applyJsonServerSorting(items, url.searchParams));
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/assets', {
      method: 'POST',
      body: mapAssetPayloadToBe(rawBody),
    });
    return jsonResponse(mapAsset(extractPayloadData(payload) || {}), status);
  }
  if (method === 'PATCH' || method === 'PUT') {
    if (!id) throw new Error('asset id is required');
    const { payload } = await beRequest(nativeFetch, `/assets/${id}`, {
      method: 'PATCH',
      body: mapAssetPayloadToBe(rawBody),
    });
    return jsonResponse(mapAsset(extractPayloadData(payload) || {}));
  }
  return null;
};

const handleAssetAssignments = async (nativeFetch, id, url, init) => {
  const method = (init?.method || 'GET').toUpperCase();
  const rawBody = init?.body ? JSON.parse(init.body) : {};
  if (method === 'GET') {
    const query = {
      page: 1,
      per_page: toInt(url.searchParams.get('_limit'), 1000) || 1000,
      employee_id: url.searchParams.get('employeeId') || undefined,
      status: url.searchParams.get('status') || undefined,
    };
    const { payload } = await beRequest(nativeFetch, '/asset-assignments', { query });
    let items = (extractPayloadData(payload) || []).map(mapAssetAssignment);
    items = applyJsonServerSorting(items, url.searchParams);
    if (id) {
      const target = items.find((item) => String(item.id) === String(id)) || null;
      return jsonResponse(target);
    }
    return jsonResponse(items);
  }
  if (method === 'POST') {
    const { payload, status } = await beRequest(nativeFetch, '/asset-assignments', {
      method: 'POST',
      body: mapAssetAssignmentPayloadToBe(rawBody),
    });
    return jsonResponse(mapAssetAssignment(extractPayloadData(payload) || {}), status);
  }
  return null;
};

const handleLegacyRequest = async (nativeFetch, url, init = {}) => {
  const { resource, id, action } = resolveLegacyPath(url);
  if (!resource) return null;

  if (resource === 'employees') return handleEmployees(nativeFetch, id, url, init);
  if (resource === 'departments') return handleDepartments(nativeFetch, id, url, init);
  if (resource === 'positions') return handlePositions(nativeFetch, id, url, init);
  if (resource === 'requestTypes') return handleRequestTypes(nativeFetch, id, url, init);
  if (resource === 'salaryDetails' || resource === 'salary-details') {
    return handleSalaryDetails(nativeFetch, id, url, init);
  }
  if (resource === 'salaryPeriods' || resource === 'salary-periods') {
    return handleSalaryPeriods(nativeFetch, id, url, init, action);
  }
  if (resource === 'attendances') return handleAttendances(nativeFetch, id, url, init);
  if (resource === 'leaveRequests') return handleLeaveRequests(nativeFetch, id, url, init);
  if (resource === 'notifications') return handleNotifications(nativeFetch, id, url, init);
  if (resource === 'serviceCategories') return handleServiceCategories(nativeFetch, id, url, init);
  if (resource === 'serviceTickets' || resource === 'supportRequests') return handleServiceTickets(nativeFetch, id, url, init);
  if (resource === 'contracts') return handleContracts(nativeFetch, id, url, init);
  if (resource === 'contractTypes' || resource === 'contract-types') {
    return handleContractTypes(nativeFetch, id, url, init);
  }
  if (resource === 'assets') return handleAssets(nativeFetch, id, url, init);
  if (resource === 'assetAssignments' || resource === 'asset-assignments') {
    return handleAssetAssignments(nativeFetch, id, url, init);
  }
  return null;
};

export const installJsonServerBridge = () => {
  if (typeof window === 'undefined') return;
  if (window.__hrm_json_bridge_installed) return;

  const nativeFetch = window.fetch.bind(window);
  window.fetch = async (input, init = {}) => {
    const rawUrl = typeof input === 'string' ? input : input?.url;
    if (!rawUrl) return nativeFetch(input, init);

    let url;
    try {
      url = new URL(rawUrl, window.location.origin);
    } catch {
      return nativeFetch(input, init);
    }

    const legacyBase = new URL(LEGACY_JSON_SERVER_BASE);
    const shouldIntercept = LEGACY_HOSTS.has(url.host) || url.host === legacyBase.host;
    if (!shouldIntercept) return nativeFetch(input, init);

    try {
      const bridged = await handleLegacyRequest(nativeFetch, url, init);
      if (bridged) return bridged;
    } catch (error) {
      const status = Number.isInteger(error?.status) ? error.status : 500;
      const message = error?.message || 'Bridge request failed';
      const code = error?.code || 'bridge_error';
      const detail = error?.payload || null;
      console.warn('[json-bridge] intercepted request failed:', message);
      return jsonResponse({ success: false, error: code, message, detail }, status);
    }

    return nativeFetch(input, init);
  };

  window.__hrm_json_bridge_installed = true;
};
