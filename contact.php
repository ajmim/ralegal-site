<?php
/**
 * RALegal — contact form handler (self-hosted, SMTP via PHPMailer).
 * Validates input, then sends an email to the firm via SMTP.
 * Data stays under RALegal's control; no third-party form service.
 */

// ---- Load PHPMailer classes ----
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

// ---- Simple anti-spam honeypot ----
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]); // silently drop
    exit;
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
$body .= "E-mail   : {$email}\n";
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
    $mail->addReplyTo($email, $name); // firm can reply to the visitor
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
