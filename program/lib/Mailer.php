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
     * Ostatni zarejestrowany błąd wysyłki.
     * @var string|null
     */
    public static ?string $lastError = null;

    /**
     * @param string $html        treść HTML wiadomości
     * @param string $to          adres odbiorcy
     * @param string $subject     temat
     * @param array  $bcc         dodatkowe adresy w ukrytej kopii
     * @param array  $attachments lista załączników: [['path' => '...', 'name' => '...'], ...] lub tablica ścieżek
     */
    public static function send(string $html, string $to, string $subject, array $bcc = [], array $attachments = []): bool
    {
        self::$lastError = null;
        try {
            $mail = new \PHPMailer(true);
            $mail->CharSet = 'utf-8';
            $mail->Timeout = 3; // Krótki limit czasu na połączenie sieciowe (ochrona przed blokowaniem żądania)

            if (defined('MAIL_TRANSPORT') && MAIL_TRANSPORT === 'smtp') {
                $smtpHost = defined('SMTP_HOST') ? trim((string)SMTP_HOST) : '';
                if ($smtpHost === '') {
                    self::$lastError = 'Host SMTP nie został skonfigurowany.';
                    return false;
                }

                $mail->IsSMTP();
                $mail->Host = $smtpHost;
                $mail->Port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
                $smtpUser   = defined('SMTP_USER') ? SMTP_USER : '';
                $smtpPass   = defined('SMTP_PASS') ? SMTP_PASS : '';
                if ($smtpUser !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUser;
                    $mail->Password = $smtpPass;
                }
                if (defined('SMTP_SECURE') && SMTP_SECURE !== '') {
                    $mail->SMTPSecure = SMTP_SECURE;
                }
            }

            $from     = defined('MAIL_FROM') ? MAIL_FROM : '';
            $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '';

            if ($from !== '') {
                $mail->SetFrom($from, $fromName);
                $mail->AddReplyTo($from, $fromName);
            }

            if ($to !== '') {
                $mail->AddAddress($to);
            }
            foreach ($bcc as $addr) {
                if ($addr !== '') {
                    $mail->AddBCC($addr);
                }
            }

            foreach ($attachments as $att) {
                if (is_array($att) && !empty($att['path']) && file_exists($att['path'])) {
                    $name = !empty($att['name']) ? $att['name'] : basename($att['path']);
                    $mail->AddAttachment($att['path'], $name);
                } elseif (is_string($att) && file_exists($att)) {
                    $mail->AddAttachment($att, basename($att));
                }
            }

            $mail->Subject = $subject;
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer.';
            $mail->MsgHTML($html);

            return (bool) $mail->Send();
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generuje treść HTML wiadomości e-mail z podsumowaniem zamówienia.
     */
    public static function buildOrderEmailHtml(array $orderData, array $items): string
    {
        $orderNumber  = htmlspecialchars($orderData['order_number'] ?? '');
        $supplierName = htmlspecialchars($orderData['supplier_name'] ?? '');
        $createdAt    = htmlspecialchars($orderData['created_at'] ?? date('Y-m-d H:i'));
        $totalAmount  = number_format((float)($orderData['total_amount'] ?? 0), 2, '.', ' ');
        $totalCount   = count($items);

        $rowsHtml = '';
        foreach ($items as $idx => $item) {
            $num       = $idx + 1;
            $name      = htmlspecialchars($item['name'] ?? '');
            $qty       = (float)($item['quantity'] ?? 0);
            $unit      = htmlspecialchars($item['unit'] ?? 'kg');
            $price     = number_format((float)($item['price'] ?? 0), 2, '.', ' ');
            $itemTotal = number_format((float)($item['item_total'] ?? ($qty * (float)($item['price'] ?? 0))), 2, '.', ' ');
            $bg        = ($num % 2 === 0) ? '#f8fafc' : '#ffffff';

            $rowsHtml .= "
                <tr style=\"background: {$bg};\">
                    <td style=\"padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: center; color: #64748b;\">{$num}</td>
                    <td style=\"padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e293b;\">{$name}</td>
                    <td style=\"padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155;\">{$price} zł</td>
                    <td style=\"padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: center; font-weight: 700; color: #2563eb;\">{$qty} {$unit}</td>
                    <td style=\"padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #1e3a8a;\">{$itemTotal} zł</td>
                </tr>";
        }

        $supplierRow = $supplierName !== '' ? "<tr><td style=\"padding: 4px 0; color: #64748b; width: 140px;\">Dostawca / cennik:</td><td style=\"padding: 4px 0; font-weight: 600; color: #1e293b;\">{$supplierName}</td></tr>" : "";

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"utf-8\">
    <title>Zamówienie {$orderNumber}</title>
</head>
<body style=\"margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b;\">
    <div style=\"max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;\">
        
        <!-- Header -->
        <div style=\"background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%); padding: 32px 28px; color: #ffffff;\">
            <h1 style=\"margin: 0 0 8px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;\">Nowe zamówienie warzyw i owoców</h1>
            <p style=\"margin: 0; font-size: 15px; opacity: 0.95;\">Numer zamówienia: <strong>{$orderNumber}</strong></p>
        </div>

        <!-- Info Card -->
        <div style=\"padding: 24px 28px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;\">
            <table style=\"width: 100%; border-collapse: collapse; font-size: 14px;\">
                <tr>
                    <td style=\"padding: 4px 0; color: #64748b; width: 140px;\">Data złożenia:</td>
                    <td style=\"padding: 4px 0; font-weight: 600; color: #1e293b;\">{$createdAt}</td>
                </tr>
                {$supplierRow}
                <tr>
                    <td style=\"padding: 4px 0; color: #64748b;\">Liczba pozycji:</td>
                    <td style=\"padding: 4px 0; font-weight: 600; color: #1e293b;\">{$totalCount}</td>
                </tr>
                <tr>
                    <td style=\"padding: 4px 0; color: #64748b;\">Łączna kwota:</td>
                    <td style=\"padding: 4px 0; font-size: 16px; font-weight: 800; color: #2563eb;\">{$totalAmount} zł</td>
                </tr>
            </table>
        </div>

        <!-- Specyfikacja produktów -->
        <div style=\"padding: 28px;\">
            <h2 style=\"margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #0f172a;\">Specyfikacja zamawianych pozycji:</h2>
            <table style=\"width: 100%; border-collapse: collapse; font-size: 13px;\">
                <thead>
                    <tr style=\"background: #e2e8f0; color: #475569;\">
                        <th style=\"padding: 10px 12px; text-align: center; border-radius: 8px 0 0 0; width: 40px;\">Lp.</th>
                        <th style=\"padding: 10px 12px; text-align: left;\">Towar</th>
                        <th style=\"padding: 10px 12px; text-align: right; width: 85px;\">Cena</th>
                        <th style=\"padding: 10px 12px; text-align: center; width: 90px;\">Ilość</th>
                        <th style=\"padding: 10px 12px; text-align: right; border-radius: 0 8px 0 0; width: 95px;\">Wartość</th>
                    </tr>
                </thead>
                <tbody>
                    {$rowsHtml}
                </tbody>
                <tfoot>
                    <tr style=\"background: #f1f5f9; font-weight: 800;\">
                        <td colspan=\"4\" style=\"padding: 12px; text-align: right; color: #1e293b;\">Razem:</td>
                        <td style=\"padding: 12px; text-align: right; color: #1e3a8a; font-size: 14px;\">{$totalAmount} zł</td>
                    </tr>
                </tfoot>
            </table>

            <div style=\"margin-top: 24px; padding: 14px 18px; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 6px; font-size: 13px; color: #1e40af;\">
                <strong>Załącznik:</strong> Do niniejszej wiadomości dołączono plik arkusza Excel (.xlsx) zawierający pełne zestawienie zamówienia.
            </div>
        </div>

        <!-- Footer -->
        <div style=\"padding: 18px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;\">
            Wiadomość wygenerowana automatycznie przez system <strong>Zamawiarka Magdy</strong>.
        </div>
    </div>
</body>
</html>";
    }
}
