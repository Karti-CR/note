<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$token = sanitize_input($_GET['token'] ?? '');
$error = '';
$success = '';
$step = 'otp'; // otp or reset_password

if (!$token) {
    $error = 'Token không hợp lệ';
    $step = 'error';
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $otp = sanitize_input($_POST['otp'] ?? '');
    
    $auth = new AuthManager($pdo);
    $result = $auth->verifyOTP($token, $otp);
    
    if ($result['success']) {
        $step = 'reset_password';
        $_SESSION['reset_token'] = $token;
    } else {
        $error = $result['message'];
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    $auth = new AuthManager($pdo);
    $result = $auth->resetPassword($token, $new_password, $new_password_confirm);
    
    if ($result['success']) {
        $success = $result['message'];
        $step = 'success';
        unset($_SESSION['reset_token']);
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
    <title>Đặt Lại Mật Khẩu - Notes App</title>
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

        .reset-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .reset-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .reset-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .reset-body {
            padding: 40px 30px;
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

        .otp-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 8px;
            font-weight: bold;
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

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
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

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
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

        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔐 Đặt Lại Mật Khẩu</h1>
        </div>
        
        <div class="reset-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <!-- Step 1: OTP Verification -->
            <?php if ($step === 'otp'): ?>
                <div class="info-box">
                    📧 Nhập mã OTP đã được gửi đến email của bạn. Mã sẽ hết hạn trong 10 phút.
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify_otp">
                    
                    <div class="form-group">
                        <label for="otp">Mã OTP</label>
                        <input 
                            type="text" 
                            id="otp" 
                            name="otp" 
                            class="otp-input"
                            placeholder="000000" 
                            maxlength="6"
                            inputmode="numeric"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn">Xác Thực OTP</button>
                </form>
            <?php endif; ?>
            
            <!-- Step 2: Reset Password -->
            <?php if ($step === 'reset_password'): ?>
                <div class="info-box">
                    ✓ OTP xác thực thành công! Vui lòng nhập mật khẩu mới của bạn.
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="form-group">
                        <label for="new_password">Mật Khẩu Mới</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            placeholder="Nhập mật khẩu mới" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirm">Xác Nhận Mật Khẩu</label>
                        <input 
                            type="password" 
                            id="new_password_confirm" 
                            name="new_password_confirm" 
                            placeholder="Nhập lại mật khẩu" 
                            required
                        >
                    </div>
                    
                    <button type="submit" class="btn">Đặt Lại Mật Khẩu</button>
                </form>
            <?php endif; ?>
            
            <!-- Step 3: Success -->
            <?php if ($step === 'success'): ?>
                <div class="success-icon">✓</div>
                <div class="alert alert-success">
                    Mật khẩu của bạn đã được đặt lại thành công!
                </div>
                <div class="info-box">
                    Bạn có thể đăng nhập lại bằng mật khẩu mới của mình.
                </div>
                <div class="back-link">
                    <a href="login.php">← Đăng Nhập</a>
                </div>
            <?php endif; ?>
            
            <!-- Error -->
            <?php if ($step === 'error'): ?>
                <div class="alert alert-error">
                    Đã xảy ra lỗi. Vui lòng thử lại từ đầu.
                </div>
                <div class="back-link">
                    <a href="forgot-password.php">← Yêu Cầu Đặt Lại Mật Khẩu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-format OTP input
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>
