/**
 * giamDocApi.js
 * ─────────────────────────────────────────────────────────
 * Centralized API service cho toàn bộ khu vực Giám đốc.
 * Tất cả dữ liệu đi qua legacy bridge tại http://localhost:3000
 * và được map sang BE API thực.
 * ─────────────────────────────────────────────────────────
 */

const BASE = 'http://localhost:3000';

// ── Helpers ────────────────────────────────────────────────────
const get = (path) => fetch(`${BASE}${path}`).then(r => {
  if (!r.ok) throw new Error(`API Error: ${r.status} ${path}`);
  return r.json();
});

const patch = (path, body) => fetch(`${BASE}${path}`, {
  method: 'PATCH',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(body)
}).then(r => r.json());

// ── Fetch All Resources ────────────────────────────────────────
export const fetchAll = async () => {
  const [employees, departments, positions, leaveRequests,
         salaryDetails, attendances, notifications, requestTypes] = await Promise.all([
    get('/employees'),
    get('/departments'),
    get('/positions'),
    get('/leaveRequests'),
    get('/salaryDetails'),
    get('/attendances'),
    get('/notifications'),
    get('/requestTypes'),
  ]);
  return { employees, departments, positions, leaveRequests, salaryDetails, attendances, notifications, requestTypes };
};

// ── Individual Fetchers ────────────────────────────────────────
export const fetchEmployees    = () => get('/employees');
export const fetchDepartments  = () => get('/departments');
export const fetchPositions    = () => get('/positions');
export const fetchLeaveRequests = () => get('/leaveRequests');
export const fetchSalaryDetails = () => get('/salaryDetails');
export const fetchAttendances  = () => get('/attendances');
export const fetchNotifications = () => get('/notifications');
export const fetchRequestTypes = () => get('/requestTypes');

// ── Leave Request Actions ──────────────────────────────────────
export const approveLeaveRequest = (id) =>
  patch(`/leaveRequests/${id}`, { status: 'ĐÃ_DUYỆT', approvedAt: new Date().toISOString() });

export const rejectLeaveRequest = (id, reason) =>
  patch(`/leaveRequests/${id}`, {
    status: 'TỪ_CHỐI',
    rejectionReason: reason,
    rejectedAt: new Date().toISOString()
  });

export const directorApproveLeaveRequest = (id) =>
  patch(`/leaveRequests/${id}`, { status: 'ĐÃ_DUYỆT', approvedAt: new Date().toISOString() });
