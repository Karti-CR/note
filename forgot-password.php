<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $email_value = $email;
    
    $auth = new AuthManager($pdo);
    $result = $auth->requestPasswordReset($email);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - Notes App</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --success: #16a34a;
            --danger: #dc2626;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            padding: 20px;
        }

        .forgot-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .forgot-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .forgot-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .forgot-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .forgot-body {
            padding: 40px 30px;
        }

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            margin-bottom: 15px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(37, 99, 235, 0.3);
        }

        .back-link {
            text-align: center;
            color: var(--secondary);
            font-size: 14px;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid var(--success);
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>🔑 Quên Mật Khẩu</h1>
            <p>Đặt lại mật khẩu của bạn</p>
        </div>
        
        <div class="forgot-body">
            <?php if ($error): ?>
                <div class="alert alert-error show"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success show">
                    ✓ <?php echo e($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                📧 Nhập email của bạn. Chúng tôi sẽ gửi liên kết và mã OTP để đặt lại mật khẩu.
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your@email.com" 
                        value="<?php echo e($email_value); ?>"
                        required
                    >
                </div>
                
                <button type="submit" class="btn">Gửi Liên Kết Đặt Lại</button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Quay lại Đăng Nhập</a>
            </div>
        </div>
    </div>
</body>
</html>
