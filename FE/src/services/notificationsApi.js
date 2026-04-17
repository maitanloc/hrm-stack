import { apiRequest } from '@/services/beApi.js';

const unwrap = (payload) => payload?.data ?? payload ?? [];

const pad = (value) => String(value).padStart(2, '0');

export const formatNotificationTime = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return 'Vừa xong';

  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return raw;

  const diffMs = Date.now() - date.getTime();
  const diffMinutes = Math.floor(diffMs / 60000);
  if (diffMinutes <= 0) return 'Vừa xong';
  if (diffMinutes < 60) return `${diffMinutes} phút trước`;

  const diffHours = Math.floor(diffMinutes / 60);
  if (diffHours < 24) return `${diffHours} giờ trước`;

  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 7) return `${diffDays} ngày trước`;

  return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;
};

export const mapNotificationType = (item) => {
  const priority = String(item?.priority || '').toUpperCase();
  const type = String(item?.notification_type || '').toUpperCase();

  if (priority === 'CAO') return 'warning';
  if (type.includes('LEAVE')) return 'info';
  if (type.includes('HOLIDAY')) return 'warning';
  if (type.includes('PAYROLL')) return 'success';
  return 'info';
};

export const mapNotificationIcon = (item) => {
  const type = String(item?.notification_type || '').toUpperCase();
  const title = String(item?.title || '').toUpperCase();

  if (type.includes('LEAVE') || title.includes('NGHỈ')) return 'event_busy';
  if (type.includes('HOLIDAY') || title.includes('LỄ')) return 'event_available';
  if (type.includes('PAYROLL') || title.includes('LƯƠNG')) return 'payments';
  if (title.includes('CÔNG TÁC')) return 'flight_takeoff';
  if (title.includes('REMOTE')) return 'laptop_chromebook';
  return 'notifications';
};

export const mapNotificationItem = (item) => ({
  id: item?.notification_id ?? item?.id ?? Math.random(),
  title: String(item?.title || 'Thông báo hệ thống'),
  desc: String(item?.content || item?.desc || 'Không có nội dung chi tiết.'),
  content: String(item?.content || item?.desc || ''),
  time: formatNotificationTime(item?.created_at || item?.read_date),
  createdAt: item?.created_at || null,
  type: mapNotificationType(item),
  icon: mapNotificationIcon(item),
  isRead: Boolean(item?.is_read ?? item?.isRead),
  priority: String(item?.priority || '').toUpperCase(),
  actionUrl: String(item?.action_url || '').trim(),
  raw: item,
});

export const fetchNotifications = async ({ page = 1, perPage = 20, isRead } = {}) => {
  const payload = await apiRequest('/notifications', {
    query: {
      page,
      per_page: perPage,
      is_read: isRead === undefined ? undefined : (isRead ? 1 : 0),
    },
  });
  return unwrap(payload).map(mapNotificationItem);
};

export const markNotificationRead = async (notificationId) => {
  if (!notificationId) return null;
  const payload = await apiRequest(`/notifications/${notificationId}`, {
    method: 'PATCH',
    body: { is_read: true },
  });
  const data = payload?.data ?? payload ?? null;
  return data ? mapNotificationItem(data) : null;
};
