import { apiRequest } from '@/services/beApi.js';

const listData = (payload) => (Array.isArray(payload?.data) ? payload.data : []);

const patch = (path, body) =>
  apiRequest(path, {
    method: 'PATCH',
    body,
  });

export const fetchEmployees = () => apiRequest('/employees');
export const fetchDepartments = () => apiRequest('/departments');
export const fetchPositions = () => apiRequest('/positions');
export const fetchLeaveRequests = () => apiRequest('/leave-requests');
export const fetchSalaryDetails = () => apiRequest('/salary-details');
export const fetchAttendances = () => apiRequest('/attendances');
export const fetchNotifications = () => apiRequest('/notifications');
export const fetchRequestTypes = () => apiRequest('/request-types');

export const fetchAll = async () => {
  const [
    employeesRes,
    departmentsRes,
    positionsRes,
    leaveRequestsRes,
    salaryDetailsRes,
    attendancesRes,
    notificationsRes,
    requestTypesRes,
  ] = await Promise.all([
    fetchEmployees(),
    fetchDepartments(),
    fetchPositions(),
    fetchLeaveRequests(),
    fetchSalaryDetails(),
    fetchAttendances(),
    fetchNotifications(),
    fetchRequestTypes(),
  ]);

  return {
    employees: listData(employeesRes),
    departments: listData(departmentsRes),
    positions: listData(positionsRes),
    leaveRequests: listData(leaveRequestsRes),
    salaryDetails: listData(salaryDetailsRes),
    attendances: listData(attendancesRes),
    notifications: listData(notificationsRes),
    requestTypes: listData(requestTypesRes),
  };
};

export const approveLeaveRequest = (id) =>
  patch(`/leave-requests/${id}`, {
    status: 'ĐÃ_DUYỆT',
    approved_at: new Date().toISOString(),
  });

export const rejectLeaveRequest = (id, reason) =>
  patch(`/leave-requests/${id}`, {
    status: 'TỪ_CHỐI',
    rejection_reason: reason,
    rejected_at: new Date().toISOString(),
  });

export const directorApproveLeaveRequest = (id) =>
  patch(`/leave-requests/${id}`, {
    status: 'ĐÃ_DUYỆT',
    approved_at: new Date().toISOString(),
  });
