import { apiRequest, toIsoLocalDate } from '@/services/beApi.js';

const unwrap = (payload) => payload?.data ?? payload ?? null;

export const fetchShiftCatalog = async () => unwrap(await apiRequest('/shifts'));

export const fetchMyShiftToday = async (date = toIsoLocalDate()) =>
  unwrap(await apiRequest('/my-shift/today', { query: { date } }));

export const fetchMySchedule = async ({ fromDate, toDate }) =>
  unwrap(await apiRequest('/my-schedule', { query: { from_date: fromDate, to_date: toDate } }));

export const fetchTeamSchedule = async ({ fromDate, toDate, departmentId }) =>
  unwrap(await apiRequest('/team-schedule', {
    query: {
      from_date: fromDate,
      to_date: toDate,
      department_id: departmentId,
    },
  }));

export const fetchAttendanceResults = async ({ fromDate, toDate, page = 1, perPage = 200 }) =>
  unwrap(await apiRequest('/attendance-results', {
    query: {
      page,
      per_page: perPage,
      date_from: fromDate,
      date_to: toDate,
    },
  }));

export const fetchHolidays = async ({ fromDate, toDate, page = 1, perPage = 200 } = {}) =>
  unwrap(await apiRequest('/holidays', {
    query: {
      page,
      per_page: perPage,
      date_from: fromDate,
      date_to: toDate,
    },
  }));

export const createHoliday = async (body) =>
  unwrap(await apiRequest('/holidays', { method: 'POST', body }));

export const updateHoliday = async (holidayId, body) =>
  unwrap(await apiRequest(`/holidays/${holidayId}`, { method: 'PATCH', body }));

export const deleteHoliday = async (holidayId) =>
  unwrap(await apiRequest(`/holidays/${holidayId}`, { method: 'DELETE' }));

export const assignTeamShift = async (body) =>
  unwrap(await apiRequest('/team-schedule/assign', { method: 'POST', body }));

export const bulkAssignTeamShift = async (body) =>
  unwrap(await apiRequest('/team-schedule/bulk-assign', { method: 'POST', body }));

export const overrideTeamShift = async (body) =>
  unwrap(await apiRequest('/team-schedule/override', { method: 'PATCH', body }));

export const publishTeamSchedule = async (body) =>
  unwrap(await apiRequest('/team-schedule/publish', { method: 'POST', body }));

export const copyTeamScheduleWeek = async (body) =>
  unwrap(await apiRequest('/team-schedule/copy-week', { method: 'POST', body }));

export const fetchTeamScheduleSuggestions = async ({ fromDate, toDate, departmentId }) =>
  unwrap(await apiRequest('/team-schedule/suggestions', {
    query: {
      from_date: fromDate,
      to_date: toDate,
      department_id: departmentId,
    },
  }));

export const fetchTeamScheduleWarnings = async ({ fromDate, toDate, departmentId }) =>
  unwrap(await apiRequest('/team-schedule/warnings', {
    query: {
      from_date: fromDate,
      to_date: toDate,
      department_id: departmentId,
    },
  }));
