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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $auth = new AuthManager($pdo);
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
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
    <title>Đăng Nhập - Notes App</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;
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
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .login-body {
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

        .form-group input::placeholder {
            color: var(--secondary);
        }

        .remember-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .remember-group input {
            width: auto;
            margin-right: 8px;
        }

        .remember-group a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .remember-group a:hover {
            text-decoration: underline;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            color: var(--secondary);
            font-size: 14px;
        }

        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
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

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>📝 Notes App</h1>
            <p>Quản lý ghi chú của bạn</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error show"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success show"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your@email.com" 
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu" 
                        required
                    >
                </div>
                
                <div class="remember-group">
                    <label style="margin: 0;">
                        <input type="checkbox" name="remember"> Nhớ tôi
                    </label>
                    <a href="forgot-password.php">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" class="btn btn-primary">Đăng Nhập</button>
            </form>
            
            <div class="signup-link">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-clear alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            if (alert.classList.contains('show')) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>
