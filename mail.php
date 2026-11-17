<?php
/**
 * METBUD — obsługa formularza kontaktowego (index.html → fetch('mail.php')).
 * Zwraca JSON: {"ok":true} albo {"ok":false,"error":"..."}.
 *
 * KONFIGURACJA (uzupełnij w menedżerze plików Cyberfolks):
 *  1. Załóż w panelu Cyberfolks skrzynkę np. formularz@metbud.net (tylko do wysyłki).
 *  2. Wpisz jej hasło w SMTP_PASS poniżej.
 *  3. W DNS domeny metbud.net dopisz serwer Cyberfolks do rekordu SPF
 *     (np. "v=spf1 include:spf.protection.outlook.com include:_spf.cyberfolks.pl -all"
 *      — dokładną wartość include potwierdź w pomocy Cyberfolks).
 * Jeśli SMTP_PASS jest pusty, skrypt próbuje wysłać przez PHP mail() (może trafiać do spamu).
 */

const MAIL_TO      = 'biuro@metbud.net';          // dokąd trafiają zapytania
const MAIL_FROM    = 'formularz@metbud.net';      // skrzynka nadawcy na hostingu
const MAIL_NAME    = 'Formularz metbud.net';
const SMTP_HOST    = 'mail.metbud.net';           // serwer SMTP Cyberfolks (sprawdź w panelu: Poczta → konfiguracja)
const SMTP_PORT    = 465;                         // 465 = SSL
const SMTP_USER    = 'formularz@metbud.net';
const SMTP_PASS    = '';                          // ← wpisz hasło skrzynki formularz@metbud.net

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out($ok, $err = '') { echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $err]); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); out(false, 'method'); }

/* proste zabezpieczenia: pole-pułapka i limit długości */
if (!empty($_POST['website'])) out(true);                       // bot wypełnił ukryte pole
$f = function ($k, $max = 300) { $v = trim((string)($_POST[$k] ?? '')); $v = str_replace(["\r", "\0"], '', $v); return mb_substr($v, 0, $max); };
$name    = $f('name', 120);
$company = $f('company', 160);
$email   = $f('email', 160);
$phone   = $f('phone', 40);
$subject = $f('subject', 120);
$message = trim(mb_substr(str_replace("\0", '', (string)($_POST['message'] ?? '')), 0, 5000));
$rodo    = !empty($_POST['rodo']);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); out(false, 'validation'); }
foreach ([$name, $company, $email, $phone, $subject] as $v) if (preg_match('/[\n\r]/', $v)) { http_response_code(422); out(false, 'validation'); }

/* limit: 1 wiadomość na 30 s z jednego IP */
$ip = $_SERVER['REMOTE_ADDR'] ?? '0';
$lock = sys_get_temp_dir() . '/metbud_form_' . md5($ip);
if (is_file($lock) && time() - filemtime($lock) < 30) { http_response_code(429); out(false, 'rate'); }
@touch($lock);

$subj = 'Zapytanie ze strony: ' . ($subject !== '' ? $subject : 'ogólne') . ' — ' . ($company !== '' ? $company : $name);
$body = "Nowe zapytanie z formularza metbud.net\n\n"
      . "Imię i nazwisko: $name\n"
      . "Firma:           $company\n"
      . "E-mail:          $email\n"
      . "Telefon:         $phone\n"
      . "Obszar:          $subject\n"
      . "Zgoda RODO:      " . ($rodo ? 'tak' : 'nie') . "\n\n"
      . "Wiadomość:\n$message\n\n"
      . "—\nIP: $ip · " . date('Y-m-d H:i:s');

$encSubj = '=?UTF-8?B?' . base64_encode($subj) . '?=';
$encName = '=?UTF-8?B?' . base64_encode(MAIL_NAME) . '?=';
$headers = [
  'From: ' . $encName . ' <' . MAIL_FROM . '>',
  'Reply-To: ' . $email,
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
  'X-Mailer: metbud.net',
];

/* ── wysyłka przez SMTP (zalecane) ───────────────────────── */
function smtp_send($to, $encSubj, $body, $headers) {
  $s = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $en, $es, 15);
  if (!$s) return false;
  stream_set_timeout($s, 15);
  $read = function () use ($s) { $r = ''; while (($l = fgets($s, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
  $cmd  = function ($c, $code) use ($s, $read) { if ($c !== null) fwrite($s, $c . "\r\n"); $r = $read(); return strpos($r, (string)$code) === 0; };
  $host = $_SERVER['SERVER_NAME'] ?? 'metbud.net';
  $ok = $cmd(null, 220) && $cmd('EHLO ' . $host, 250) && $cmd('AUTH LOGIN', 334)
     && $cmd(base64_encode(SMTP_USER), 334) && $cmd(base64_encode(SMTP_PASS), 235)
     && $cmd('MAIL FROM:<' . MAIL_FROM . '>', 250) && $cmd('RCPT TO:<' . $to . '>', 250) && $cmd('DATA', 354);
  if ($ok) {
    $msg = implode("\r\n", array_merge($headers, ['To: <' . $to . '>', 'Subject: ' . $encSubj, 'Date: ' . date('r')])) . "\r\n\r\n"
         . preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
    $ok = $cmd($msg . "\r\n.", 250);
  }
  @fwrite($s, "QUIT\r\n"); fclose($s);
  return $ok;
}

$sent = false;
if (SMTP_PASS !== '') $sent = smtp_send(MAIL_TO, $encSubj, $body, $headers);
if (!$sent) $sent = @mail(MAIL_TO, $encSubj, $body, implode("\r\n", $headers), '-f' . MAIL_FROM);

if ($sent) out(true);
http_response_code(500);
out(false, 'send');
