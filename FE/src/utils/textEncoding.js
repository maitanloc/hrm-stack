const LIKELY_MOJIBAKE_RE = /(Ã|Â|â|Æ|Ð|Ñ|áº|á»|Ä|�)/;

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
  const text = String(value ?? '');
  if (text === '' || !LIKELY_MOJIBAKE_RE.test(text)) {
    return text;
  }

  try {
    const bytes = cp1252BytesFromString(text);
    if (!bytes || bytes.length === 0) return text;

    const repaired = new TextDecoder('utf-8', { fatal: false }).decode(bytes);
    if (!repaired || repaired === text) return text;

    // Do not replace with a visibly broken candidate.
    if (repaired.includes('�') && !text.includes('�')) return text;
    return repaired;
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
