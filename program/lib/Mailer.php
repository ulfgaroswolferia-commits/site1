<?php
/**
 * Wrapper na PHPMailer. Transport i nadawca z konfiguracji (MAIL_* w data.php).
 *
 * Wymaga wcześniejszego załadowania class.phpmailer.php (i class.smtp.php dla SMTP) —
 * odkomentuj odpowiednie linie w program/config/includes.php.
 *
 * Nie rzuca wyjątków na zewnątrz: błąd wysyłki jest logowany i zwracany jako false,
 * żeby niedostępny serwer poczty nie wywalał całego żądania.
 *
 * Użycie:
 *   $html = $this->render('mail/_welcome', ['name' => $name]);
 *   Mailer::send($html, 'klient@example.com', 'Witamy', ['kopia@example.com']);
 */
class Mailer
{
    /**
     * @param string $html    treść HTML wiadomości
     * @param string $to      adres odbiorcy
     * @param string $subject temat
     * @param array  $bcc     dodatkowe adresy w ukrytej kopii
     */
    public static function send(string $html, string $to, string $subject, array $bcc = []): bool
    {
        try {
            $mail = new \PHPMailer(true);
            $mail->CharSet = 'utf-8';

            if (defined('MAIL_TRANSPORT') && MAIL_TRANSPORT === 'smtp') {
                $mail->IsSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                if (SMTP_USER !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                }
                if (SMTP_SECURE !== '') {
                    $mail->SMTPSecure = SMTP_SECURE;
                }
            }

            $from     = defined('MAIL_FROM') ? MAIL_FROM : '';
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '';

            $mail->SetFrom($from, $fromName);
            $mail->AddReplyTo($from, $fromName);

            if ($to !== '') {
                $mail->AddAddress($to);
            }
            foreach ($bcc as $addr) {
                if ($addr !== '') {
                    $mail->AddBCC($addr);
                }
            }

            $mail->Subject = $subject;
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer.';
            $mail->MsgHTML($html);

            return (bool) $mail->Send();
        } catch (\Throwable $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
