<?php
/**
 * RALegal — contact form handler (self-hosted, SMTP via PHPMailer).
 * Validates input, then sends an email to the firm via SMTP.
 * Data stays under RALegal's control; no third-party form service.
 *
 * HARDENING (2026-08-25): stops bot spam so it never triggers an SMTP email
 * (and thus never hits Infomaniak's RBL blacklist / bounce notifications):
 *   - honeypot hidden field (invisible to humans, filled by bots)
 *   - time-trap: human fill+submit takes >3s; bots submit instantly
 *   - light IP-based rate limit (file cache, degrades gracefully if not writable)
 *   - visitor email is kept ONLY in the body, NOT in Reply-To, so the firm's
 *     reply goes to the visitor but Infomaniak never RBL-checks a random
 *     visitor address.
 */
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---- Load SMTP config (private file) ----
$cfg = file_exists(__DIR__ . '/smtp_config.php')
    ? require __DIR__ . '/smtp_config.php'
    : null;

// ---- Only accept POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// ---- Spam screening: any bot signal -> silently "succeed" without sending ----
function silently_drop(): void {
    echo json_encode(['ok' => true]);
    exit;
}

// 1) Honeypot: hidden field a real human never sees. If filled, it's a bot.
if (!empty($_POST['website'])) {
    silently_drop();
}

// 2) Time-trap: the page records page load time in a hidden field on submit.
//    A human takes >3s to fill the form; bots POST in milliseconds.
$submitted_at = microtime(true);
$rendered_at  = (float)($_POST['form_time'] ?? 0.0);
if ($rendered_at <= 0 || ($submitted_at - $rendered_at) < 3.0) {
    silently_drop();
}

// 3) Rate limit: max N real submissions per IP per window (file-based, graceful).
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_ok = rate_limit_check($ip);
if (!$rate_ok) {
    silently_drop();
}

// ---- Clean fields ----
function clean($k, $max = 2000) {
    $v = trim($_POST[$k] ?? '');
    return mb_substr($v, 0, $max);
}

$name    = clean('name', 200);
$phone   = clean('phone', 40);
$email   = clean('email', 254);
$objet   = clean('objet', 200);
$message = clean('message', 10000);

// ---- Validate ----
$errors = [];
if ($name === '')                       $errors[] = 'name';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if ($message === '')                    $errors[] = 'message';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation', 'fields' => $errors]);
    exit;
}

// ---- Build mail ----
$subject = '[RALegal Contact] ' . ($objet !== '' ? $objet : 'Demande de contact');
$body  = "Nouveau message via le site ralegal.ch\n";
$body .= "----------------------------------------\n";
$body .= "Nom      : {$name}\n";
$body .= "Téléphone: " . ($phone !== '' ? $phone : '(non renseigné)') . "\n";
$body .= "E-mail   : {$email}\n";   // visitor email kept in body for replying
$body .= "Objet    : " . ($objet !== '' ? $objet : '(non renseigné)') . "\n";
$body .= "----------------------------------------\n\n";
$body .= "Message:\n{$message}\n";

// ---- Send via SMTP ----
$mail = new PHPMailer(true);
$ok = false;
$err = 'mail_failed';
try {
    $mail->isSMTP();
    $mail->Host       = $cfg['host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['username'] ?? '';
    $mail->Password   = $cfg['password'] ?? '';
    $mail->SMTPSecure = ($cfg['secure'] ?? 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($cfg['port'] ?? 587);
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($cfg['from_email'] ?? 'noreply@ralegal.ch', $cfg['from_name'] ?? 'RALegal');
    // NOTE: no addReplyTo(visitor). Visitor email is only in the body, so
    // Infomaniak does not RBL-check it. The firm replies by using that address.
    $mail->addAddress($cfg['to_email'] ?? 'rajmi@ralegal.ch', $cfg['to_name'] ?? '');

    $mail->Subject = $subject;
    $mail->Body    = $body;

    if ($mail->send()) {
        $ok = true;
    }
} catch (Exception $e) {
    $ok = false;
    $err = 'mail_failed';
}

if ($ok) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $err]);
}

/**
 * Lightweight per-IP rate limiter.
 * Allows up to $max submissions per $window seconds. Uses a small JSON cache
 * dir. If the directory cannot be created/written (read-only web root), it
 * degrades to allowing the request (never blocks real users).
 */
function rate_limit_check(string $ip, int $max = 4, int $window = 300): bool {
    $dir = __DIR__ . '/.contact-cache';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return true; // cannot persist -> don't block
    }
    if (!is_writable($dir)) {
        return true;
    }
    $file = $dir . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $ip) . '.json';
    $now  = time();
    $data = [];
    if (is_file($file)) {
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) $data = [];
        $data = array_filter($data, fn($t) => $now - $t < $window);
    }
    if (count($data) >= $max) {
        return false;
    }
    $data[] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}
