<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';

$input = filter_input_array(INPUT_POST, [
    'name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'phone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'service' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'message' => FILTER_UNSAFE_RAW,
    'form' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
]);

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$service = trim($input['service'] ?? '');
$message = trim($input['message'] ?? '');
$formTitle = trim($input['form'] ?? 'Заявка');

if ($name === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Укажите имя и контакт для связи.']);
    exit;
}

$mailBody = sprintf(
    "<p><strong>Форма:</strong> %s</p>\n<p><strong>Имя:</strong> %s</p>\n<p><strong>Контакт:</strong> %s</p>\n<p><strong>Услуга:</strong> %s</p>\n<p><strong>Комментарий:</strong><br>%s</p>",
    htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($service ?: 'Не выбрана', ENT_QUOTES, 'UTF-8'),
    nl2br(htmlspecialchars($message ?: 'Без комментариев', ENT_QUOTES, 'UTF-8'))
);

try {
    $mailer = new PHPMailer(true);
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom('no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'site.local'), 'Brow Studio');
    $mailer->addAddress('vp.163@mail.ru', 'Brow Studio');

    $replyEmail = filter_var($phone, FILTER_VALIDATE_EMAIL);
    if ($replyEmail) {
        $mailer->addReplyTo($replyEmail, $name);
    }

    $mailer->isHTML(true);
    $mailer->Subject = sprintf('Новая заявка: %s', $formTitle);
    $mailer->Body = $mailBody;
    $mailer->AltBody = strip_tags(str_replace('<br>', "\n", $mailBody));

    $mailer->send();

    echo json_encode([
        'success' => true,
        'message' => 'Заявка отправлена. Я свяжусь с вами в течение часа.',
    ]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Не удалось отправить письмо. Попробуйте снова или свяжитесь напрямую.',
        'error' => $exception->getMessage(),
    ]);
}
