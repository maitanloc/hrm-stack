export const formatDate = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '-';
  const date = new Date(raw);
  if (Number.isNaN(date.getTime())) return raw;
  return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

export const formatDateTime = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '-';
  const date = new Date(raw.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return raw;
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')} ${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`;
};

export const formatCurrency = (value) => {
  const amount = Number(value || 0);
  if (!Number.isFinite(amount)) return '-';
  return `${amount.toLocaleString('vi-VN')} VND`;
};

export const toBadgeMap = (pairs) => {
  const map = {};
  pairs.forEach(([key, cls]) => {
    map[String(key).toUpperCase()] = cls;
  });
  return map;
};
