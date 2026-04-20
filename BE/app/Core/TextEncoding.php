<?php
declare(strict_types=1);

namespace App\Core;

final class TextEncoding
{
    private const LIKELY_MOJIBAKE_RE = '/(Ã|Â|â|Æ|Ð|Ñ|áº|á»|Ä|Å|Æ°|Æ¡|Ä‘|Äƒ|�)/u';
    private const VIETNAMESE_CHAR_RE = '/[À-ỹĐđ]/u';

    /** @var array<int, int> */
    private const CP1252_UNICODE_TO_BYTE = [
        8364 => 0x80,
        8218 => 0x82,
        402 => 0x83,
        8222 => 0x84,
        8230 => 0x85,
        8224 => 0x86,
        8225 => 0x87,
        710 => 0x88,
        8240 => 0x89,
        352 => 0x8a,
        8249 => 0x8b,
        338 => 0x8c,
        381 => 0x8e,
        8216 => 0x91,
        8217 => 0x92,
        8220 => 0x93,
        8221 => 0x94,
        8226 => 0x95,
        8211 => 0x96,
        8212 => 0x97,
        732 => 0x98,
        8482 => 0x99,
        353 => 0x9a,
        8250 => 0x9b,
        339 => 0x9c,
        382 => 0x9e,
        376 => 0x9f,
    ];

    private function __construct()
    {
    }

    public static function fixMojibake(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Skip strings that look like JSON arrays or objects to prevent corruption
        $trimmed = trim($value);
        if (($trimmed !== '' && $trimmed[0] === '[') || ($trimmed !== '' && $trimmed[0] === '{')) {
            return $value;
        }

        $text = $value;

        // If string contains clear Vietnamese markers and NO known mojibake noise, keep it.
        $hasVietnamese = (bool) preg_match(self::VIETNAMESE_CHAR_RE, $text);
        $hasMojibakeNoise = (bool) preg_match(self::LIKELY_MOJIBAKE_RE, $text);

        if ($hasVietnamese && !$hasMojibakeNoise) {
            return $text;
        }

        if (!$hasMojibakeNoise) {
            return $text;
        }

        $candidate = $text;

        for ($attempt = 0; $attempt < 3; $attempt += 1) {
            $bytes = self::cp1252BytesFromString($candidate);
            if ($bytes === null || $bytes === '') {
                break;
            }

            if (!mb_check_encoding($bytes, 'UTF-8')) {
                break;
            }

            $repaired = $bytes;
            if ($repaired === '' || $repaired === $candidate) {
                break;
            }

            if (str_contains($repaired, '�') && !str_contains($candidate, '�')) {
                break;
            }

            $candidate = $repaired;

            if (preg_match(self::LIKELY_MOJIBAKE_RE, $candidate) !== 1) {
                break;
            }
        }

        $originalScore = preg_match_all(self::VIETNAMESE_CHAR_RE, $text);
        $repairedScore = preg_match_all(self::VIETNAMESE_CHAR_RE, $candidate);
        $originalNoise = preg_match_all(self::LIKELY_MOJIBAKE_RE, $text);
        $repairedNoise = preg_match_all(self::LIKELY_MOJIBAKE_RE, $candidate);

        // Accept repaired text when mojibake markers are reduced, even if raw Vietnamese-char count drops.
        if ($repairedNoise < $originalNoise) {
            return $candidate;
        }

        if ($repairedScore < $originalScore && !str_contains($candidate, 'Đ') && !str_contains($candidate, 'đ')) {
            return $text;
        }

        return $candidate;
    }

    public static function deepFixMojibake(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = self::deepFixMojibake($item);
            }
            return $out;
        }

        if (is_object($value)) {
            $out = clone $value;
            foreach ($out as $key => $item) {
                $out->{$key} = self::deepFixMojibake($item);
            }
            return $out;
        }

        if (is_string($value)) {
            return self::fixMojibake($value);
        }

        return $value;
    }

    private static function cp1252BytesFromString(string $value): ?string
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) {
            return null;
        }

        $bytes = '';
        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');
            if ($code <= 0xff) {
                $bytes .= chr($code);
                continue;
            }

            $mapped = self::CP1252_UNICODE_TO_BYTE[$code] ?? null;
            if ($mapped === null) {
                return null;
            }
            $bytes .= chr($mapped);
        }

        return $bytes;
    }
}
