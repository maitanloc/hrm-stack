export const LEGACY_JSON_SERVER_BASE = 'http://localhost:3000';

const LOOPBACK_HOSTS = new Set(['127.0.0.1', 'localhost', '0.0.0.0']);

const isLoopbackApiBase = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return false;
  try {
    const withProtocol = /^https?:\/\//i.test(raw) ? raw : `http://${raw}`;
    const parsed = new URL(withProtocol);
    return LOOPBACK_HOSTS.has(parsed.hostname);
  } catch {
    return false;
  }
};

const isPublicBrowserHost = () => {
  if (typeof window === 'undefined') return false;
  return !LOOPBACK_HOSTS.has(window.location.hostname);
};

const normalizeApiBase = (raw, fallback) => {
  const value = String(raw || '').trim();
  if (!value) return fallback;
  // Production safety: prevent FE on public domain from calling localhost API.
  if (isPublicBrowserHost() && isLoopbackApiBase(value)) return fallback;
  if (value.startsWith('/')) return value.replace(/\/+$/, '');
  if (/^https?:\/\//i.test(value)) return value.replace(/\/+$/, '');
  if (/^[\w.-]+:\d+/.test(value)) return `http://${value}`.replace(/\/+$/, '');
  return fallback;
};

export const BE_API_BASE = normalizeApiBase(import.meta.env.VITE_BE_API_BASE, '/api/v1');
export const HRM_API_BASE = normalizeApiBase(import.meta.env.VITE_HRM_API_BASE, '/hrm-api');

export const AUTH_TOKEN_KEY = 'accessToken';
export const AUTH_USER_KEY = 'authUser';

const readStorageToken = (storage) => {
  try {
    return storage.getItem(AUTH_TOKEN_KEY) || '';
  } catch {
    return '';
  }
};

export const getAccessToken = () => {
  if (typeof window === 'undefined') return '';
  return readStorageToken(window.localStorage) || readStorageToken(window.sessionStorage);
};
