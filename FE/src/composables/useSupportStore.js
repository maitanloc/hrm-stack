import { reactive, computed } from 'vue';
import { getSessionItem } from '@/services/session.js';
import { apiRequest } from '@/services/beApi.js';

const AVATAR_COLORS = [
  'linear-gradient(135deg,#2563eb,#1d4ed8)',
  'linear-gradient(135deg,#16a34a,#15803d)',
  'linear-gradient(135deg,#dc2626,#b91c1c)',
  'linear-gradient(135deg,#ea580c,#c2410c)',
  'linear-gradient(135deg,#7c3aed,#6d28d9)',
  'linear-gradient(135deg,#0891b2,#0e7490)',
];

const state = reactive({
  tickets: [],
});

const mapBackendStatusToUi = (status) => {
  const key = String(status || '').toUpperCase();
  if (['RESOLVED', 'CLOSED', 'HOAN_THANH'].includes(key)) return 'Hoan thanh';
  if (['IN_PROGRESS', 'PROCESSING', 'DANG_XU_LY'].includes(key)) return 'Dang xu ly';
  if (['REJECTED', 'TU_CHOI'].includes(key)) return 'Tu choi';
  return 'Cho xu ly';
};

const mapUiStatusToBackend = (status) => {
  if (status === 'Hoan thanh') return 'RESOLVED';
  if (status === 'Dang xu ly') return 'IN_PROGRESS';
  if (status === 'Tu choi') return 'CLOSED';
  return 'OPEN';
};

const mapTicketRow = (req, employees, departments, categories = []) => {
  const empId = req.employeeId || req.requesterId || req.requester_id;
  const emp = employees.find((e) => String(e.id || e.employee_id) === String(empId));
  const deptId =
    emp?.deptId ||
    emp?.departmentId ||
    emp?.department_id ||
    emp?.department?.departmentId ||
    emp?.department?.department_id;
  const dept = departments.find((d) => String(d.id || d.department_id) === String(deptId));
  const category = categories.find((c) => String(c.id || c.category_id) === String(req.categoryId || req.category_id));

  return {
    id: String(req.id || req.ticket_id || req.ticketId || Date.now()),
    employeeName: emp ? emp.fullName || emp.full_name || emp.name : 'Nguoi dung he thong',
    department: dept ? dept.departmentName || dept.name || dept.department_name : 'N/A',
    category: req.type || category?.name || req.category || 'Ho tro IT',
    title: req.title || 'Khong co tieu de',
    priority: req.priority || 'MEDIUM',
    status: mapBackendStatusToUi(req.status),
    date: req.date || new Date().toLocaleDateString('vi-VN'),
    deadline: req.deadline || '',
    asset: req.asset || '',
    description: req.desc || req.description || '',
    avatarColor: AVATAR_COLORS[String(req.id || req.ticket_id || '').length % AVATAR_COLORS.length],
    note: req.note || '',
  };
};

export function useSupportStore() {
  const fetchTickets = async () => {
    const [ticketRes, empRes, deptRes, categoryRes] = await Promise.all([
      apiRequest('/service-tickets', { query: { page: 1, per_page: 500 } }),
      apiRequest('/employees', { query: { page: 1, per_page: 500 } }),
      apiRequest('/departments', { query: { page: 1, per_page: 200 } }),
      apiRequest('/service-categories'),
    ]);

    const tickets = ticketRes?.data || [];
    const employees = empRes?.data || [];
    const departments = deptRes?.data || [];
    const categories = categoryRes?.data || [];

    state.tickets = tickets.map((req) => mapTicketRow(req, employees, departments, categories));
  };

  const addTicket = async (ticketData) => {
    const currentUserId = getSessionItem('userId') || '2';
    const payload = await apiRequest('/service-tickets', {
      method: 'POST',
      body: {
        requester_id: Number(currentUserId),
        title: ticketData.title,
        description: ticketData.description,
        priority: ticketData.priority || 'MEDIUM',
        category_id: Number(ticketData.categoryId || 1),
      },
    });

    await fetchTickets();
    return payload?.data?.ticket_id ?? payload?.data?.id ?? null;
  };

  const updateStatus = async (ticketId, newStatus, note = '') => {
    await apiRequest(`/service-tickets/${ticketId}`, {
      method: 'PATCH',
      body: {
        status: mapUiStatusToBackend(newStatus),
        note: note || undefined,
      },
    });
    await fetchTickets();
  };

  const tickets = computed(() => state.tickets);

  return {
    tickets,
    fetchTickets,
    addTicket,
    updateStatus,
  };
}
