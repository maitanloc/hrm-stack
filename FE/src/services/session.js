import { AUTH_TOKEN_KEY, AUTH_USER_KEY } from '@/services/runtimeConfig.js';

const STORAGE_KEYS = [
  AUTH_TOKEN_KEY,
  AUTH_USER_KEY,
  'userRole',
  'userId',
  'userName',
  'userEmail',
  'userCode',
  'userPhone',
  'userDeptId',
  'userManagedDeptIds',
  'userDeptName',
  'userPosition',
  'rememberLogin',
];

const hasWindow = typeof window !== 'undefined';

const readStorage = (storage, key) => {
  try {
    return storage.getItem(key);
  } catch {
    return null;
  }
};

const writeStorage = (storage, key, value) => {
  try {
    storage.setItem(key, value);
  } catch {
    // ignore quota/private mode errors
  }
};

const removeStorageKey = (storage, key) => {
  try {
    storage.removeItem(key);
  } catch {
    // ignore
  }
};

export const getSessionItem = (key) => {
  if (!hasWindow) return null;
  const localValue = readStorage(window.localStorage, key);
  if (localValue !== null && localValue !== undefined && localValue !== '') return localValue;
  return readStorage(window.sessionStorage, key);
};

export const getCurrentUserRole = () => (getSessionItem('userRole') || '').trim().toLowerCase();

export const clearAuthSession = () => {
  if (!hasWindow) return;
  STORAGE_KEYS.forEach((key) => {
    removeStorageKey(window.localStorage, key);
    removeStorageKey(window.sessionStorage, key);
  });
};

export const persistAuthSession = ({ remember = false, user = {}, token = '', uiRole = '' }) => {
  if (!hasWindow) return;

  const primary = remember ? window.localStorage : window.sessionStorage;
  const secondary = remember ? window.sessionStorage : window.localStorage;

  // keep only one source of truth to avoid stale-session bugs.
  STORAGE_KEYS.forEach((key) => removeStorageKey(secondary, key));

  const managedDeptIds = Array.isArray(user.managed_department_ids)
    ? user.managed_department_ids
        .map((v) => Number(v))
        .filter((v) => Number.isFinite(v) && v > 0)
    : [];

  const resolvedDeptId = user.department_id ?? user.departmentId ?? managedDeptIds[0] ?? '';

  const payload = {
    userRole: uiRole,
    userId: String(user.employee_id ?? user.employeeId ?? ''),
    userName: String(user.full_name ?? user.fullName ?? ''),
    userEmail: String(user.company_email ?? user.companyEmail ?? ''),
    userCode: String(user.employee_code ?? user.employeeCode ?? ''),
    userPhone: String(user.phone_number ?? user.phoneNumber ?? ''),
    userDeptId: String(resolvedDeptId ?? ''),
    userManagedDeptIds: JSON.stringify(managedDeptIds),
    userDeptName: String(user.department_name ?? user.departmentName ?? ''),
    userPosition: String(user.position_name ?? user.positionName ?? ''),
    rememberLogin: remember ? '1' : '0',
  };

  if (token) payload[AUTH_TOKEN_KEY] = token;
  payload[AUTH_USER_KEY] = JSON.stringify(user || {});

  Object.entries(payload).forEach(([key, value]) => writeStorage(primary, key, value));
};

export const redirectToLogin = () => {
  if (!hasWindow) return;
  if (window.location.pathname !== '/login') {
    window.location.href = '/login';
  }
};

export const handleUnauthorized = () => {
  clearAuthSession();
  redirectToLogin();
};
