<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';

session_start();

$token = sanitize_input($_GET['token'] ?? '');
$message = '';
$is_success = false;

if ($token) {
    $auth = new AuthManager($pdo);
    $result = $auth->verifyEmail($token);
    
    if ($result['success']) {
        $is_success = true;
        $message = $result['message'];
    } else {
        $message = $result['message'];
    }
} else {
    $message = 'Token xác thực không hợp lệ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Thực Email - Notes App</title>
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --danger: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            padding: 40px 30px;
            text-align: center;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid var(--success);
            text-align: left;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
            text-align: left;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(37, 99, 235, 0.3);
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($is_success): ?>
            <div class="icon">✓</div>
            <h1>Xác Thực Thành Công!</h1>
            <p>Email của bạn đã được xác thực thành công. Tài khoản của bạn bây giờ đã hoạt động.</p>
            <div class="message success">
                <?php echo e($message); ?>
            </div>
            <a href="login.php" class="btn">Đăng Nhập</a>
        <?php else: ?>
            <div class="icon">✗</div>
            <h1>Xác Thực Thất Bại</h1>
            <p>Xin lỗi, không thể xác thực email của bạn.</p>
            <div class="message error">
                <?php echo e($message); ?>
            </div>
            <a href="register.php" class="btn">Quay Lại Đăng Ký</a>
        <?php endif; ?>
    </div>
</body>
</html>
