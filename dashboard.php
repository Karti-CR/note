<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';

session_start();
require_login();

$user_id = get_user_id();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$preferences = $stmt->fetch();

// Get user notes
$stmt = $pdo->prepare("
    SELECT * FROM notes 
    WHERE user_id = ? AND is_deleted = 0
    ORDER BY is_pinned DESC, updated_at DESC
");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

// Get labels
$stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$labels = $stmt->fetchAll();

$theme = $preferences['theme'] ?? 'light';
$view_mode = $preferences['notes_view'] ?? 'grid';
$is_verified = $user['is_verified'] ? true : false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Notes App</title>
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
            line-height: 1.6;
        }

        body.dark-theme {
            --light: #0f172a;
            --dark: #e2e8f0;
            --text: #e2e8f0;
            --text-light: #94a3b8;
            --border: #334155;
            background: #0f172a;
        }

        /* Header */
        header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        body.dark-theme header {
            background: #1e293b;
        }

        .header-content {
            max-width: 1400px;
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
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            background: var(--light);
            color: var(--text);
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }

        .btn-icon:hover {
            background: var(--border);
        }

        .user-menu {
            position: relative;
        }

        .user-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s;
        }

        .user-btn:hover {
            transform: scale(1.05);
        }

        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            display: none;
            margin-top: 10px;
        }

        body.dark-theme .user-menu-dropdown {
            background: #1e293b;
        }

        .user-menu-dropdown.active {
            display: block;
        }

        .user-menu-dropdown a,
        .user-menu-dropdown button {
            display: block;
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            color: var(--text);
            transition: background 0.3s;
            border-bottom: 1px solid var(--border);
            font-family: inherit;
        }

        .user-menu-dropdown a:last-child,
        .user-menu-dropdown button:last-child {
            border-bottom: none;
        }

        .user-menu-dropdown a:hover,
        .user-menu-dropdown button:hover {
            background: var(--light);
        }

        body.dark-theme .user-menu-dropdown a:hover,
        body.dark-theme .user-menu-dropdown button:hover {
            background: #334155;
        }

        .user-menu-dropdown a.danger {
            color: var(--danger);
        }

        /* Verification Banner */
        .verification-banner {
            background: #fef3c7;
            border-bottom: 2px solid var(--warning);
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .verification-banner.hidden {
            display: none;
        }

        .verification-banner-text {
            color: #92400e;
            font-weight: 600;
        }

        .verification-banner button {
            background: var(--warning);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .verification-banner button:hover {
            background: #d97706;
        }

        /* Main */
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            align-items: center;
        }

        body.dark-theme .toolbar {
            background: #1e293b;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--light);
            color: var(--text);
            font-family: inherit;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .toolbar-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
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

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-secondary.active {
            background: var(--primary);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* Notes Grid */
        .notes-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .notes-container.list-view {
            grid-template-columns: 1fr;
        }

        .note-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        body.dark-theme .note-card {
            background: #1e293b;
        }

        .note-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .note-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .note-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            flex: 1;
        }

        .note-icons {
            display: flex;
            gap: 6px;
            font-size: 18px;
        }

        .note-content {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .note-labels {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .label-tag {
            display: inline-block;
            padding: 4px 10px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .note-meta {
            font-size: 12px;
            color: var(--text-light);
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            color: var(--text);
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
            }

            .toolbar-buttons {
                width: 100%;
            }

            .notes-container {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 15px 10px;
            }

            .toolbar {
                padding: 15px;
            }

            .note-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body class="<?php echo $theme === 'dark' ? 'dark-theme' : ''; ?>">
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">📝 Notes</div>
            <div class="header-actions">
                <button class="btn-icon" id="themeToggle" title="Chuyển đổi chủ đề">
                    <?php echo $theme === 'dark' ? '☀️' : '🌙'; ?>
                </button>
                
                <div class="user-menu">
                    <button class="user-btn" id="userMenuBtn">
                        <?php echo strtoupper($user['name'][0]); ?>
                    </button>
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <a href="settings.php">⚙️ Cài Đặt</a>
                        <a href="api/logout.php">🚪 Đăng Xuất</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Verification Banner -->
    <?php if (!$is_verified): ?>
    <div class="verification-banner">
        <span class="verification-banner-text">
            ⚠️ Email của bạn chưa được xác thực. Vui lòng kiểm tra email để xác thực tài khoản.
        </span>
        <button onclick="location.href='settings.php'">Xác Thực Lại</button>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="🔍 Tìm kiếm ghi chú..." 
                >
            </div>
            <div class="toolbar-buttons">
                <button class="btn btn-primary" id="newNoteBtn">+ Ghi Chú Mới</button>
                <button class="btn btn-secondary <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" id="gridViewBtn">⊞ Grid</button>
                <button class="btn btn-secondary <?php echo $view_mode === 'list' ? 'active' : ''; ?>" id="listViewBtn">☰ List</button>
            </div>
        </div>

        <!-- Notes Container -->
        <div class="notes-container <?php echo $view_mode === 'list' ? 'list-view' : ''; ?>" id="notesContainer">
            <?php if (empty($notes)): ?>
                <div style="grid-column: 1/-1;">
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h2>Chưa có ghi chú nào</h2>
                        <p>Tạo ghi chú đầu tiên của bạn để bắt đầu</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card" onclick="location.href='note.php?id=<?php echo $note['id']; ?>'">
                        <div class="note-card-header">
                            <h3 class="note-title"><?php echo e($note['title']); ?></h3>
                            <div class="note-icons">
                                <?php if ($note['is_pinned']): ?>
                                    <span title="Ghi chú được ghim">📌</span>
                                <?php endif; ?>
                                <?php if ($note['is_locked']): ?>
                                    <span title="Ghi chú được bảo vệ">🔒</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="note-content">
                            <?php echo e(substr(strip_tags($note['content']), 0, 150)); ?>...
                        </div>
                        
                        <div class="note-labels">
                            <?php 
                            // Get labels for this note
                            $stmt = $pdo->prepare("
                                SELECT l.* FROM labels l
                                JOIN note_labels nl ON l.id = nl.label_id
                                WHERE nl.note_id = ?
                            ");
                            $stmt->execute([$note['id']]);
                            $note_labels = $stmt->fetchAll();
                            
                            foreach ($note_labels as $label): 
                            ?>
                                <span class="label-tag" style="background-color: <?php echo e($label['color'] ?? '#2563eb'); ?>;">
                                    <?php echo e($label['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="note-meta">
                            📅 <?php echo date('d/m/Y H:i', strtotime($note['updated_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // User Menu Toggle
        document.getElementById('userMenuBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('userMenuDropdown').classList.toggle('active');
        });

        document.addEventListener('click', () => {
            document.getElementById('userMenuDropdown').classList.remove('active');
        });

        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            const isDark = document.body.classList.toggle('dark-theme');
            this.textContent = isDark ? '☀️' : '🌙';
            
            // Save preference via API
            fetch('api/user-settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'update_theme', theme: isDark ? 'dark' : 'light'})
            });
        });

        // View Mode Toggle
        const notesContainer = document.getElementById('notesContainer');
        
        document.getElementById('gridViewBtn').addEventListener('click', function() {
            notesContainer.classList.remove('list-view');
            document.getElementById('listViewBtn').classList.remove('active');
            this.classList.add('active');
            
            fetch('api/user-settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'update_view', view: 'grid'})
            });
        });

        document.getElementById('listViewBtn').addEventListener('click', function() {
            notesContainer.classList.add('list-view');
            document.getElementById('gridViewBtn').classList.remove('active');
            this.classList.add('active');
            
            fetch('api/user-settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'update_view', view: 'list'})
            });
        });

        // New Note Button
        document.getElementById('newNoteBtn').addEventListener('click', () => {
            location.href = 'note.php?action=create';
        });

        // Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            
            searchTimeout = setTimeout(() => {
                if (query.length === 0) {
                    location.reload();
                } else {
                    // Search via API
                    fetch('api/notes.php?action=search&query=' + encodeURIComponent(query))
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Update notes display
                                console.log('Search results:', data.data);
                            }
                        });
                }
            }, 300);
        });
    </script>
</body>
</html>
