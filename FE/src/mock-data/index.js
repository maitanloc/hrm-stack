import { reactive } from 'vue';
import { AUTH_TOKEN_KEY, BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';

const normalizeKey = (value) =>
  String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .replace(/\s+/g, '_')
    .toUpperCase();

const toInt = (value, fallback = null) => {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const toNum = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const stripDate = (value) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  return text.includes('T') ? text.slice(0, 10) : text.slice(0, 10);
};

const stripTime = (value) => {
  const text = String(value ?? '').trim();
  if (!text) return '';
  if (text.includes('T')) return text.slice(11, 19);
  return text.length === 5 ? `${text}:00` : text.slice(0, 8);
};

const asArray = (value) => (Array.isArray(value) ? value : []);

const replaceList = (target, items) => {
  target.splice(0, target.length, ...items);
};

const withHelpers = (arr, idFields) => {
  const fields = asArray(idFields);
  const findIndexById = (id) =>
    arr.findIndex((item) =>
      fields.some((field) => String(item?.[field]) === String(id)) || String(item?.id) === String(id)
    );

  arr.getAll = function getAll() {
    return this;
  };

  arr.getById = function getById(id) {
    return this.find(
      (item) => fields.some((field) => String(item?.[field]) === String(id)) || String(item?.id) === String(id)
    );
  };

  arr.update = function update(id, data) {
    const index = findIndexById(id);
    if (index === -1) return null;
    Object.assign(this[index], data || {});
    return this[index];
  };

  arr.add = function add(data) {
    this.unshift(data);
    return data;
  };

  arr.delete = function remove(id) {
    const index = findIndexById(id);
    if (index === -1) return false;
    this.splice(index, 1);
    return true;
  };

  return arr;
};

const requestApi = async (path, { query = {}, method = 'GET', body } = {}) => {
  const token = getAccessToken();
  if (!token) throw new Error('missing token');

  const params = new URLSearchParams();
  Object.entries(query || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    params.set(key, String(value));
  });

  const queryString = params.toString();
  const url = `${BE_API_BASE}${path}${queryString ? `?${queryString}` : ''}`;
  const response = await fetch(url, {
    method,
    headers: {
      Authorization: `Bearer ${token}`,
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload?.success === false) {
    if (response.status === 401 || payload?.error === 'token_expired') {
      handleUnauthorized();
    }
    throw new Error(payload?.message || `request failed: ${response.status}`);
  }

  if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'data')) {
    return payload.data;
  }

  return payload;
};

const requestList = async (path, query = {}) => {
  try {
    const data = await requestApi(path, { query });
    return asArray(data);
  } catch {
    return [];
  }
};

const normalizeAttendanceStatus = (value) => {
  const key = normalizeKey(value);
  if (['PRESENT', 'ON_TIME', 'ONTIME', 'DUNG_GIO'].includes(key)) return 'ontime';
  if (['LATE', 'MUON'].includes(key)) return 'late';
  if (['ABSENT', 'VANG'].includes(key)) return 'absent';
  return 'ontime';
};

const normalizeLeaveStatus = (value) => {
  const key = normalizeKey(value);
  if (key === 'PENDING') return 'CHỜ_DUYỆT';
  if (key === 'APPROVED') return 'ĐÃ_DUYỆT';
  if (key === 'REJECTED') return 'TỪ_CHỐI';
  if (key === 'IN_PROGRESS') return 'ĐANG_XỬ_LÝ';
  if (key === 'WAIT_DIRECTOR' || key === 'CHO_GIAM_DOC_DUYET') return 'CHỜ_GIÁM_ĐỐC_DUYỆT';
  if (key === 'WAIT_HR_CONFIRM' || key === 'CHO_XAC_NHAN_HR') return 'CHỜ_XÁC_NHẬN_HR';
  return String(value || 'CHỜ_DUYỆT');
};

const normalizeAssetStatus = (value) => {
  const key = normalizeKey(value);
  if (['AVAILABLE', 'IN_STOCK', 'TRONG_KHO', 'READY'].includes(key)) return 'TRONG_KHO';
  if (['IN_USE', 'ASSIGNED', 'DANG_SU_DUNG', 'DANG_SU_DUNG_'].includes(key)) return 'ĐANG_SỬ_DỤNG';
  if (['BROKEN', 'DAMAGED', 'MAINTENANCE', 'HONG'].includes(key)) return 'HỎNG';
  if (['LIQUIDATED', 'DA_THANH_LY', 'RETIRED'].includes(key)) return 'ĐÃ_THANH_LÝ';
  return 'TRONG_KHO';
};

const normalizeContractStatus = (value) => {
  const key = normalizeKey(value);
  if (['DANG_HIEU_LUC', 'CO_HIEU_LUC', 'HIEU_LUC'].includes(key)) return 'HIỆU_LỰC';
  if (['SAP_HET_HAN', 'HET_HAN', 'EXPIRED'].includes(key)) return 'SẮP_HẾT_HẠN';
  if (['DA_THANH_LY', 'DA_CHAM_DUT', 'TERMINATED'].includes(key)) return 'ĐÃ_CHẤM_DỨT';
  if (['CHO_HIEU_LUC', 'PENDING'].includes(key)) return 'CHỜ_HIỆU_LỰC';
  return 'HIỆU_LỰC';
};

const normalizeRecruitmentStatus = (applicationStatus, workflowStatus) => {
  const appKey = normalizeKey(applicationStatus);
  const wfKey = normalizeKey(workflowStatus);

  if (appKey === 'REJECTED') return 'TỪ_CHỐI';
  if (['PASSED', 'HIRED'].includes(appKey)) return 'TRÚNG_TUYỂN';
  if (appKey === 'INTERVIEWING') return 'ĐANG_PHỎNG_VẤN';
  if (wfKey === 'APPROVED') return 'TP_ĐÃ_DUYỆT';
  if (wfKey === 'PENDING' && ['SCREENING', 'NEW'].includes(appKey)) return 'CHỜ_TP_DUYỆT';
  return 'CHỜ_HR_DUYỆT';
};

const normalizeRole = (employee = {}, managerIds = new Set()) => {
  const explicit = normalizeKey(employee.role || employee.role_code || employee.primary_role_code);
  if (explicit) {
    if (explicit.includes('ADMIN')) return 'ADMIN';
    if (explicit.includes('DIRECTOR')) return 'DIRECTOR';
    if (explicit.includes('MANAGER')) return 'MANAGER';
    if (explicit.includes('HR')) return 'HR';
    return 'EMPLOYEE';
  }

  const employeeId = toInt(employee.employee_id);
  if (employeeId !== null && managerIds.has(employeeId)) return 'MANAGER';

  const positionKey = normalizeKey(employee.position_name || employee.job_title);
  if (positionKey.includes('GIAM_DOC') || positionKey.includes('DIRECTOR')) return 'DIRECTOR';
  if (positionKey.includes('TRUONG_PHONG') || positionKey.includes('MANAGER')) return 'MANAGER';
  if (positionKey.includes('HR') || positionKey.includes('NHAN_SU')) return 'HR';
  return 'EMPLOYEE';
};

const mapEmployee = (item = {}, managerIds = new Set()) => {
  const employeeId = toInt(item.employee_id ?? item.employeeId ?? item.id);
  const departmentId = toInt(item.department_id ?? item.departmentId);
  const positionId = toInt(item.position_id ?? item.positionId);
  const departmentName = item.department_name ?? item.departmentName ?? '';
  const positionName = item.position_name ?? item.positionName ?? item.job_title ?? '';

  return {
    ...item,
    id: employeeId,
    employeeId,
    employee_id: employeeId,
    employeeCode: item.employee_code ?? item.employeeCode ?? '',
    employee_code: item.employee_code ?? item.employeeCode ?? '',
    fullName: item.full_name ?? item.fullName ?? '',
    full_name: item.full_name ?? item.fullName ?? '',
    name: item.full_name ?? item.fullName ?? '',
    companyEmail: item.company_email ?? item.companyEmail ?? '',
    company_email: item.company_email ?? item.companyEmail ?? '',
    phoneNumber: item.phone_number ?? item.phoneNumber ?? '',
    phone_number: item.phone_number ?? item.phoneNumber ?? '',
    hireDate: item.hire_date ?? item.hireDate ?? null,
    hire_date: item.hire_date ?? item.hireDate ?? null,
    dateOfBirth: item.date_of_birth ?? item.dateOfBirth ?? null,
    date_of_birth: item.date_of_birth ?? item.dateOfBirth ?? null,
    status: item.status ?? 'ĐANG_LÀM_VIỆC',
    departmentId,
    department_id: departmentId,
    deptId: departmentId,
    department: {
      departmentId,
      department_id: departmentId,
      departmentName,
      department_name: departmentName,
    },
    positionId,
    position_id: positionId,
    position: {
      positionId,
      position_id: positionId,
      positionName,
      position_name: positionName,
    },
    role: normalizeRole(item, managerIds),
    baseLeaveDays: toNum(item.base_leave_days ?? item.baseLeaveDays, 12),
  };
};

const mapDepartment = (item = {}) => {
  const departmentId = toInt(item.department_id ?? item.departmentId ?? item.id);
  const managerId = toInt(item.manager_id ?? item.managerId);
  const statusKey = normalizeKey(item.status);
  const isActive = !['INACTIVE', 'DISABLED', '0', 'FALSE'].includes(statusKey) && item.status !== 0 && item.status !== false;

  return {
    ...item,
    id: departmentId,
    departmentId,
    department_id: departmentId,
    departmentCode: item.department_code ?? item.departmentCode ?? '',
    department_code: item.department_code ?? item.departmentCode ?? '',
    departmentName: item.department_name ?? item.departmentName ?? '',
    department_name: item.department_name ?? item.departmentName ?? '',
    managerId,
    manager_id: managerId,
    manager: item.manager_name ?? item.managerName ?? null,
    parent_id: toInt(item.parent_department_id ?? item.parent_id),
    status: isActive,
  };
};

const mapPosition = (item = {}) => {
  const positionId = toInt(item.position_id ?? item.positionId ?? item.id);
  return {
    ...item,
    id: positionId,
    positionId,
    position_id: positionId,
    positionCode: item.position_code ?? item.positionCode ?? '',
    position_code: item.position_code ?? item.positionCode ?? '',
    positionName: item.position_name ?? item.positionName ?? '',
    position_name: item.position_name ?? item.positionName ?? '',
    name: item.position_name ?? item.positionName ?? '',
    status: item.status ?? true,
  };
};

const mapRequestType = (item = {}) => {
  const id = toInt(item.request_type_id ?? item.requestTypeId ?? item.id);
  return {
    ...item,
    id,
    requestTypeId: id,
    request_type_id: id,
    requestTypeName: item.request_type_name ?? item.requestTypeName ?? '',
    request_type_name: item.request_type_name ?? item.requestTypeName ?? '',
    category: item.category ?? item.module ?? 'NGHỈ_PHÉP',
  };
};

const mapAttendance = (item = {}) => {
  const id = toInt(item.attendance_id ?? item.attendanceId ?? item.id);
  const employeeId = toInt(item.employee_id ?? item.employeeId);
  const attendanceDate = stripDate(item.attendance_date ?? item.attendanceDate ?? item.date);
  let checkIn2Raw = item.check_in_time_2 ?? item.checkIn2 ?? '';
  let checkOut2Raw = item.check_out_time_2 ?? item.checkOut2 ?? '';
  if ((!checkIn2Raw || !checkOut2Raw) && item.notes) {
    try {
      const meta = JSON.parse(String(item.notes));
      checkIn2Raw = checkIn2Raw || meta?.checkIn2 || meta?.check_in_time_2 || '';
      checkOut2Raw = checkOut2Raw || meta?.checkOut2 || meta?.check_out_time_2 || '';
    } catch {
      // ignore invalid legacy notes content
    }
  }
  return {
    ...item,
    id,
    attendanceId: id,
    attendance_id: id,
    employeeId,
    employee_id: employeeId,
    attendanceDate,
    attendance_date: attendanceDate,
    date: attendanceDate,
    checkIn1: stripTime(item.check_in_time ?? item.checkIn1),
    checkOut1: stripTime(item.check_out_time ?? item.checkOut1),
    checkIn2: stripTime(checkIn2Raw),
    checkOut2: stripTime(checkOut2Raw),
    status: normalizeAttendanceStatus(item.status),
  };
};

const mapLeaveRequest = (item = {}) => {
  const leaveRequestId = toInt(item.leave_request_id ?? item.leaveRequestId ?? item.id);
  const requestId = toInt(item.request_id ?? item.requestId ?? leaveRequestId);
  const employeeId = toInt(item.employee_id ?? item.requester_id ?? item.requesterId ?? item.employeeId);
  const requestTypeId = toInt(item.request_type_id ?? item.requestTypeId ?? item.leave_type_id ?? item.leaveTypeId, 1);
  const status = normalizeLeaveStatus(item.request_status ?? item.status);

  return {
    ...item,
    id: leaveRequestId,
    leaveRequestId: leaveRequestId,
    leave_request_id: leaveRequestId,
    requestId,
    request_id: requestId,
    requesterId: employeeId,
    requester_id: employeeId,
    employeeId,
    employee_id: employeeId,
    requestTypeId,
    request_type_id: requestTypeId,
    leaveTypeId: toInt(item.leave_type_id ?? item.leaveTypeId, requestTypeId),
    leave_type_id: toInt(item.leave_type_id ?? item.leaveTypeId, requestTypeId),
    startDate: stripDate(item.from_date ?? item.startDate),
    endDate: stripDate(item.to_date ?? item.endDate),
    from_date: stripDate(item.from_date ?? item.startDate),
    to_date: stripDate(item.to_date ?? item.endDate),
    days: toNum(item.number_of_days ?? item.days, 1),
    number_of_days: toNum(item.number_of_days ?? item.days, 1),
    status,
    requestStatus: status,
    request_status: status,
    reason: item.request_reason ?? item.reason ?? item.notes ?? item.request_notes ?? '',
    notes: item.request_notes ?? item.notes ?? item.request_reason ?? item.reason ?? '',
    requesterName: item.employee_name ?? item.requesterName ?? '',
    employeeName: item.employee_name ?? item.requesterName ?? '',
    requestDate: stripDate(item.request_date ?? item.created_at ?? item.from_date),
    request_date: stripDate(item.request_date ?? item.created_at ?? item.from_date),
  };
};

const mapSalaryDetail = (item = {}) => {
  const salaryId = toInt(item.salary_detail_id ?? item.salaryId ?? item.id);
  const employeeId = toInt(item.employee_id ?? item.employeeId);
  const periodKey = String(item.period_month_key ?? '').trim();
  const [yearText, monthText] = periodKey.split('-');

  return {
    ...item,
    id: salaryId,
    salaryId: salaryId,
    salary_id: salaryId,
    salary_detail_id: salaryId,
    employeeId,
    employee_id: employeeId,
    periodId: toInt(item.period_id ?? item.periodId),
    period_id: toInt(item.period_id ?? item.periodId),
    periodName: item.period_name ?? item.periodName ?? '',
    month: toInt(item.month, toInt(monthText, new Date().getMonth() + 1)),
    year: toInt(item.year, toInt(yearText, new Date().getFullYear())),
    basicSalary: toNum(item.basic_salary ?? item.basicSalary, 0),
    basic_salary: toNum(item.basic_salary ?? item.basicSalary, 0),
    grossSalary: toNum(item.gross_salary ?? item.grossSalary, 0),
    gross_salary: toNum(item.gross_salary ?? item.grossSalary, 0),
    netSalary: toNum(item.net_salary ?? item.netSalary, 0),
    net_salary: toNum(item.net_salary ?? item.netSalary, 0),
    allowance: toNum(item.total_allowances ?? item.allowance ?? item.bonus, 0),
    tax: toNum(item.personal_income_tax ?? item.tax ?? item.total_deductions, 0),
    transferStatus: item.transfer_status ?? item.transferStatus ?? '',
    transfer_status: item.transfer_status ?? item.transferStatus ?? '',
  };
};

const mapContract = (item = {}) => {
  const contractId = toInt(item.contract_id ?? item.contractId ?? item.id);
  return {
    ...item,
    id: contractId,
    contractId,
    contract_id: contractId,
    contractCode: item.contract_code ?? item.contractCode ?? item.contract_number ?? '',
    contract_code: item.contract_code ?? item.contractCode ?? item.contract_number ?? '',
    contractType: item.contract_type_code ?? item.contract_type_name ?? item.contractType ?? '',
    contract_type: item.contract_type_code ?? item.contract_type_name ?? item.contractType ?? '',
    employeeId: toInt(item.employee_id ?? item.employeeId),
    employee_id: toInt(item.employee_id ?? item.employeeId),
    startDate: stripDate(item.effective_date ?? item.start_date ?? item.startDate),
    endDate: stripDate(item.expiry_date ?? item.end_date ?? item.endDate),
    basicSalary: toNum(item.basic_salary ?? item.basicSalary, 0),
    basic_salary: toNum(item.basic_salary ?? item.basicSalary, 0),
    status: normalizeContractStatus(item.ui_status ?? item.status),
    workLocation: item.work_location ?? item.workLocation ?? '',
    jobTitle: item.job_title ?? item.jobTitle ?? item.position_name ?? '',
  };
};

const mapAsset = (item = {}, assignmentMap = new Map()) => {
  const assetId = toInt(item.asset_id ?? item.assetId ?? item.id);
  return {
    ...item,
    id: assetId,
    assetId,
    asset_id: assetId,
    assetCode: item.asset_code ?? item.assetCode ?? '',
    asset_code: item.asset_code ?? item.assetCode ?? '',
    assetName: item.asset_name ?? item.assetName ?? '',
    asset_name: item.asset_name ?? item.assetName ?? '',
    category: item.category_name ?? item.category ?? 'Khác',
    status: normalizeAssetStatus(item.status),
    assignedTo: assignmentMap.get(assetId) ?? null,
  };
};

const mapJobPosting = (item = {}) => {
  const jobId = toInt(item.recruitment_position_id ?? item.jobId ?? item.id);
  const statusKey = normalizeKey(item.status);
  const parseList = (value) => {
    if (Array.isArray(value)) return value;
    if (typeof value !== 'string') return [];
    return value
      .split(/\r?\n|;|\./)
      .map((part) => part.trim())
      .filter(Boolean);
  };

  return {
    ...item,
    jobId,
    recruitmentPositionId: jobId,
    title: item.position_name ?? item.title ?? '',
    positionName: item.position_name ?? item.positionName ?? '',
    departmentId: toInt(item.department_id ?? item.departmentId),
    departmentName: item.department_name ?? item.departmentName ?? '',
    workType: item.employment_type ?? item.workType ?? 'FULL_TIME',
    location: item.work_location ?? item.location ?? 'TP.HCM',
    salaryMin: toNum(item.salary_min ?? item.salaryMin, 0),
    salaryMax: toNum(item.salary_max ?? item.salaryMax, 0),
    description: item.description ?? '',
    requirements: parseList(item.requirements ?? item.description),
    benefits: parseList(item.benefits),
    status: ['OPEN', 'ACTIVE', 'DANG_MO'].includes(statusKey) ? 'ĐANG_MỞ' : 'ĐÃ_ĐÓNG',
  };
};

const mapRecruitmentApplication = (item = {}) => {
  const applicationId = toInt(item.candidate_id ?? item.applicationId ?? item.id);
  const interviewDate = item.latest_interview_date
    ? `${item.latest_interview_date}T${String(item.latest_interview_time || '00:00:00').slice(0, 8)}`
    : item.suggested_interview_date
      ? `${item.suggested_interview_date}T${String(item.suggested_interview_time || '00:00:00').slice(0, 8)}`
      : null;

  return {
    ...item,
    applicationId,
    id: applicationId,
    backendCandidateId: applicationId,
    jobId: toInt(item.recruitment_position_id ?? item.jobId),
    departmentId: toInt(item.department_id ?? item.departmentId),
    departmentName: item.department_name ?? item.departmentName ?? '',
    positionName: item.position_name ?? item.positionName ?? '',
    jobTitle: item.position_name ?? item.jobTitle ?? '',
    fullName: item.full_name ?? item.fullName ?? '',
    email: item.email ?? '',
    phone: item.phone_number ?? item.phone ?? '',
    cvUrl: item.cv_download_url ?? item.cv_url ?? item.cvUrl ?? '',
    aiMatchScore: toNum(item.ai_score ?? item.aiMatchScore, 0),
    status: normalizeRecruitmentStatus(item.application_status, item.workflow_status),
    appliedDate: item.applied_at ? `${item.applied_at}T00:00:00` : new Date().toISOString(),
    reviewedByHR: null,
    reviewedByManager: item.manager_review_notes ?? null,
    managerScore: item.manager_score !== undefined ? toNum(item.manager_score, 0) : null,
    interviewDate,
    notes: item.notes ?? '',
  };
};

export const mockEmployees = withHelpers(reactive([]), ['employeeId', 'employee_id']);
export const mockAttendances = withHelpers(reactive([]), ['attendanceId', 'attendance_id']);
export const mockLeaveRequests = withHelpers(reactive([]), ['leaveRequestId', 'leave_request_id', 'requestId', 'request_id', 'id']);
export const mockSalaryDetails = withHelpers(reactive([]), ['salaryId', 'salary_id', 'salary_detail_id']);
export const mockDepartments = withHelpers(reactive([]), ['departmentId', 'department_id']);
export const mockAssets = withHelpers(reactive([]), ['assetId', 'asset_id']);
export const mockContracts = withHelpers(reactive([]), ['contractId', 'contract_id']);
export const mockPositions = withHelpers(reactive([]), ['positionId', 'position_id']);
export const mockRequestTypes = withHelpers(reactive([]), ['requestTypeId', 'request_type_id']);
export const mockCandidates = withHelpers(reactive([]), ['candidate_id', 'candidateId', 'id']);
export const mockApplications = withHelpers(reactive([]), ['applicationId', 'id']);
export const mockJobPostings = withHelpers(reactive([]), ['jobId', 'recruitmentPositionId', 'id']);

export const mockDB = { requests: mockLeaveRequests, attendances: mockAttendances };

mockApplications.approve = function approve(id) {
  this.update(id, { status: 'CHỜ_TP_DUYỆT' });
};
mockApplications.reject = function reject(id, reason) {
  this.update(id, { status: 'TỪ_CHỐI', notes: reason || '' });
};
mockApplications.schedule = function schedule(id, date) {
  this.update(id, { status: 'ĐANG_PHỎNG_VẤN', interviewDate: date || null });
};
mockApplications.hire = function hire(id) {
  this.update(id, { status: 'TRÚNG_TUYỂN' });
};

mockLeaveRequests.approve = function approve(id) {
  this.update(id, { status: 'ĐÃ_DUYỆT', requestStatus: 'ĐÃ_DUYỆT' });
};
mockLeaveRequests.reject = function reject(id, reason) {
  this.update(id, {
    status: 'TỪ_CHỐI',
    requestStatus: 'TỪ_CHỐI',
    notes: reason || '',
    rejectionReason: reason || '',
  });
};
mockLeaveRequests.delete = function remove(id) {
  this.delete(id);
};
mockLeaveRequests.directorApprove = function directorApprove(id) {
  this.update(id, { status: 'ĐÃ_DUYỆT', requestStatus: 'ĐÃ_DUYỆT' });
};

const clearAll = () => {
  [
    mockEmployees,
    mockAttendances,
    mockLeaveRequests,
    mockSalaryDetails,
    mockDepartments,
    mockAssets,
    mockContracts,
    mockPositions,
    mockRequestTypes,
    mockCandidates,
    mockApplications,
    mockJobPostings,
  ].forEach((list) => replaceList(list, []));
};

let loadPromise = null;

export const loadMockData = async ({ force = false } = {}) => {
  if (!force && loadPromise) return loadPromise;

  loadPromise = (async () => {
    const token = getAccessToken();
    if (!token) {
      clearAll();
      return false;
    }

    const [
      departmentsRaw,
      employeesRaw,
      positionsRaw,
      requestTypesRaw,
      leaveRequestsRaw,
      attendancesRaw,
      salaryDetailsRaw,
      contractsRaw,
      assetsRaw,
      assignmentsRaw,
      recruitmentPositionsRaw,
      recruitmentCandidatesRaw,
    ] = await Promise.all([
      requestList('/departments', { page: 1, per_page: 500 }),
      requestList('/employees', { page: 1, per_page: 1000 }),
      requestList('/positions', { page: 1, per_page: 300 }),
      requestList('/request-types', { page: 1, per_page: 200 }),
      requestList('/leave-requests', { page: 1, per_page: 1000 }),
      requestList('/attendances', { page: 1, per_page: 1000 }),
      requestList('/salary-details', { page: 1, per_page: 1000 }),
      requestList('/contracts', { page: 1, per_page: 600 }),
      requestList('/assets', { page: 1, per_page: 600 }),
      requestList('/asset-assignments', { page: 1, per_page: 1000, status: 'ASSIGNED' }),
      requestList('/recruitment-positions', { page: 1, per_page: 500 }),
      requestList('/recruitment-candidates', { page: 1, per_page: 1000 }),
    ]);

    const mappedDepartments = departmentsRaw.map(mapDepartment);
    const managerIds = new Set(
      mappedDepartments
        .map((item) => toInt(item.managerId ?? item.manager_id))
        .filter((value) => value !== null)
    );

    const assignmentMap = new Map();
    assignmentsRaw.forEach((item) => {
      const assetId = toInt(item.asset_id ?? item.assetId);
      const employeeId = toInt(item.employee_id ?? item.employeeId);
      if (assetId !== null && employeeId !== null) {
        assignmentMap.set(assetId, employeeId);
      }
    });

    replaceList(mockDepartments, mappedDepartments);
    replaceList(mockEmployees, employeesRaw.map((item) => mapEmployee(item, managerIds)));
    replaceList(mockPositions, positionsRaw.map(mapPosition));
    replaceList(mockRequestTypes, requestTypesRaw.map(mapRequestType));
    replaceList(mockLeaveRequests, leaveRequestsRaw.map(mapLeaveRequest));
    replaceList(mockAttendances, attendancesRaw.map(mapAttendance));
    replaceList(mockSalaryDetails, salaryDetailsRaw.map(mapSalaryDetail));
    replaceList(mockContracts, contractsRaw.map(mapContract));
    replaceList(mockAssets, assetsRaw.map((item) => mapAsset(item, assignmentMap)));
    replaceList(mockJobPostings, recruitmentPositionsRaw.map(mapJobPosting));
    replaceList(mockApplications, recruitmentCandidatesRaw.map(mapRecruitmentApplication));
    replaceList(mockCandidates, recruitmentCandidatesRaw.map((item) => ({ ...item })));

    return true;
  })();

  try {
    return await loadPromise;
  } finally {
    loadPromise = null;
  }
};

if (typeof window !== 'undefined') {
  if (getAccessToken()) {
    void loadMockData();
  }

  window.addEventListener('storage', (event) => {
    if (event.key !== AUTH_TOKEN_KEY) return;
    if (event.newValue) {
      void loadMockData({ force: true });
    } else {
      clearAll();
    }
  });
}

export const fetchEmployeesAPI = async () => {
  await loadMockData();
  return mockEmployees;
};

export const fetchAttendancesAPI = async (employeeId) => {
  await loadMockData();
  if (!employeeId) return mockAttendances;
  return mockAttendances.filter((item) => String(item.employeeId) === String(employeeId));
};

export const fetchLeaveRequestsAPI = async (employeeId) => {
  await loadMockData();
  if (!employeeId) return mockLeaveRequests;
  return mockLeaveRequests.filter((item) => String(item.requesterId) === String(employeeId));
};

export const fetchSalaryDetailsAPI = async (employeeId) => {
  await loadMockData();
  if (!employeeId) return mockSalaryDetails;
  return mockSalaryDetails.filter((item) => String(item.employeeId) === String(employeeId));
};
