<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

/* ── sanitize ── */
function clean(string $v): string {
    return trim(strip_tags($v));
}

$name    = clean($_POST['name']    ?? '');
$company = clean($_POST['company'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = clean($_POST['phone']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Brakujące pola']);
    exit;
}

/* ── compose ── */
$to      = 'biuro@metbud.net';
$subj    = '=?UTF-8?B?' . base64_encode('Zapytanie ofertowe: ' . ($subject ?: 'ogólne')) . '?=';

$body  = "Nowe zapytanie ofertowe ze strony metbud.net\n";
$body .= str_repeat('─', 48) . "\n\n";
$body .= "Imię i nazwisko : $name\n";
$body .= "Firma           : " . ($company ?: '—') . "\n";
$body .= "E-mail          : $email\n";
$body .= "Telefon         : " . ($phone ?: '—') . "\n";
$body .= "Dotyczy         : " . ($subject ?: '—') . "\n\n";
$body .= "Wiadomość:\n$message\n";

$headers  = "From: biuro@metbud.net\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: MetbudForm/1.0\r\n";

if (mail($to, $subj, $body, $headers)) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Błąd wysyłki — spróbuj mailowo']);
}
