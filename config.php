<?php
// DATABASE CONFIGURATION 
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'notes_app');
define('DB_PORT', 3306);

// APPLICATION SETTINGS 
define('APP_NAME', 'Notes App');
define('APP_URL', 'http://localhost/notes-app');
define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');

// SECURITY SETTINGS
define('SESSION_TIMEOUT', 86400); 
define('BCRYPT_COST', 12);
define('TOKEN_EXPIRY', 3600); 
define('OTP_EXPIRY', 600); 

// EMAIL CONFIGURATION 
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com'); 
define('SMTP_PASS', 'your-app-password');     
define('SMTP_FROM', 'noreply@notesapp.local');
define('SMTP_FROM_NAME', 'Notes App');

// FILE UPLOAD SETTINGS 
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', __DIR__ . '/uploads');

//  PAGINATION 
define('ITEMS_PER_PAGE', 12);

// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Create uploads directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

//  DATABASE CONNECTION 
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('<h1>Database Connection Error</h1><p>Unable to connect to the database. Please check your configuration.</p><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// HELPER FUNCTION

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function generate_otp() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất một chữ cái in hoa';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất một chữ cái thường';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất một số';
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt (!@#$%^&*)';
    }
    
    return $errors;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get base URL for links
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}

function json_response($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function is_authenticated() {
    return isset($_SESSION['user_id']);
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_authenticated()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function log_activity($user_id, $action, $resource, $resource_id = null, $details = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, resource, resource_id, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $resource,
            $resource_id,
            $details ? json_encode($details) : null
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

?>
