<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';
require_once 'lib/EmailService.php';

session_start();
require_login();

$user_id = get_user_id();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$preferences = $stmt->fetch();

$theme = $preferences['theme'] ?? 'light';
$font_size = $preferences['font_size'] ?? 14;
$default_note_color = $preferences['default_note_color'] ?? 'default';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_preferences') {
            $new_theme = sanitize_input($_POST['theme'] ?? 'light');
            $new_font_size = intval($_POST['font_size'] ?? 14);
            $new_color = sanitize_input($_POST['default_color'] ?? 'default');
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE user_preferences 
                    SET theme = ?, font_size = ?, default_note_color = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$new_theme, $new_font_size, $new_color, $user_id]);
                
                $message = 'Cài đặt đã được cập nhật thành công';
                $theme = $new_theme;
                $font_size = $new_font_size;
                $default_note_color = $new_color;
            } catch (Exception $e) {
                $error = 'Lỗi khi cập nhật cài đặt';
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $new_password_confirm = $_POST['new_password_confirm'] ?? '';
            
            if (!verify_password($current_password, $user['password_hash'])) {
                $error = 'Mật khẩu hiện tại không chính xác';
            } elseif ($new_password !== $new_password_confirm) {
                $error = 'Mật khẩu mới không khớp';
            } else {
                $password_errors = validate_password($new_password);
                if (!empty($password_errors)) {
                    $error = implode(', ', $password_errors);
                } else {
                    try {
                        $new_hash = hash_password($new_password);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$new_hash, $user_id]);
                        $message = 'Mật khẩu đã được thay đổi thành công';
                    } catch (Exception $e) {
                        $error = 'Lỗi khi thay đổi mật khẩu';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt - Notes App</title>
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
            --text: #1e293b;
            --text-light: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light);
            color: var(--text);
        }

        body.dark-theme {
            --light: #0f172a;
            --dark: #e2e8f0;
            --text: #e2e8f0;
            --text-light: #94a3b8;
            --border: #334155;
            background: #0f172a;
        }

        header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 15px 0;
        }

        body.dark-theme header {
            background: #1e293b;
        }

        .header-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--text-light);
            margin-bottom: 40px;
        }

        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }

        .settings-sidebar {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .settings-nav-item {
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            background: white;
            border: 1px solid var(--border);
            color: var(--text);
            font-weight: 600;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        body.dark-theme .settings-nav-item {
            background: #1e293b;
        }

        .settings-nav-item.active {
            background: var(--primary);
            color: white;
            border-left-color: var(--primary);
            border-color: var(--primary);
        }

        .settings-nav-item:hover {
            border-color: var(--primary);
        }

        .settings-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
        }

        body.dark-theme .settings-content {
            background: #1e293b;
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--light);
            color: var(--text);
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group input:disabled {
            background: var(--border);
            cursor: not-allowed;
        }

        .form-group .help-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 6px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .color-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .color-option {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .color-option:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .color-option.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .user-info {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        body.dark-theme .user-info {
            background: #0f172a;
        }

        .user-info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .user-info-item:last-child {
            border-bottom: none;
        }

        .user-info-label {
            font-weight: 600;
            color: var(--text-light);
        }

        .user-info-value {
            color: var(--text);
        }

        .verification-status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #dcfce7;
            border-left: 4px solid var(--success);
            color: #166534;
        }

        .verification-status.unverified {
            background: #fef3c7;
            border-left-color: var(--warning);
            color: #92400e;
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 10px;
            }

            .settings-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body class="<?php echo $theme === 'dark' ? 'dark-theme' : ''; ?>">
    <header>
        <div class="header-content">
            <a href="dashboard.php" class="logo">📝 Notes</a>
            <div>
                <a href="dashboard.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Quay lại</a>
            </div>
        </div>
    </header>

    <main>
        <h1 class="page-title">⚙️ Cài Đặt</h1>
        <p class="page-subtitle">Quản lý tài khoản và tùy chọn cá nhân của bạn</p>

        <?php if ($message): ?>
            <div class="alert alert-success show"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error show"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Sidebar -->
            <div class="settings-sidebar">
                <button class="settings-nav-item active" onclick="showSection('profile')">👤 Hồ Sơ</button>
                <button class="settings-nav-item" onclick="showSection('preferences')">🎨 Tùy Chọn</button>
                <button class="settings-nav-item" onclick="showSection('security')">🔐 Bảo Mật</button>
            </div>

            <!-- Content -->
            <div class="settings-content">
                <!-- Profile Section -->
                <div id="profile" class="settings-section active">
                    <h2 class="section-title">Hồ Sơ Người Dùng</h2>

                    <div class="user-info">
                        <div class="user-info-item">
                            <span class="user-info-label">Tên:</span>
                            <span class="user-info-value"><?php echo e($user['name']); ?></span>
                        </div>
                        <div class="user-info-item">
                            <span class="user-info-label">Email:</span>
                            <span class="user-info-value"><?php echo e($user['email']); ?></span>
                        </div>
                        <div class="user-info-item">
                            <span class="user-info-label">Tham gia:</span>
                            <span class="user-info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>

                    <?php if ($user['is_verified']): ?>
                        <div class="verification-status">
                            ✓ Email của bạn đã được xác thực
                        </div>
                    <?php else: ?>
                        <div class="verification-status unverified">
                            ⚠️ Email của bạn chưa được xác thực. <a href="#" onclick="resendVerificationEmail(event)" style="color: inherit; font-weight: 600; cursor: pointer;">Gửi lại liên kết xác thực</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Preferences Section -->
                <div id="preferences" class="settings-section">
                    <h2 class="section-title">Tùy Chọn Giao Diện</h2>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_preferences">

                        <div class="form-group">
                            <label for="theme">Chủ Đề</label>
                            <select name="theme" id="theme" onchange="updateThemePreview(this.value)">
                                <option value="light" <?php echo $theme === 'light' ? 'selected' : ''; ?>>☀️ Sáng</option>
                                <option value="dark" <?php echo $theme === 'dark' ? 'selected' : ''; ?>>🌙 Tối</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="font_size">Cỡ Chữ: <span id="fontSizeValue"><?php echo $font_size; ?>px</span></label>
                            <input 
                                type="range" 
                                id="font_size" 
                                name="font_size" 
                                min="10" 
                                max="20" 
                                value="<?php echo $font_size; ?>"
                                onchange="document.getElementById('fontSizeValue').textContent = this.value + 'px'"
                            >
                            <div class="help-text">Khoảng: 10px - 20px</div>
                        </div>

                        <div class="form-group">
                            <label>Màu Ghi Chú Mặc Định</label>
                            <div class="color-options">
                                <div class="color-option <?php echo $default_note_color === 'default' ? 'selected' : ''; ?>" 
                                     style="background: #e2e8f0;" 
                                     onclick="selectColor(this, 'default')">
                                </div>
                                <div class="color-option <?php echo $default_note_color === 'yellow' ? 'selected' : ''; ?>" 
                                     style="background: #fef08a;" 
                                     onclick="selectColor(this, 'yellow')">
                                </div>
                                <div class="color-option <?php echo $default_note_color === 'blue' ? 'selected' : ''; ?>" 
                                     style="background: #bfdbfe;" 
                                     onclick="selectColor(this, 'blue')">
                                </div>
                                <div class="color-option <?php echo $default_note_color === 'pink' ? 'selected' : ''; ?>" 
                                     style="background: #fbcfe8;" 
                                     onclick="selectColor(this, 'pink')">
                                </div>
                                <div class="color-option <?php echo $default_note_color === 'green' ? 'selected' : ''; ?>" 
                                     style="background: #dcfce7;" 
                                     onclick="selectColor(this, 'green')">
                                </div>
                                <div class="color-option <?php echo $default_note_color === 'purple' ? 'selected' : ''; ?>" 
                                     style="background: #e9d5ff;" 
                                     onclick="selectColor(this, 'purple')">
                                </div>
                            </div>
                            <input type="hidden" name="default_color" id="selectedColor" value="<?php echo $default_note_color; ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                    </form>
                </div>

                <!-- Security Section -->
                <div id="security" class="settings-section">
                    <h2 class="section-title">Bảo Mật</h2>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Mật Khẩu Hiện Tại</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                placeholder="Nhập mật khẩu hiện tại" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="new_password">Mật Khẩu Mới</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                placeholder="Nhập mật khẩu mới" 
                                required
                            >
                            <div class="help-text">Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt</div>
                        </div>

                        <div class="form-group">
                            <label for="new_password_confirm">Xác Nhận Mật Khẩu Mới</label>
                            <input 
                                type="password" 
                                id="new_password_confirm" 
                                name="new_password_confirm" 
                                placeholder="Nhập lại mật khẩu mới" 
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary">Thay Đổi Mật Khẩu</button>
                    </form>

                    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                    <h3 style="margin-top: 30px; color: var(--danger);">Vùng Nguy Hiểm</h3>
                    <p style="color: var(--text-light); margin-top: 10px;">Những thao tác này không thể được hoàn tác.</p>
                    
                    <button type="button" class="btn btn-danger" onclick="if(confirm('Bạn chắc chắn muốn xóa tài khoản của bạn? Điều này sẽ xóa tất cả dữ liệu của bạn vĩnh viễn.')) { location.href='api/delete-account.php'; }">
                        🗑️ Xóa Tài Khoản
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(el => {
                el.classList.remove('active');
            });
            
            // Remove active class from nav items
            document.querySelectorAll('.settings-nav-item').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Mark nav item as active
            event.target.classList.add('active');
        }

        function selectColor(element, color) {
            document.querySelectorAll('.color-option').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('selectedColor').value = color;
        }

        function updateThemePreview(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }
        }

        function resendVerificationEmail(event) {
            event.preventDefault();
            
            if (confirm('Gửi lại email xác thực đến địa chỉ của bạn?')) {
                fetch('api/resend-verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Email xác thực đã được gửi. Vui lòng kiểm tra hộp thư của bạn.');
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể gửi email'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
                });
            }
        }
    </script>
</body>
</html>
