import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js';
import { handleUnauthorized } from '@/services/session.js';
import { deepFixMojibake } from '@/utils/textEncodingFixed.js';

const isNil = (value) => value === undefined || value === null || value === '';
const GET_CACHE_TTL_MS = 5000;
const GET_CACHE_HIDDEN_STALE_MS = 30000;
const GET_CACHE_MAX_ITEMS = 200;
const inFlightGetRequests = new Map();
const getResponseCache = new Map();

const normalizePath = (path) => {
  const raw = String(path || '').trim();
  if (!raw) return `${BE_API_BASE}`;
  if (/^https?:\/\//i.test(raw)) return raw;
  const suffix = raw.startsWith('/') ? raw : `/${raw}`;
  return `${BE_API_BASE}${suffix}`;
};

const buildUrl = (path, query = {}) => {
  const base = normalizePath(path);
  const url = new URL(base, window.location.origin);
  Object.entries(query || {}).forEach(([key, value]) => {
    if (isNil(value)) return;
    url.searchParams.set(key, String(value));
  });
  return url.toString();
};

const parseResponseBody = async (response) => {
  const text = await response.text();
  if (!text) return {};
  try {
    return deepFixMojibake(JSON.parse(text));
  } catch {
    return { message: deepFixMojibake(text) };
  }
};

const createError = (status, payload) => {
  const message = payload?.message || `Request failed (HTTP ${status})`;
  const error = new Error(message);
  error.status = status;
  error.payload = payload;
  return error;
};

const clonePayload = (payload) => {
  if (typeof structuredClone === 'function') {
    return structuredClone(payload);
  }
  return JSON.parse(JSON.stringify(payload));
};

const pruneGetCache = () => {
  if (getResponseCache.size <= GET_CACHE_MAX_ITEMS) return;
  const deleteCount = getResponseCache.size - GET_CACHE_MAX_ITEMS;
  const keys = getResponseCache.keys();
  for (let i = 0; i < deleteCount; i += 1) {
    const key = keys.next().value;
    if (!key) break;
    getResponseCache.delete(key);
  }
};

export const apiRequest = async (path, options = {}) => {
  const method = String(options.method || 'GET').toUpperCase();
  const headers = new Headers(options.headers || {});
  const token = getAccessToken();

  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }
  if (!headers.has('Accept')) {
    headers.set('Accept', 'application/json; charset=utf-8');
  }

  const isFormData = typeof FormData !== 'undefined' && options.body instanceof FormData;
  if (!isFormData && method !== 'GET' && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  const init = {
    method,
    headers,
  };

  if (options.body !== undefined && options.body !== null && method !== 'GET') {
    init.body = isFormData ? options.body : JSON.stringify(options.body);
  }

  const url = buildUrl(path, options.query || {});
  const cacheKey = `${method}:${url}`;
  const bypassGetCache = options.noGetCache === true;
  const now = Date.now();

  if (method === 'GET' && !bypassGetCache) {
    const cached = getResponseCache.get(cacheKey);
    if (cached && cached.expiresAt > now) {
      return clonePayload(cached.payload);
    }
    const isHiddenTab = typeof document !== 'undefined' && document.hidden;
    if (cached && isHiddenTab && cached.hiddenStaleUntil > now) {
      return clonePayload(cached.payload);
    }
    if (inFlightGetRequests.has(cacheKey)) {
      return clonePayload(await inFlightGetRequests.get(cacheKey));
    }
  }

  const execute = async () => {
    const response = await fetch(url, init);
    const payload = await parseResponseBody(response);
    const statusCode = Number(payload?.status || response.status || 0);

    if (statusCode === 401 || response.status === 401) {
      handleUnauthorized();
    }

    if (!response.ok || statusCode >= 400) {
      throw createError(response.status, payload);
    }

    if (method === 'GET' && !bypassGetCache) {
      const createdAt = Date.now();
      getResponseCache.set(cacheKey, {
        payload,
        expiresAt: createdAt + GET_CACHE_TTL_MS,
        hiddenStaleUntil: createdAt + GET_CACHE_HIDDEN_STALE_MS,
      });
      pruneGetCache();
    }

    return payload;
  };

  if (method === 'GET' && !bypassGetCache) {
    const promise = execute();
    inFlightGetRequests.set(cacheKey, promise);
    try {
      return clonePayload(await promise);
    } finally {
      inFlightGetRequests.delete(cacheKey);
    }
  }

  return execute();
};

export const toIsoLocalDate = (value = new Date()) => {
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toISOString().slice(0, 10);
};

export const toApiDateTime = (value) => {
  const raw = String(value || '').trim();
  if (!raw) return '';
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(raw)) return `${raw.replace('T', ' ')}:00`;
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(raw)) return raw.replace('T', ' ');
  return raw;
};
