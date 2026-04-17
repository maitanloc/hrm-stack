import { apiRequest } from '@/services/beApi.js';

export const DEVICE_STORAGE_KEY = 'attendance_mobile_device_id';
export const ATTENDANCE_GEO_STATE_KEY = 'attendance_geo_state_v1';
export const LAST_EVENT_STORAGE_KEY = 'attendance_last_event_label';

const ATTENDANCE_PRECHECK_PATHS = ['/attendance/precheck', '/attendance/pre-check'];
const ATTENDANCE_BOOTSTRAP_PATH = '/attendance/bootstrap';

const isLoopbackHost = (host) => ['localhost', '127.0.0.1', '0.0.0.0'].includes(String(host || '').toLowerCase());

export const isSecureGeoContext = () => {
  if (typeof window === 'undefined') return true;
  if (window.isSecureContext) return true;
  return isLoopbackHost(window.location.hostname);
};

export const ensureDeviceId = () => {
  if (typeof window === 'undefined') return 'web-ssr';
  const fromStorage = window.localStorage.getItem(DEVICE_STORAGE_KEY);
  if (fromStorage) return fromStorage;
  const seed = `${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
  const created = `web-${seed}`;
  window.localStorage.setItem(DEVICE_STORAGE_KEY, created);
  return created;
};

export const platformCode = () => {
  if (typeof navigator === 'undefined') return 'ANDROID';
  const ua = String(navigator.userAgent || '').toLowerCase();
  if (ua.includes('iphone') || ua.includes('ipad') || ua.includes('ios')) return 'IOS';
  return 'ANDROID';
};

export const nowApiTime = () => {
  const d = new Date();
  const pad = (value) => String(value).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
};

export const normalizeLocation = (coords, capturedAt = Date.now()) => ({
  lat: Number(coords?.lat ?? 0),
  lng: Number(coords?.lng ?? 0),
  accuracy: Number(coords?.accuracy ?? 999),
  capturedAt: Number(capturedAt || Date.now()),
});

const saveGeoState = (state) => {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem(ATTENDANCE_GEO_STATE_KEY, JSON.stringify(state));
};

export const loadGeoState = () => {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.localStorage.getItem(ATTENDANCE_GEO_STATE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

const requestWithFallback = async (paths, options = {}) => {
  const [primaryPath, ...fallbackPaths] = paths;
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

const geolocationError = (reason, message) => {
  const error = new Error(message);
  error.reason = reason;
  return error;
};

export const readCurrentLocation = async () => {
  if (typeof navigator === 'undefined' || !navigator.geolocation) {
    throw geolocationError('UNSUPPORTED', 'Thiết bị này chưa hỗ trợ định vị GPS.');
  }
  if (!isSecureGeoContext()) {
    throw geolocationError('INSECURE_CONTEXT', 'Trình duyệt chỉ cho phép lấy GPS trên kết nối HTTPS an toàn.');
  }

  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve(normalizeLocation({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy: position.coords.accuracy || 999,
        }, position.timestamp || Date.now()));
      },
      (error) => {
        if (error?.code === 1) {
          reject(geolocationError('PERMISSION_DENIED', 'Bạn chưa cấp quyền vị trí. Hãy bật GPS và cho phép truy cập vị trí để chấm công.'));
          return;
        }
        if (error?.code === 3) {
          reject(geolocationError('TIMEOUT', 'Hệ thống chưa lấy được GPS kịp thời. Vui lòng mở GPS rồi thử lại.'));
          return;
        }
        reject(geolocationError('UNAVAILABLE', 'Không lấy được vị trí hiện tại. Vui lòng kiểm tra GPS và thử lại.'));
      },
      {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 0,
      }
    );
  });
};

export const buildGeoStateFromPrecheck = (precheck = {}, location = null) => {
  const currentLocation = precheck?.current_location || {};
  const resolvedLocation = location || normalizeLocation({
    lat: currentLocation.lat,
    lng: currentLocation.lng,
    accuracy: currentLocation.accuracy_m,
  });

  return {
    status: String(precheck?.status || '').toLowerCase(),
    riskLevel: String(precheck?.risk_level || '').toUpperCase(),
    reasonCode: String(precheck?.reason_code || '').toUpperCase(),
    allowClockIn: Boolean(precheck?.allow_clock_in),
    userMessage: String(precheck?.user_message || '').trim(),
    companyAnchorLabel: String(precheck?.company_anchor_label || '').trim(),
    companyAnchorDistanceM: Number(precheck?.company_anchor_distance_m),
    greenRadiusM: Number(precheck?.zone?.green_radius_m || 0),
    yellowRadiusM: Number(precheck?.zone?.yellow_radius_m || 0),
    capturedAt: Number(resolvedLocation?.capturedAt || Date.now()),
    location: resolvedLocation,
    raw: precheck,
  };
};

export const persistGeoState = (precheck = {}, location = null) => {
  const state = buildGeoStateFromPrecheck(precheck, location);
  saveGeoState(state);
  return state;
};

export const bootstrapAttendanceDevice = async ({ employeeId, location, workingArea = null }) => {
  const normalizedLocation = normalizeLocation(location);
  return apiRequest(ATTENDANCE_BOOTSTRAP_PATH, {
    method: 'POST',
    body: {
      employee_id: Number(employeeId),
      device_id: ensureDeviceId(),
      platform: platformCode(),
      lat: normalizedLocation.lat,
      lng: normalizedLocation.lng,
      accuracy_m: Number(normalizedLocation.accuracy || 999),
      working_area: workingArea || undefined,
    },
  });
};

export const requestAttendancePrecheck = async ({ employeeId, attendanceType = 'CHECKIN' }) => {
  const location = await readCurrentLocation();
  let payload = await requestWithFallback(ATTENDANCE_PRECHECK_PATHS, {
    method: 'POST',
    body: {
      employee_id: Number(employeeId),
      device_id: ensureDeviceId(),
      platform: platformCode(),
      lat: location.lat,
      lng: location.lng,
      accuracy_m: Number(location.accuracy || 999),
      attendance_type: attendanceType,
      client_time: nowApiTime(),
      app_version: 'mobile-web-1.2.0',
    },
  });

  let precheck = payload?.data || {};
  const reasonCode = String(precheck?.reason_code || '').toUpperCase();
  if (reasonCode === 'DEVICE_NOT_TRUSTED') {
    await bootstrapAttendanceDevice({
      employeeId,
      location,
      workingArea: precheck?.company_anchor_label || null,
    });
    payload = await requestWithFallback(ATTENDANCE_PRECHECK_PATHS, {
      method: 'POST',
      body: {
        employee_id: Number(employeeId),
        device_id: ensureDeviceId(),
        platform: platformCode(),
        lat: location.lat,
        lng: location.lng,
        accuracy_m: Number(location.accuracy || 999),
        attendance_type: attendanceType,
        client_time: nowApiTime(),
        app_version: 'mobile-web-1.2.0',
      },
    });
    precheck = payload?.data || {};
  }

  const state = persistGeoState(precheck, location);
  return { payload, precheck, state, location };
};
