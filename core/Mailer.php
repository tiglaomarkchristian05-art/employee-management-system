<?php

class Mailer {
    public static function sendSecurityCode($recipient, $code) {
        $recipient = trim((string)$recipient);
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $transport = strtolower((string)env_value('MAIL_TRANSPORT', 'smtp'));
        $subject = 'Great Solomon HRMS sign-in verification code';
        $body = self::securityCodeTemplate($code);

        try {
            if ($transport === 'mail') {
                $from = self::fromEmail();
                return @mail($recipient, $subject, $body, "From: " . self::fromName() . " <{$from}>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8");
            }
            if ($transport !== 'smtp') return false;
            return self::smtp($recipient, $subject, $body);
        } catch (Throwable $exception) {
            error_log('Security email delivery failed because mail configuration is invalid.');
            return false;
        }
    }

    private static function smtp($recipient, $subject, $body) {
        $host = trim((string)env_value('SMTP_HOST', ''));
        $port = (int)env_value('SMTP_PORT', '587');
        $username = (string)env_value('SMTP_USERNAME', '');
        $password = (string)env_value('SMTP_PASSWORD', '');
        $encryption = strtolower((string)env_value('SMTP_ENCRYPTION', 'tls'));
        if ($host === '' || $port < 1 || $username === '' || $password === '') return false;

        $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errorNumber, $errorMessage, 10, STREAM_CLIENT_CONNECT);
        if (!$socket) return false;
        stream_set_timeout($socket, 10);

        try {
            self::expect($socket, [220]);
            $hostname = preg_replace('/[^A-Za-z0-9.-]/', '', (string)($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
            self::command($socket, "EHLO {$hostname}", [250]);
            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS negotiation failed.');
                self::command($socket, "EHLO {$hostname}", [250]);
            }
            self::command($socket, 'AUTH LOGIN', [334]);
            self::command($socket, base64_encode($username), [334]);
            self::command($socket, base64_encode($password), [235]);
            self::command($socket, 'MAIL FROM:<' . self::fromEmail() . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . self::fromName() . ' <' . self::fromEmail() . '>',
                'To: <' . $recipient . '>',
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . preg_replace('/(?m)^\./', '..', $body) . "\r\n.";
            self::command($socket, $message, [250]);
            self::command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $exception) {
            if (is_resource($socket)) fclose($socket);
            error_log('Security email delivery failed without exposing credentials or OTP content.');
            return false;
        }
    }

    private static function command($socket, $command, array $expected) {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expected);
    }

    private static function expect($socket, array $expected) {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expected, true)) throw new RuntimeException('SMTP command rejected.');
    }

    private static function fromEmail() {
        $email = trim((string)env_value('SMTP_FROM_EMAIL', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid sender configuration.');
        return $email;
    }

    private static function fromName() {
        return trim(preg_replace('/[\r\n]+/', '', (string)env_value('SMTP_FROM_NAME', APP_NAME . ' Security')));
    }

    private static function securityCodeTemplate($code) {
        $safeCode = htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8');
        $minutes = (int)MFA_OTP_TTL_MINUTES;
        return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            .'<body style="margin:0;padding:0;background:#f3f1ff;font-family:Arial,Helvetica,sans-serif;color:#172033;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f1ff;padding:32px 12px;"><tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e1ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 30px rgba(75,56,190,.12);">'
            .'<tr><td style="height:8px;background:#5b3fea;font-size:0;line-height:0;">&nbsp;</td></tr>'
            .'<tr><td align="center" style="padding:34px 36px 14px;">'
            .'<div style="display:inline-block;width:58px;height:58px;line-height:58px;border-radius:16px;background:#eeeaff;color:#5b3fea;font-size:30px;font-weight:700;">&#9819;</div>'
            .'<h1 style="margin:18px 0 6px;font-size:24px;line-height:32px;color:#172033;">Sign-in verification</h1>'
            .'<p style="margin:0;color:#667085;font-size:14px;line-height:22px;">Great Solomon Manpower Services Inc.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:14px 36px 8px;">'
            .'<p style="margin:0 0 18px;text-align:center;font-size:15px;line-height:24px;color:#475467;">Use the code below to finish signing in to the Employee Development, Compliance, and Benefits System.</p>'
            .'<div style="margin:0 auto;padding:20px 12px;text-align:center;background:#f6f4ff;border:1px solid #ddd7ff;border-radius:14px;">'
            .'<div style="margin-bottom:8px;color:#6b5bd2;font-size:11px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;">One-time verification code</div>'
            .'<div style="font-family:Courier New,monospace;font-size:36px;line-height:44px;font-weight:700;letter-spacing:8px;color:#4f36df;">'.$safeCode.'</div>'
            .'</div></td></tr>'
            .'<tr><td style="padding:20px 36px 34px;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff8e8;border:1px solid #f5dfa2;border-radius:10px;"><tr><td style="padding:14px 16px;color:#7a5b12;font-size:13px;line-height:20px;">'
            .'<strong>Expires in '.$minutes.' minutes.</strong> This code is single-use. Never share it with anyone, including HR or system administrators.'
            .'</td></tr></table>'
            .'<p style="margin:20px 0 0;text-align:center;color:#7b8190;font-size:12px;line-height:19px;">If you did not attempt to sign in, you may ignore this email and report the activity to your system administrator.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:18px 30px;text-align:center;background:#fafaff;border-top:1px solid #ece9ff;color:#8a90a0;font-size:11px;line-height:18px;">Automated security message &middot; Please do not reply</td></tr>'
            .'</table></td></tr></table></body></html>';
    }
}
