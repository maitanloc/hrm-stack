// Giả lập logic TextEncoding.js từ FE/src/utils/textEncodingFixed.js
const { TextDecoder } = require('util');

const LIKELY_MOJIBAKE_RE = /(Ã|Â|â|Æ|Ð|Ñ|áº|á»|Ä|Å|Æ°|Æ¡|Ä‘|Äƒ|)/;
const VIETNAMESE_CHAR_RE = /[À-ỹĐđ]/g;

const CP1252_MAP = {
  8364: 0x80, 8218: 0x82, 402: 0x83, 8222: 0x84, 8230: 0x85, 8224: 0x86, 8225: 0x87,
  710: 0x88, 8240: 0x89, 352: 0x8a, 8249: 0x8b, 338: 0x8c, 381: 0x8e, 8216: 0x91,
  8217: 0x92, 8220: 0x93, 8221: 0x94, 8226: 0x95, 8211: 0x96, 8212: 0x97, 732: 0x98,
  8482: 0x99, 353: 0x9a, 8250: 0x9b, 339: 0x9c, 382: 0x9e, 376: 0x9f,
};

function fixMojibake(text) {
  if (!text || !LIKELY_MOJIBAKE_RE.test(text)) return text;
  
  try {
    let candidate = text;
    for (let i = 0; i < 2; i++) {
      const bytes = [];
      for (const ch of candidate) {
        const code = ch.codePointAt(0);
        if (code <= 0xff) bytes.push(code);
        else if (CP1252_MAP[code]) bytes.push(CP1252_MAP[code]);
        else return text;
      }
      const repaired = new TextDecoder('utf-8').decode(Uint8Array.from(bytes));
      if (repaired === candidate) break;
      candidate = repaired;
      if (!LIKELY_MOJIBAKE_RE.test(candidate)) break;
    }
    return candidate;
  } catch { return text; }
}

// TEST CASES
const testCases = [
  { name: "Lỗi Mojibake 'ó'", input: "HÃ²a BÃ¬nh", expected: "Hòa Bình" },
  { name: "Lỗi Mojibake 'ệ'", input: "Hệ thá»‘ng", expected: "Hệ thống" },
  { name: "Dữ liệu sạch", input: "Nguyễn Văn A", expected: "Nguyễn Văn A" },
  { name: "Lỗi dấu '?' (Không thể phục hồi nhưng không làm hỏng thêm)", input: "Nguy?n V?n B", expected: "Nguy?n V?n B" }
];

console.log("=== TEST ENCODING REPAIR ===");
testCases.forEach(tc => {
  const result = fixMojibake(tc.input);
  const status = result === tc.expected ? "✅ PASS" : "❌ FAIL";
  console.log(`${status} [${tc.name}]: "${tc.input}" -> "${result}"`);
});
