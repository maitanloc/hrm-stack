import { reactive, computed } from 'vue';
import { getSessionItem } from '@/services/session.js';

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
  if (['RESOLVED', 'CLOSED', 'HOÀN_THÀNH'].includes(key)) return 'Hoàn thành';
  if (['IN_PROGRESS', 'PROCESSING', 'ĐANG_XỬ_LÝ'].includes(key)) return 'Đang xử lý';
  if (['REJECTED', 'TỪ_CHỐI'].includes(key)) return 'Từ chối';
  return 'Chờ xử lý';
};

const mapUiStatusToBackend = (status) => {
  if (status === 'Hoàn thành') return 'RESOLVED';
  if (status === 'Đang xử lý') return 'IN_PROGRESS';
  if (status === 'Từ chối') return 'CLOSED';
  return 'OPEN';
};

const mapTicketRow = (req, employees, departments, categories = []) => {
  const empId = req.employeeId || req.requesterId || req.requester_id;
  const emp = employees.find((e) => String(e.id) === String(empId) || String(e.employeeId) === String(empId));
  const deptId = emp?.deptId || emp?.departmentId || emp?.department?.departmentId;
  const dept = departments.find((d) => String(d.id) === String(deptId) || String(d.departmentId) === String(deptId));
  const category = categories.find((c) => String(c.id) === String(req.categoryId || req.category_id));

  return {
    id: req.id ? req.id.toString() : Date.now().toString(),
    employeeName: emp ? emp.fullName || emp.full_name || emp.name : 'Người dùng hệ thống',
    department: dept ? dept.departmentName || dept.name || dept.department_name : 'N/A',
    category: req.type || category?.name || req.category || 'Hỗ trợ IT & Thiết bị',
    title: req.title || 'Không có tiêu đề',
    priority: req.priority || 'MEDIUM',
    status: mapBackendStatusToUi(req.status),
    date: req.date || new Date().toLocaleDateString('vi-VN'),
    deadline: req.deadline || '',
    asset: req.asset || '',
    description: req.desc || req.description || '',
    avatarColor: AVATAR_COLORS[String(req.id || '').length % AVATAR_COLORS.length],
    note: req.note || '',
  };
};

export function useSupportStore() {
  const fetchTickets = async () => {
    const [ticketRes, empRes, deptRes, categoryRes] = await Promise.all([
      fetch('http://localhost:3000/serviceTickets?_limit=500'),
      fetch('http://localhost:3000/employees?_limit=500'),
      fetch('http://localhost:3000/departments?_limit=200'),
      fetch('http://localhost:3000/serviceCategories?_limit=50'),
    ]);

    if (!ticketRes.ok || !empRes.ok || !deptRes.ok) {
      state.tickets = [];
      throw new Error('Failed to load support tickets from backend');
    }

    const [tickets, employees, departments, categories] = await Promise.all([
      ticketRes.json(),
      empRes.json(),
      deptRes.json(),
      categoryRes.ok ? categoryRes.json() : Promise.resolve([]),
    ]);

    state.tickets = (tickets || []).map((req) => mapTicketRow(req, employees || [], departments || [], categories || []));
  };

  const addTicket = async (ticketData) => {
    const currentUserId = getSessionItem('userId') || '2';
    const body = {
      requesterId: currentUserId,
      title: ticketData.title,
      description: ticketData.description,
      priority: ticketData.priority || 'MEDIUM',
      categoryId: ticketData.categoryId || 1,
    };

    const res = await fetch('http://localhost:3000/serviceTickets', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });

    if (!res.ok) {
      throw new Error('Failed to create support ticket');
    }

    const created = await res.json();
    await fetchTickets();
    return created?.id;
  };

  const updateStatus = async (ticketId, newStatus, note = '') => {
    const res = await fetch(`http://localhost:3000/serviceTickets/${ticketId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        status: mapUiStatusToBackend(newStatus),
        note: note || undefined,
      }),
    });

    if (!res.ok) {
      throw new Error('Failed to update support ticket');
    }

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
