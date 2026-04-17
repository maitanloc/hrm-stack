<template>
  <section class="mobile-clock-shell">
    <div class="mobile-clock-card">
      <header class="mobile-clock-header">
        <h1>Chấm công</h1>
        <p>Mở ứng dụng và bấm một nút để chấm công.</p>
      </header>

      <div class="mobile-status" :class="statusClass">
        {{ userMessage }}
      </div>

      <div v-if="gpsRequirementMessage" class="mobile-status status-warning">
        {{ gpsRequirementMessage }}
      </div>

      <div class="geo-probe" :class="{ 'geo-probe-active': isWorking }">
        <span class="probe-dot"></span>
        <span>{{ probeText }}</span>
      </div>

      <div v-if="shiftSummary" class="shift-summary-card">
        <div class="shift-summary-header">
          <span class="material-symbols-outlined text-[18px]">badge</span>
          <span>Ca làm việc hôm nay</span>
        </div>
        <div class="shift-summary-main">
          <strong>{{ shiftSummary.name }}</strong>
          <span>{{ shiftSummary.window }}</span>
        </div>
        <p class="shift-summary-meta">
          {{ shiftSummary.meta }}
        </p>
      </div>

      <p class="zone-text">
        {{ liveStateLabel }}
      </p>

      <p class="location-text">
        {{ locationLabel }}
      </p>
      <p class="gps-time-text">
        {{ gpsCapturedLabel }}
      </p>
      <p class="zone-text">
        {{ zoneHint }}
      </p>
      <p v-if="anchorHint" class="anchor-text">
        {{ anchorHint }}
      </p>

      <div v-if="readyForAction" class="action-grid">
        <button
          class="action-btn action-in"
          type="button"
          :disabled="isWorking"
          @click="handleAttendance('CHECKIN')"
        >
          {{ isWorking && pendingType === 'CHECKIN' ? 'Đang xử lý' : 'Đi làm' }}
        </button>
        <button
          class="action-btn action-out"
          type="button"
          :disabled="isWorking"
          @click="handleAttendance('CHECKOUT')"
        >
          {{ isWorking && pendingType === 'CHECKOUT' ? 'Đang xử lý' : 'Ra về' }}
        </button>
      </div>

      <button
        v-else-if="showRetry"
        class="action-btn action-retry"
        type="button"
        :disabled="isWorking"
        @click="retryCurrentState"
      >
        {{ isWorking ? 'Đang kiểm tra' : retryButtonLabel }}
      </button>

      <p v-if="lastResultTime" class="last-result">
        Lần chấm công gần nhất: {{ lastResultTime }}
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useCurrentUser } from '@/composables/useCurrentUser.js';
import { apiRequest } from '@/services/beApi.js';
import { clearAuthSession } from '@/services/session.js';
import { useConfirm } from '@/composables/useConfirm.js';
import { fetchMyShiftToday } from '@/services/workforceApi.js';
import {
  ensureDeviceId,
  LAST_EVENT_STORAGE_KEY,
  loadGeoState,
  nowApiTime,
  platformCode,
  persistGeoState,
  readCurrentLocation,
  requestAttendancePrecheck,
} from '@/services/attendanceGeo.js';

const AUTO_PRECHECK_INTERVAL_MS = 45000;

const { employeeId, employeeCode, fullName } = useCurrentUser();
const { showAlert } = useConfirm();

const checking = ref(false);
const acting = ref(false);
const userMessage = ref('Đang dò vị trí của bạn so với mốc công ty.');
const statusType = ref('info');
const pendingType = ref('CHECKIN');
const precheckState = ref(null);
const latestLocation = ref(null);
const lastResultTime = ref('');
const gpsWatchId = ref(null);
const lastAutoPrecheckAt = ref(0);
const lastPopupKey = ref('');
const lastPopupAt = ref(0);
const gpsRequirementMessage = ref('');
const gpsActionLabel = ref('');
const shiftContext = ref(null);

const isWorking = computed(() => checking.value || acting.value);

const statusClass = computed(() => {
  if (statusType.value === 'success') return 'status-success';
  if (statusType.value === 'warning') return 'status-warning';
  if (statusType.value === 'danger') return 'status-danger';
  return 'status-info';
});

const locationLabel = computed(() => {
  if (!latestLocation.value) return 'Chưa lấy được vị trí hiện tại.';
  const { lat, lng, accuracy } = latestLocation.value;
  const userLabel = `${employeeCode.value || ''} ${fullName.value || ''}`.trim();
  return `${userLabel || 'Nhân viên'} · ${lat.toFixed(6)}, ${lng.toFixed(6)} · sai số ${Math.round(accuracy)}m`;
});

const blockedReason = computed(() => String(precheckState.value?.reason_code || '').toUpperCase());
const retryButtonLabel = computed(() => (blockedReason.value === 'DEVICE_NOT_TRUSTED' ? 'Xác minh lại' : 'Thử lại'));

const probeText = computed(() => {
  if (checking.value) return 'Đang dò xem bạn có ở gần công ty không.';
  if (acting.value) return 'Đang xác nhận chấm công từ vị trí hiện tại.';
  return 'Hệ thống đã sẵn sàng kiểm tra vị trí.';
});

const zoneHint = computed(() => {
  const status = String(precheckState.value?.status || '').toLowerCase();
  const risk = String(precheckState.value?.risk_level || '').toUpperCase();
  const hasToken = Boolean(precheckState.value?.precheck_token);
  const reason = String(precheckState.value?.reason_code || '').toUpperCase();

  if (status === 'unregistered_device' || reason === 'DEVICE_NOT_TRUSTED') {
    return 'Thiết bị này chưa được xác minh. Vui lòng đăng nhập lại trên điện thoại này.';
  }
  if (!hasToken || status === 'blocked_outside_zone') return 'Bạn đang ngoài khu vực làm việc.';
  if (risk === 'GREEN') return 'Bạn đang trong khu vực có thể chấm công.';
  if (risk === 'YELLOW') return 'Bạn đang gần khu vực làm việc và vẫn có thể chấm công.';
  return 'Hệ thống đang đánh giá khu vực làm việc.';
});

const liveStateLabel = computed(() => {
  const risk = String(precheckState.value?.risk_level || '').toUpperCase();
  const status = String(precheckState.value?.status || '').toLowerCase();
  const reason = String(precheckState.value?.reason_code || '').toUpperCase();

  if (reason === 'DEVICE_NOT_TRUSTED' || status === 'unregistered_device') return 'Thiết bị mới cần xác minh lại';
  if (status === 'blocked_outside_zone' || reason === 'OUT_OF_GEOFENCE' || reason === 'IMPOSSIBLE_TRAVEL') return 'Bạn đang ngoài khu vực cho phép';
  if (risk === 'YELLOW') return 'Bạn đang gần khu vực công ty';
  if (risk === 'GREEN') return 'Bạn đang trong khu vực công ty';
  return 'Hệ thống đang xác định trạng thái GPS';
});

const gpsCapturedLabel = computed(() => {
  const captured = Number(latestLocation.value?.capturedAt || 0);
  if (!Number.isFinite(captured) || captured <= 0) return 'GPS chưa xác định thời điểm.';
  const d = new Date(captured);
  return `GPS cập nhật lúc ${d.toLocaleTimeString('vi-VN')}.`;
});

const readyForAction = computed(() => {
  const pre = precheckState.value;
  if (!pre) return false;
  return Boolean(pre.precheck_token);
});

const showRetry = computed(() => {
  const reason = blockedReason.value;
  return reason === 'OUT_OF_GEOFENCE'
    || reason === 'IMPOSSIBLE_TRAVEL'
    || reason === 'DEVICE_NOT_TRUSTED'
    || !readyForAction.value;
});

const anchorHint = computed(() => {
  const label = String(precheckState.value?.company_anchor_label || '').trim();
  const distance = Number(precheckState.value?.company_anchor_distance_m);
  if (!Number.isFinite(distance) || distance < 0) return '';

  const rounded = Math.round(distance);
  if (label) {
    return `Mốc gần nhất: ${label} · cách ${rounded}m.`;
  }
  return `Khoảng cách tới mốc công ty gần nhất: ${rounded}m.`;
});

const shiftSummary = computed(() => {
  const shift = shiftContext.value?.shift;
  if (!shift) return null;
  const parts = [];
  if (shift.source === 'OVERRIDE') parts.push('Ca đổi theo ngày');
  if (shift.source === 'EMPLOYEE_DEFAULT') parts.push('Ca mặc định của bạn');
  if (shift.source === 'DEPARTMENT_SCHEDULE') parts.push('Lịch phòng ban');
  if (shiftContext.value?.holiday?.holiday_name) parts.push(`Ngày nghỉ: ${shiftContext.value.holiday.holiday_name}`);
  if (shiftContext.value?.leave?.leave_type_name) parts.push(`Đơn nghỉ: ${shiftContext.value.leave.leave_type_name}`);
  if (shiftContext.value?.remote?.request_type_name) parts.push('Có lịch làm việc từ xa');
  if (shiftContext.value?.business_trip?.request_type_name) parts.push('Có lịch công tác');

  return {
    name: shift.shift_name || shift.shift_code || 'Ca làm việc',
    window: [shift.start_time, shift.end_time].filter(Boolean).join(' - ') || 'Đang cập nhật',
    meta: parts.join(' · ') || 'Hệ thống đã xác định ca làm việc từ lịch hiện tại.',
  };
});

const showSmartAlert = async (key, title, message) => {
  const now = Date.now();
  const dedupeKey = `${key}:${message}`;
  if (lastPopupKey.value === dedupeKey && now - lastPopupAt.value < 15000) return;
  lastPopupKey.value = dedupeKey;
  lastPopupAt.value = now;
  await showAlert(title, message);
};

const setLatestLocation = (coords, capturedAt = Date.now()) => {
  latestLocation.value = {
    lat: Number(coords?.lat ?? 0),
    lng: Number(coords?.lng ?? 0),
    accuracy: Number(coords?.accuracy ?? 999),
    capturedAt: Number(capturedAt || Date.now()),
  };
  return latestLocation.value;
};

const requestWithFallback = async (primaryPath, fallbackPaths = [], options = {}) => {
  try {
    return await apiRequest(primaryPath, options);
  } catch (error) {
    if (Number(error?.status) !== 404) throw error;
    for (const path of fallbackPaths) {
      try {
        return await apiRequest(path, options);
      } catch (fallbackError) {
        if (Number(fallbackError?.status) !== 404) throw fallbackError;
      }
    }
    throw error;
  }
};

const extractErrorMessage = (error) => {
  const status = Number(error?.status || error?.payload?.status || 0);
  if (status === 404) {
    return 'Hệ thống chấm công đang được cập nhật. Vui lòng bấm Thử lại sau ít phút.';
  }

  if (error?.reason === 'INSECURE_CONTEXT') {
    return 'Trang hiện chưa ở kết nối HTTPS an toàn nên trình duyệt chặn GPS. Hãy mở hệ thống bằng đường dẫn HTTPS để chấm công.';
  }
  if (error?.reason === 'PERMISSION_DENIED') {
    return 'Bạn chưa cấp quyền vị trí. Hãy bật GPS và cho phép truy cập vị trí để chấm công.';
  }

  const payloadMessage = String(error?.payload?.data?.user_message || error?.payload?.message || '').trim();
  const rawMessage = payloadMessage || String(error?.message || '').trim();
  const lowered = rawMessage.toLowerCase();

  // Never expose internal stack/class errors to end users.
  if (
    lowered.includes('class "') ||
    lowered.includes('not found') ||
    lowered.includes('sqlstate') ||
    lowered.includes('exception') ||
    lowered.includes('fatal error')
  ) {
    return 'Hệ thống đang bận. Vui lòng bấm Thử lại sau ít phút.';
  }

  if (rawMessage) return rawMessage;
  return 'Không thể thực hiện thao tác. Vui lòng thử lại.';
};

const buildPrecheckBody = (type, location) => ({
  employee_id: Number(employeeId.value),
  device_id: ensureDeviceId(),
  platform: platformCode(),
  lat: location.lat,
  lng: location.lng,
  accuracy_m: Number(location.accuracy || 999),
  attendance_type: type,
  client_time: nowApiTime(),
  app_version: 'mobile-web-1.1.0',
});

const runPrecheckRequest = async (type, location) => {
  const payload = await requestWithFallback('/attendance/precheck', ['/attendance/pre-check'], {
    method: 'POST',
    body: buildPrecheckBody(type, location),
  });
  applyPrecheckResult(payload?.data || {});
};

const applyPrecheckResult = (precheck) => {
  precheckState.value = precheck;
  persistGeoState(precheck, latestLocation.value);
  const risk = String(precheck?.risk_level || '').toUpperCase();
  const hasToken = Boolean(precheck?.precheck_token);
  const reason = String(precheck?.reason_code || '').toUpperCase();

  if (!hasToken) {
    statusType.value = 'warning';
    if (reason === 'DEVICE_NOT_TRUSTED') {
      userMessage.value = 'Đây là điện thoại mới. Vui lòng xác minh để tiếp tục.';
      void showSmartAlert('device-new', 'Thiết bị mới', userMessage.value);
      return;
    }
    userMessage.value = precheck?.user_message || 'Bạn đang ngoài khu vực làm việc. Vui lòng đến đúng vị trí rồi bấm Thử lại.';
    void showSmartAlert('outside-zone', 'Ngoài khu vực', userMessage.value);
    return;
  }

  if (risk === 'YELLOW') {
    statusType.value = 'warning';
    userMessage.value = precheck?.user_message || 'Bạn đang gần khu vực làm việc. Hệ thống vẫn cho phép chấm công.';
    void showSmartAlert('yellow-zone', 'Cảnh báo vị trí', userMessage.value);
    return;
  }

  statusType.value = 'success';
  userMessage.value = precheck?.user_message || 'Bạn đang ở đúng khu vực làm việc. Bấm Đi làm hoặc Ra về.';
};

const runPrecheckOnly = async (type = 'CHECKIN') => {
  if (!employeeId.value) {
    statusType.value = 'danger';
    userMessage.value = 'Không tìm thấy thông tin nhân viên. Vui lòng đăng nhập lại.';
    return;
  }

  statusType.value = 'info';
  userMessage.value = 'Đang dò vị trí của bạn so với mốc công ty.';
  checking.value = true;
  try {
    const location = await readCurrentLocation();
    setLatestLocation(location, location.capturedAt);
    await runPrecheckRequest(type, location);
    gpsRequirementMessage.value = '';
    gpsActionLabel.value = '';
  } catch (error) {
    statusType.value = 'danger';
    userMessage.value = extractErrorMessage(error);
    gpsRequirementMessage.value = userMessage.value;
    gpsActionLabel.value = error?.reason === 'INSECURE_CONTEXT' ? 'Mở bản HTTPS' : 'Bật GPS';
  } finally {
    checking.value = false;
  }
};

const autoRefreshPrecheck = async () => {
  if (isWorking.value || !employeeId.value || !latestLocation.value) return;
  if (typeof document !== 'undefined' && document.hidden) return;
  const now = Date.now();
  if (now - lastAutoPrecheckAt.value < AUTO_PRECHECK_INTERVAL_MS) return;
  lastAutoPrecheckAt.value = now;

  try {
    await runPrecheckRequest(pendingType.value || 'CHECKIN', latestLocation.value);
  } catch {
    // Keep silent for background refresh; explicit actions still show full errors.
  }
};

const startGpsWatcher = () => {
  if (!navigator.geolocation || gpsWatchId.value !== null) return;
  gpsWatchId.value = navigator.geolocation.watchPosition(
    (position) => {
      setLatestLocation(
        {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy: position.coords.accuracy || 999,
        },
        position.timestamp || Date.now()
      );
      void autoRefreshPrecheck();
    },
    () => {
      // Do not interrupt UX here; explicit precheck will show actionable message.
    },
    {
      enableHighAccuracy: true,
      timeout: 12000,
      maximumAge: 0,
    }
  );
};

const stopGpsWatcher = () => {
  if (gpsWatchId.value === null || !navigator.geolocation) return;
  navigator.geolocation.clearWatch(gpsWatchId.value);
  gpsWatchId.value = null;
};

const handleAttendance = async (type) => {
  if (!employeeId.value) {
    statusType.value = 'danger';
    userMessage.value = 'Không tìm thấy thông tin nhân viên. Vui lòng đăng nhập lại.';
    return;
  }

  statusType.value = 'info';
  userMessage.value = 'Đang dò vị trí của bạn so với mốc công ty.';
  acting.value = true;
  pendingType.value = type;
  try {
    const precheckResponse = await requestAttendancePrecheck({
      employeeId: Number(employeeId.value),
      attendanceType: type,
    });
    const location = precheckResponse.location;
    setLatestLocation(location, location.capturedAt);
    const precheck = precheckResponse.precheck;
    applyPrecheckResult(precheck);
    if (!precheck.precheck_token) {
      return;
    }

    const endpoint = type === 'CHECKIN' ? '/attendance/checkin' : '/attendance/checkout';
    const fallbackEndpoints = type === 'CHECKIN' ? ['/attendance/clock-in'] : ['/attendance/clock-out'];
    const commitRes = await requestWithFallback(endpoint, fallbackEndpoints, {
      method: 'POST',
      body: {
        employee_id: Number(employeeId.value),
        precheck_token: precheck.precheck_token,
        client_time: nowApiTime(),
      },
    });

    const result = commitRes?.data || {};
    statusType.value = 'success';
    userMessage.value = result.user_message || 'Chấm công thành công.';
    if (result.event_time) {
      const d = new Date(result.event_time);
      lastResultTime.value = Number.isNaN(d.getTime()) ? String(result.event_time) : d.toLocaleString('vi-VN');
      localStorage.setItem(LAST_EVENT_STORAGE_KEY, lastResultTime.value);
    }

    await runPrecheckOnly(type);
  } catch (error) {
    statusType.value = 'danger';
    userMessage.value = extractErrorMessage(error);
    gpsRequirementMessage.value = userMessage.value;
    gpsActionLabel.value = error?.reason === 'INSECURE_CONTEXT' ? 'Mở bản HTTPS' : 'Bật GPS';
  } finally {
    acting.value = false;
  }
};

const retryCurrentState = async () => {
  if (blockedReason.value === 'DEVICE_NOT_TRUSTED') {
    await showSmartAlert('device-relogin', 'Xác minh lại', 'Đây là điện thoại mới. Vui lòng đăng nhập lại để xác minh thiết bị.');
    clearAuthSession();
    window.location.href = '/login';
    return;
  }
  if (gpsActionLabel.value === 'Mở bản HTTPS') {
    window.location.href = `https://anhsinhvienfpoly.click${window.location.pathname}`;
    return;
  }
  await runPrecheckOnly(pendingType.value || 'CHECKIN');
};

const refreshShiftContext = async () => {
  if (!employeeId.value) return;
  try {
    shiftContext.value = await fetchMyShiftToday();
  } catch {
    shiftContext.value = null;
  }
};

onMounted(async () => {
  const savedLast = localStorage.getItem(LAST_EVENT_STORAGE_KEY);
  if (savedLast) {
    lastResultTime.value = savedLast;
  }
  const savedGeo = loadGeoState();
  if (savedGeo?.location) {
    latestLocation.value = savedGeo.location;
  }
  if (savedGeo?.raw) {
    precheckState.value = savedGeo.raw;
  }
  startGpsWatcher();
  await refreshShiftContext();
  await runPrecheckOnly('CHECKIN');
});

onUnmounted(() => {
  stopGpsWatcher();
});
</script>

<style scoped>
.mobile-clock-shell {
  min-height: calc(100vh - 120px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 12px;
}

.mobile-clock-card {
  width: 100%;
  max-width: 460px;
  border-radius: 18px;
  border: 1px solid var(--sys-border-subtle);
  background: var(--sys-bg-surface);
  padding: 16px;
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.mobile-clock-header h1 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--sys-text-primary);
}

.mobile-clock-header p {
  margin: 4px 0 0;
  font-size: 0.9rem;
  color: var(--sys-text-secondary);
}

.mobile-status {
  border-radius: 12px;
  padding: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.4;
  border: 1px solid transparent;
}

.status-info {
  background: var(--sys-info-soft);
  color: var(--sys-info-text);
  border-color: var(--sys-info-border);
}

.status-success {
  background: var(--sys-success-soft);
  color: var(--sys-success-text);
  border-color: var(--sys-success-border);
}

.status-warning {
  background: var(--sys-warning-soft);
  color: var(--sys-warning-text);
  border-color: var(--sys-warning-border);
}

.status-danger {
  background: var(--sys-danger-soft);
  color: var(--sys-danger-text);
  border-color: var(--sys-danger-border);
}

.location-text {
  margin: 0;
  font-size: 0.8rem;
  color: var(--sys-text-secondary);
  line-height: 1.45;
}

.gps-time-text {
  margin: -6px 0 0;
  font-size: 0.78rem;
  color: var(--sys-text-secondary);
}

.zone-text {
  margin: -4px 0 0;
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--sys-text-primary);
}

.anchor-text {
  margin: -4px 0 0;
  font-size: 0.82rem;
  color: var(--sys-text-secondary);
}

.shift-summary-card {
  border: 1px solid var(--sys-border-subtle);
  background: var(--sys-bg-page);
  border-radius: 14px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.shift-summary-header {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--sys-text-secondary);
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.shift-summary-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  color: var(--sys-text-primary);
  font-size: 0.92rem;
}

.shift-summary-main strong {
  font-size: 0.95rem;
}

.shift-summary-main span {
  color: var(--sys-brand-solid);
  font-weight: 700;
  white-space: nowrap;
}

.shift-summary-meta {
  margin: 0;
  color: var(--sys-text-secondary);
  font-size: 0.78rem;
  line-height: 1.45;
}

.geo-probe {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: 999px;
  border: 1px solid var(--sys-border-subtle);
  background: var(--sys-bg-page);
  color: var(--sys-text-secondary);
  font-size: 0.78rem;
  font-weight: 600;
}

.geo-probe-active {
  color: var(--sys-brand-solid);
  border-color: rgba(37, 99, 235, 0.35);
}

.probe-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: var(--sys-brand-solid);
  box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.42);
  animation: probePulse 1.6s infinite;
}

@keyframes probePulse {
  0% {
    box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.42);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(37, 99, 235, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
  }
}

.action-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.action-btn {
  border: 0;
  border-radius: 14px;
  min-height: 52px;
  padding: 0 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-in {
  background: #0f766e;
  color: #fff;
}

.action-out {
  background: #0f172a;
  color: #fff;
}

.action-retry {
  background: #f59e0b;
  color: #111827;
}

.last-result {
  margin: 0;
  font-size: 0.8rem;
  color: var(--sys-text-secondary);
}

@media (max-width: 480px) {
  .action-grid {
    grid-template-columns: 1fr;
  }

  .mobile-clock-card {
    border-radius: 14px;
    padding: 12px;
  }
}
</style>
