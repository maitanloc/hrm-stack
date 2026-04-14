<?php
declare(strict_types=1);

namespace App\Services;

class MailService
{
    private string $driver;
    private string $fromAddress;
    private string $fromName;
    private string $replyToAddress;
    private string $replyToName;

    public function __construct()
    {
        $this->driver = strtolower(trim((string) env('MAIL_DRIVER', 'log')));
        $this->fromAddress = trim((string) env('MAIL_FROM_ADDRESS', 'no-reply@localhost'));
        $this->fromName = trim((string) env('MAIL_FROM_NAME', (string) env('APP_NAME', 'HRM System')));
        $this->replyToAddress = trim((string) env('MAIL_REPLY_TO_ADDRESS', $this->fromAddress));
        $this->replyToName = trim((string) env('MAIL_REPLY_TO_NAME', $this->fromName));
    }

    public function send(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        $to = trim($to);
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            return match ($this->driver) {
                'smtp' => $this->sendViaSmtp($to, $subject, $textBody, $htmlBody),
                'mail' => $this->sendViaNativeMail($to, $subject, $textBody, $htmlBody),
                default => $this->sendViaLog($to, $subject, $textBody, $htmlBody),
            };
        } catch (\Throwable $exception) {
            error_log('[mail] send failed: ' . $exception->getMessage());
            return false;
        }
    }

    private function sendViaLog(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        $preview = substr(trim($textBody), 0, 400);
        error_log(sprintf(
            '[mail:log] to=%s subject="%s" body="%s" html=%s',
            $to,
            $subject,
            $preview,
            $htmlBody !== null ? 'yes' : 'no'
        ));
        return true;
    }

    private function sendViaNativeMail(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        [$headers, $body] = $this->buildMimeHeadersAndBody($to, $subject, $textBody, $htmlBody);
        $encodedSubject = $this->encodeHeader($subject);
        return @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    }

    private function sendViaSmtp(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        $host = trim((string) env('MAIL_SMTP_HOST', ''));
        if ($host === '') {
            return false;
        }

        $port = (int) env('MAIL_SMTP_PORT', '587');
        $encryption = strtolower(trim((string) env('MAIL_SMTP_ENCRYPTION', 'tls')));
        $username = trim((string) env('MAIL_SMTP_USERNAME', ''));
        $password = (string) env('MAIL_SMTP_PASSWORD', '');
        $timeout = max(5, (int) env('MAIL_SMTP_TIMEOUT', '15'));

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            throw new \RuntimeException('SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectCode($this->readResponse($socket), [220]);

            $hostname = trim((string) env('MAIL_EHLO_DOMAIN', 'localhost'));
            $this->writeCommand($socket, 'EHLO ' . $hostname);
            $this->expectCode($this->readResponse($socket), [250]);

            if ($encryption === 'tls') {
                $this->writeCommand($socket, 'STARTTLS');
                $this->expectCode($this->readResponse($socket), [220]);

                $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new \RuntimeException('SMTP STARTTLS failed');
                }

                $this->writeCommand($socket, 'EHLO ' . $hostname);
                $this->expectCode($this->readResponse($socket), [250]);
            }

            if ($username !== '') {
                $this->writeCommand($socket, 'AUTH LOGIN');
                $this->expectCode($this->readResponse($socket), [334]);
                $this->writeCommand($socket, base64_encode($username));
                $this->expectCode($this->readResponse($socket), [334]);
                $this->writeCommand($socket, base64_encode($password));
                $this->expectCode($this->readResponse($socket), [235]);
            }

            $this->writeCommand($socket, 'MAIL FROM:<' . $this->fromAddress . '>');
            $this->expectCode($this->readResponse($socket), [250]);

            $this->writeCommand($socket, 'RCPT TO:<' . $to . '>');
            $this->expectCode($this->readResponse($socket), [250, 251]);

            $this->writeCommand($socket, 'DATA');
            $this->expectCode($this->readResponse($socket), [354]);

            [$headers, $body] = $this->buildMimeHeadersAndBody($to, $subject, $textBody, $htmlBody);
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = preg_replace('/^\./m', '..', $message) ?? $message;

            fwrite($socket, $message . "\r\n.\r\n");
            $this->expectCode($this->readResponse($socket), [250]);

            $this->writeCommand($socket, 'QUIT');
            $this->readResponse($socket);

            return true;
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return array{0: array<int, string>, 1: string}
     */
    private function buildMimeHeadersAndBody(string $to, string $subject, string $textBody, ?string $htmlBody = null): array
    {
        $headers = [
            'Date: ' . gmdate('D, d M Y H:i:s O'),
            'From: ' . $this->formatAddress($this->fromAddress, $this->fromName),
            'To: ' . $this->formatAddress($to, $to),
            'Reply-To: ' . $this->formatAddress($this->replyToAddress, $this->replyToName),
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'X-Mailer: HRM-MailService',
        ];

        if ($htmlBody !== null && trim($htmlBody) !== '') {
            $boundary = '=_HRM_' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $textBody . "\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                . $htmlBody . "\r\n\r\n"
                . '--' . $boundary . '--';

            return [$headers, $body];
        }

        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        return [$headers, $textBody];
    }

    /**
     * @return array{code:int, message:string}
     */
    private function readResponse($socket): array
    {
        $code = 0;
        $full = '';

        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }

            $full .= $line;
            if (preg_match('/^(\d{3})([ \-])/', $line, $matches) === 1) {
                $code = (int) $matches[1];
                $isLast = $matches[2] === ' ';
                if ($isLast) {
                    break;
                }
            } else {
                break;
            }
        }

        return ['code' => $code, 'message' => trim($full)];
    }

    /**
     * @param array{code:int, message:string} $response
     * @param array<int, int> $expectedCodes
     */
    private function expectCode(array $response, array $expectedCodes): void
    {
        if (!in_array($response['code'], $expectedCodes, true)) {
            throw new \RuntimeException(
                'Unexpected SMTP response [' . $response['code'] . ']: ' . $response['message']
            );
        }
    }

    private function writeCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeName = trim($name);
        if ($safeName === '') {
            return '<' . $email . '>';
        }
        return $this->encodeHeader($safeName) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (preg_match('/^[\x20-\x7E]+$/', $trimmed) === 1) {
            return $trimmed;
        }

        return '=?UTF-8?B?' . base64_encode($trimmed) . '?=';
    }
}

