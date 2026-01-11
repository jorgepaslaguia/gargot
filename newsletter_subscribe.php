<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['newsletter_csrf']) || !hash_equals($_SESSION['newsletter_csrf'], $token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Please refresh and try again.'
    ]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter an email address.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'The email address is not valid.'
    ]);
    exit;
}

try {
    $sql = 'INSERT INTO newsletter_subscribers (email, created_at, last_update, is_active, first_source, last_ip)
            VALUES (?, NOW(), NOW(), 1, ?, ?)
            ON DUPLICATE KEY UPDATE
                last_update = NOW(),
                is_active = 1,
                last_ip = VALUES(last_ip)';

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('DB prepare error: ' . $conexion->error);
    }

    $source = 'site_footer';
    $ip     = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt->bind_param('sss', $email, $source, $ip);
    $stmt->execute();
    $stmt->close();

    $_SESSION['newsletter_closed'] = true;

    echo json_encode([
        'success' => true,
        'message' => 'Thanks for subscribing. You\'ll receive our next drops and updates by email.'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'There was a problem saving your subscription. Please try again later.'
    ]);
}
