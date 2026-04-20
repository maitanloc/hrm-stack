const LIKELY_MOJIBAKE_RE = /(Ã|Â|â|Æ|Ð|Ñ|áº|á»|Ä|Å|Æ°|Æ¡|Ä‘|Äƒ|�)/;
const VIETNAMESE_CHAR_RE = /[À-ỹĐđ]/g;

const CP1252_UNICODE_TO_BYTE = {
  8364: 0x80,
  8218: 0x82,
  402: 0x83,
  8222: 0x84,
  8230: 0x85,
  8224: 0x86,
  8225: 0x87,
  710: 0x88,
  8240: 0x89,
  352: 0x8a,
  8249: 0x8b,
  338: 0x8c,
  381: 0x8e,
  8216: 0x91,
  8217: 0x92,
  8220: 0x93,
  8221: 0x94,
  8226: 0x95,
  8211: 0x96,
  8212: 0x97,
  732: 0x98,
  8482: 0x99,
  353: 0x9a,
  8250: 0x9b,
  339: 0x9c,
  382: 0x9e,
  376: 0x9f,
};

const cp1252BytesFromString = (value) => {
  const bytes = [];
  for (const ch of String(value)) {
    const code = ch.codePointAt(0);
    if (code === undefined) continue;

    if (code <= 0xff) {
      bytes.push(code);
      continue;
    }

    const mapped = CP1252_UNICODE_TO_BYTE[code];
    if (mapped === undefined) {
      return null;
    }
    bytes.push(mapped);
  }
  return Uint8Array.from(bytes);
};

export const fixMojibake = (value) => {
  let text = String(value ?? '');
  if (text === '') return text;

  // 1. Khôi phục các từ ENUM phổ biến bị hỏng ngay tại FE (Dành cho hiển thị)
  const commonFixes = {
    'HI?U L?C': 'HIỆU_LỰC',
    '??NG L?M VI?C': 'ĐANG_LÀM_VIỆC',
    '?? DUY?T': 'ĐÃ_DUYỆT',
    'CH? DUY?T': 'CHỜ_DUYỆT',
    'T? CH?I': 'TỪ_CHỐI'
  };
  if (commonFixes[text]) return commonFixes[text];

  if (!LIKELY_MOJIBAKE_RE.test(text)) {
    return text;
  }

  try {
    let candidate = text;

    for (let attempt = 0; attempt < 3; attempt += 1) {
      const bytes = cp1252BytesFromString(candidate);
      if (!bytes || bytes.length === 0) break;

      const repaired = new TextDecoder('utf-8', { fatal: false }).decode(bytes);
      if (!repaired || repaired === candidate) break;
      if (repaired.includes('�') && !candidate.includes('�')) break;
      candidate = repaired;

      if (!LIKELY_MOJIBAKE_RE.test(candidate)) {
        break;
      }
    }

    const originalScore = (text.match(VIETNAMESE_CHAR_RE) || []).length;
    const repairedScore = (candidate.match(VIETNAMESE_CHAR_RE) || []).length;
    const originalNoise = (text.match(LIKELY_MOJIBAKE_RE) || []).length;
    const repairedNoise = (candidate.match(LIKELY_MOJIBAKE_RE) || []).length;

    // Prefer repaired text when mojibake markers are reduced.
    if (repairedNoise < originalNoise) {
      return candidate;
    }

    if (repairedScore < originalScore && !candidate.includes('Đ') && !candidate.includes('đ')) {
      return text;
    }

    return candidate;
  } catch {
    return text;
  }
};

export const deepFixMojibake = (value) => {
  if (Array.isArray(value)) {
    return value.map((item) => deepFixMojibake(item));
  }

  if (value && typeof value === 'object') {
    const out = {};
    Object.entries(value).forEach(([key, raw]) => {
      out[key] = deepFixMojibake(raw);
    });
    return out;
  }

  if (typeof value === 'string') {
    return fixMojibake(value);
  }

  return value;
};

export const parseJsonTextSafely = (text) => {
  const raw = String(text ?? '');
  if (!raw.trim()) return {};
  try {
    return deepFixMojibake(JSON.parse(raw));
  } catch {
    return { message: fixMojibake(raw) };
  }
};

export const parseJsonResponseSafely = async (response) => {
  const text = await response.text();
  return parseJsonTextSafely(text);
};
