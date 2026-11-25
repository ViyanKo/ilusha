<?php
require_once __DIR__ . "/../error/ApiError.php";

class EmailService
{
    private const FALLBACK_HOST = "opt3-kusminova.xn--80ahdri7a.site";

    public function sendVerificationEmail(array $user, string $token): bool
    {
        try {
            $email = trim((string) ($user['email'] ?? ''));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log('mail verify: invalid email');
                return false;
            }

            $host = $this->getHost();
            $link = $this->getBaseUrl($host) . "/verify?token=" . urlencode($token);
            $firstName = trim((string) ($user['first_name'] ?? ''));
            $greeting = $firstName !== '' ? $firstName : 'друг';

            $safeGreeting = htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

            $fromName = mb_encode_mimeheader('Кондитерская', 'UTF-8');
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $fromName . ' <no-reply@' . $host . '>',
                'Reply-To: no-reply@' . $host,
            ];

            $subject = mb_encode_mimeheader('Подтверждение регистрации — Кондитерская', 'UTF-8');
            $body = $this->buildBody($safeGreeting, $safeLink);

            $sent = mail($email, $subject, $body, implode("\r\n", $headers));

            if (!$sent) {
                error_log('mail verify: send failed for ' . $email);
            }

            return (bool) $sent;
        } catch (Throwable $e) {
            error_log('mail verify: ' . $e->getMessage());
            return false;
        }
    }

    private function buildBody(string $greeting, string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение регистрации</title>
</head>
<body style="margin:0;padding:0;background:#f7f2ec;font-family:Arial,Helvetica,sans-serif;color:#3b2a20;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f2ec;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;padding:32px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 16px;font-size:24px;color:#3b2a20;">Здравствуйте, {$greeting}!</h1>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Спасибо за регистрацию в кондитерской. Чтобы подтвердить адрес электронной почты,
                                перейдите по кнопке ниже.
                            </p>
                            <p style="margin:24px 0;text-align:center;">
                                <a href="{$link}" style="display:inline-block;padding:14px 28px;background:#c98b5e;color:#ffffff;text-decoration:none;border-radius:10px;font-size:16px;font-weight:bold;">
                                    Подтвердить email
                                </a>
                            </p>
                            <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#6b5a4e;">
                                Если кнопка не открывается, скопируйте ссылку в браузер:
                            </p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;word-break:break-all;">
                                <a href="{$link}" style="color:#c98b5e;">{$link}</a>
                            </p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#6b5a4e;">
                                Ссылка действует 24 часа. Если вы не регистрировались, просто проигнорируйте это письмо.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function getHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

        if ($host === '') {
            return self::FALLBACK_HOST;
        }

        $host = preg_replace('/:\d+$/', '', $host) ?? '';

        if ($host === '' || !preg_match('/^[A-Za-z0-9.\-]+$/', $host)) {
            return self::FALLBACK_HOST;
        }

        return $host;
    }

    private function getBaseUrl(string $host): string
    {
        $port = $_SERVER['SERVER_PORT'] ?? null;
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string) $port === '443';
        $scheme = $isHttps ? 'https' : 'http';

        return $scheme . '://' . $host;
    }
}
