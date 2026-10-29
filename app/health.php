<?php
// Health check: reports whether the app can reach its database.
// Used to verify deployments (EC2 and Elastic Beanstalk) without logging in.
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    $result = [
        'status' => 'ok',
        'environment' => APP_ENV,
        'server' => gethostname(),
        'database' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'courses' => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
            'enrollments' => (int) $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
        ],
    ];
} catch (Throwable $ex) {
    http_response_code(503);
    $result = ['status' => 'error', 'environment' => APP_ENV, 'message' => 'Database connection failed'];
}

echo json_encode($result, JSON_PRETTY_PRINT);
