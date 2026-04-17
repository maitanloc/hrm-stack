/**
 * Nhãn tiếng Việt cho mã lý do chấm công / cảnh báo (đồng bộ BE AttendanceRiskController).
 */
export const REASON_CODE_LABEL_VI = {
  OK_IN_ZONE: 'Trong vùng cho phép',
  SIGNAL_WEAK: 'Tín hiệu định vị yếu',
  GPS_DRIFT_MINOR: 'Vị trí lệch nhẹ (GPS)',
  OUT_OF_GEOFENCE: 'Ngoài khu vực làm việc',
  DEVICE_NOT_TRUSTED: 'Thiết bị chưa xác minh',
  MULTI_DEVICE_SHORT_TIME: 'Đổi thiết bị trong thời gian ngắn',
  IMPOSSIBLE_TRAVEL: 'Di chuyển bất thường (quá xa trong thời gian ngắn)',
};

export function labelReasonCode(code) {
  if (!code) return '—';
  const key = String(code).toUpperCase();
  return REASON_CODE_LABEL_VI[key] || key;
}

export const ATTENDANCE_TYPE_LABEL_VI = {
  CHECKIN: 'Vào ca',
  CHECKOUT: 'Ra ca',
};

export function labelAttendanceType(type) {
  if (!type) return '—';
  const key = String(type).toUpperCase();
  return ATTENDANCE_TYPE_LABEL_VI[key] || type;
}
