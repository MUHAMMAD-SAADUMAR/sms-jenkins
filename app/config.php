<?php
// Shared bootstrap: settings, database connection, session and helper functions.
// Every page starts with: require_once __DIR__ . '/config.php';

ob_start();

// Read a setting from the environment. Apache SetEnv (EC2) and Elastic Beanstalk
// environment properties both end up in one of these places.
function env($key, $default = null)
{
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

define('APP_NAME', 'Student Management System');
define('APP_ENV', env('APP_ENV', 'local'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'sms'));
define('DB_USER', env('DB_USER', 'sms_app'));
define('DB_PASS', env('DB_PASS', ''));

date_default_timezone_set('Asia/Karachi');

function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

session_start();

// ---------- Output helpers ----------

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes()
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------- CSRF protection ----------

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf()
{
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid form submission. Please go back and try again.');
    }
}

// ---------- Authentication ----------

function current_user()
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, name, email, role, reg_no, is_active FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if ($user && !$user['is_active']) {
                $user = null;
                session_destroy();
            }
        }
    }
    return $user;
}

function require_login()
{
    if (!current_user()) {
        flash('error', 'Please log in first.');
        redirect('login.php');
    }
    return current_user();
}

function require_admin()
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied: administrators only.');
    }
    return $user;
}

function is_admin()
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

// ---------- Grades ----------

const GRADE_POINTS = [
    'A' => 4.00, 'A-' => 3.66, 'B+' => 3.33, 'B' => 3.00, 'B-' => 2.66,
    'C+' => 2.33, 'C' => 2.00, 'C-' => 1.66, 'D+' => 1.33, 'D' => 1.00, 'F' => 0.00,
];
