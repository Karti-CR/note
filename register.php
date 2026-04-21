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
$name_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $email_value = $email;
    $name_value = $name;
    
    $auth = new AuthManager($pdo);
    $result = $auth->register($email, $name, $password, $password_confirm);
    
    if ($result['success']) {
        $success = $result['message'];
        header('Refresh: 3; URL=dashboard.php');
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
    <title>Đăng Ký - Notes App</title>
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

        .register-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .register-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .register-body {
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

        .password-requirements {
            font-size: 12px;
            color: var(--secondary);
            margin-top: 8px;
            padding: 10px;
            background: var(--light);
            border-radius: 6px;
            line-height: 1.6;
        }

        .password-requirements li {
            list-style: none;
            padding-left: 20px;
            position: relative;
        }

        .password-requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--success);
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

        .login-link {
            text-align: center;
            color: var(--secondary);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
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
            .register-container {
                margin: 0;
            }

            .register-header {
                padding: 30px 20px;
            }

            .register-header h1 {
                font-size: 24px;
            }

            .register-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>📝 Notes App</h1>
            <p>Tạo tài khoản mới</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-error show"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success show">
                    ✓ <?php echo e($success); ?><br>
                    <small>Chuyển hướng đến dashboard trong 3 giây...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="name">Tên Hiển Thị</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Ví dụ: John Doe" 
                        value="<?php echo e($name_value); ?>"
                        required
                    >
                </div>
                
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
                
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu" 
                        required
                    >
                    <div class="password-requirements">
                        <strong>Yêu cầu mật khẩu:</strong>
                        <ul>
                            <li>Ít nhất 8 ký tự</li>
                            <li>Chứa chữ cái in hoa (A-Z)</li>
                            <li>Chứa chữ cái thường (a-z)</li>
                            <li>Chứa số (0-9)</li>
                            <li>Chứa ký tự đặc biệt (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Xác Nhận Mật Khẩu</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        placeholder="Nhập lại mật khẩu" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">Đăng Ký</button>
            </form>
            
            <div class="login-link">
                Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>
            </div>
        </div>
    </div>

    <script>
        // Password validation feedback
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const form = document.getElementById('registerForm');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                'Ít nhất 8 ký tự': password.length >= 8,
                'Chứa chữ cái in hoa': /[A-Z]/.test(password),
                'Chứa chữ cái thường': /[a-z]/.test(password),
                'Chứa số': /[0-9]/.test(password),
                'Chứa ký tự đặc biệt': /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            console.log('Password requirements:', requirements);
        });

        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                alert('Mật khẩu không khớp!');
                return false;
            }
        });
    </script>
</body>
</html>
