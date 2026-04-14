/**
 * UI Mapper: Ánh xạ từ chuẩn dữ liệu DB (Raw Enum/Code) sang cấu hình hiển thị UI
 * Giúp Frontend Components code sạch hơn, gỡ bỏ sự ràng buộc CSS cứng vào Data Logic.
 */

// Hàm định dạng chữ cái tự chia Initials cho Avatar
// VD: "Trần Lan Anh" -> "LA", "Nguyễn Lê" -> "NL"
export const getInitials = (fullName) => {
  if (!fullName) return '?';
  const parts = fullName.split(' ').filter(Boolean);
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  const last = parts[parts.length - 1];
  const preLast = parts[parts.length - 2];
  return (preLast.charAt(0) + last.charAt(0)).toUpperCase();
};

export const getAvatarColors = (employeeId) => {
  const colors = [
    { bg: 'bg-pink-100', text: 'text-pink-600' },
    { bg: 'bg-indigo-100', text: 'text-indigo-600' },
    { bg: 'bg-green-100', text: 'text-green-600' },
    { bg: 'bg-amber-100', text: 'text-amber-600' },
    { bg: 'bg-teal-100', text: 'text-teal-600' },
    { bg: 'bg-purple-100', text: 'text-purple-600' },
    { bg: 'bg-blue-100', text: 'text-blue-600' },
  ];
  return colors[employeeId % colors.length];
};

export const getRequestStatusUI = (status) => {
  const map = {
    'CHỜ_DUYỆT': { label: 'Chờ duyệt', icon: 'schedule', statusClass: 'bg-[var(--sys-brand-soft)] text-[var(--sys-brand-solid)] border-[var(--sys-brand-border)]', dotClass: 'bg-[var(--sys-brand-solid)]' },
    'ĐÃ_DUYỆT': { label: 'Đã duyệt', icon: 'task_alt', statusClass: 'bg-green-50 text-green-600 border-green-100', dotClass: 'bg-green-600' },
    'TỪ_CHỐI': { label: 'Đã từ chối', icon: 'cancel', statusClass: 'bg-red-50 text-red-600 border-red-100', dotClass: 'bg-red-600' },
    'NHÁP': { label: 'Nháp', icon: 'edit', statusClass: 'bg-slate-50 text-slate-600 border-slate-100', dotClass: 'bg-slate-500' },
  };
  return map[status] || map['CHỜ_DUYỆT'];
};

export const getRequestTypeUI = (categoryStr) => {
  const map = {
    'NGHỈ_PHÉP': { icon: 'event_busy', color: 'text-blue-600', bg: 'bg-blue-50', catKey: 'nghi_phep' },
    'TUYỂN_DỤNG': { icon: 'person_add', color: 'text-indigo-600', bg: 'bg-indigo-50', catKey: 'tuyen_dung' },
    'DIỀU_CHỈNH_CÔNG': { icon: 'payments', color: 'text-green-600', bg: 'bg-green-50', catKey: 'luong' },
    'TẠM_ỨNG_LƯƠNG': { icon: 'badge', color: 'text-amber-600', bg: 'bg-amber-50', catKey: 'hop_dong' },
    'THANH_TOÁN': { icon: 'account_balance_wallet', color: 'text-purple-600', bg: 'bg-purple-50', catKey: 'khac' },
    'KHÁC': { icon: 'more_horiz', color: 'text-slate-600', bg: 'bg-slate-50', catKey: 'khac' }
  };
  return map[categoryStr] || map['KHÁC'];
};
