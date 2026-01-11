<?php
session_start();

if (empty($_SESSION['newsletter_csrf']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['newsletter_csrf'], $_POST['csrf_token'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$_SESSION['newsletter_closed'] = true;

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true
]);
