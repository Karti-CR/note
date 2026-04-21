<?php
require_once 'config.php';
require_once 'lib/AuthManager.php';

session_start();
require_login();

$user_id = get_user_id();
$note_id = intval($_GET['id'] ?? 0);
$action = sanitize_input($_GET['action'] ?? 'view');

$note = null;
$labels = [];
$error = '';

// Get user preferences
$stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$preferences = $stmt->fetch();
$theme = $preferences['theme'] ?? 'light';

// If editing/viewing existing note
if ($note_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    
    if (!$note) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Get note labels
    $stmt = $pdo->prepare("
        SELECT l.* FROM labels l
        JOIN note_labels nl ON l.id = nl.label_id
        WHERE nl.note_id = ?
    ");
    $stmt->execute([$note_id]);
    $labels = $stmt->fetchAll();
}

// Get all labels for this user
$stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$all_labels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $note ? e($note['title']) : 'Ghi Chú Mới'; ?> - Notes App</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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

        .header-actions {
            display: flex;
            gap: 10px;
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

        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .editor {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body.dark-theme .editor {
            background: #1e293b;
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

        input[type="text"],
        textarea,
        select {
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

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input[type="text"] {
            font-size: 24px;
            font-weight: 700;
            padding: 15px;
        }

        textarea {
            min-height: 400px;
            resize: vertical;
            font-size: <?php echo $note ? ($note['font_size'] ?? 14) : 14; ?>px;
        }

        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
        }

        body.dark-theme .editor-toolbar {
            background: #0f172a;
        }

        .toolbar-group {
            display: flex;
            gap: 8px;
            align-items: center;
            padding-right: 15px;
            border-right: 1px solid var(--border);
        }

        .toolbar-group:last-child {
            border-right: none;
        }

        .toolbar-group label {
            margin: 0;
            font-size: 12px;
            color: var(--text-light);
        }

        .toolbar-group select,
        .toolbar-group input {
            max-width: 100px;
            margin: 0;
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .label-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .label-badge button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        body.dark-theme .status-bar {
            background: #0f172a;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-light);
        }

        .status-dot.saving {
            background: var(--warning);
            animation: pulse 1s infinite;
        }

        .status-dot.saved {
            background: var(--success);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .left-actions,
        .right-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            main {
                padding: 20px 15px;
            }

            .editor {
                padding: 20px;
            }

            input[type="text"] {
                font-size: 18px;
            }

            textarea {
                min-height: 300px;
            }

            .editor-toolbar {
                flex-direction: column;
            }

            .toolbar-group {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border);
                padding-right: 0;
                padding-bottom: 10px;
            }

            .toolbar-group:last-child {
                border-bottom: none;
            }

            .action-buttons {
                flex-direction: column;
            }

            .left-actions,
            .right-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 10px;
            }

            .editor {
                padding: 15px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body class="<?php echo $theme === 'dark' ? 'dark-theme' : ''; ?>">
    <header>
        <div class="header-content">
            <a href="dashboard.php" class="logo">📝 Notes</a>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>
    </header>

    <main>
        <div class="editor">
            <!-- Status Bar -->
            <div class="status-bar">
                <div class="status-indicator">
                    <span class="status-dot saved" id="saveDot"></span>
                    <span id="saveStatus">Đã lưu</span>
                </div>
                <?php if ($note): ?>
                    <span>Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($note['updated_at'])); ?></span>
                <?php endif; ?>
            </div>

            <form id="noteForm" method="POST" action="">
                <!-- Title -->
                <div class="form-group">
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="Tiêu đề ghi chú..." 
                        value="<?php echo $note ? e($note['title']) : ''; ?>"
                        required
                    >
                </div>

                <!-- Toolbar -->
                <div class="editor-toolbar">
                    <div class="toolbar-group">
                        <label>Cỡ chữ:</label>
                        <select id="fontSize" onchange="updateFontSize()">
                            <option value="12">12px</option>
                            <option value="14" selected>14px</option>
                            <option value="16">16px</option>
                            <option value="18">18px</option>
                            <option value="20">20px</option>
                        </select>
                    </div>

                    <div class="toolbar-group">
                        <label>Màu sắc:</label>
                        <select id="noteColor" onchange="updateNoteColor()">
                            <option value="default">Trắng</option>
                            <option value="yellow">Vàng</option>
                            <option value="blue">Xanh</option>
                            <option value="pink">Hồng</option>
                            <option value="green">Xanh lá</option>
                            <option value="purple">Tím</option>
                        </select>
                    </div>
                </div>

                <!-- Labels -->
                <div class="form-group">
                    <label>Thẻ ghi chú</label>
                    <div class="labels-container" id="labelsContainer">
                        <?php foreach ($labels as $label): ?>
                            <div class="label-badge" style="background-color: <?php echo e($label['color'] ?? '#2563eb'); ?>;">
                                <?php echo e($label['name']); ?>
                                <button type="button" onclick="removeLabel(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <select id="labelSelect" onchange="addLabel()">
                        <option value="">+ Thêm thẻ</option>
                        <?php foreach ($all_labels as $label): ?>
                            <option value="<?php echo $label['id']; ?>"><?php echo e($label['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Content -->
                <div class="form-group">
                    <textarea 
                        id="content" 
                        name="content" 
                        placeholder="Nội dung ghi chú..." 
                    ><?php echo $note ? e($note['content']) : ''; ?></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <div class="left-actions">
                        <button type="button" class="btn btn-secondary" id="pinBtn" onclick="togglePin()">
                            📌 <?php echo ($note && $note['is_pinned']) ? 'Bỏ Ghim' : 'Ghim'; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="shareNote()">
                            🔗 Chia Sẻ
                        </button>
                    </div>
                    <div class="right-actions">
                        <?php if ($note): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteNote()">
                                🗑️ Xóa
                            </button>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const fontSizeSelect = document.getElementById('fontSize');
        const noteColorSelect = document.getElementById('noteColor');
        const saveStatus = document.getElementById('saveStatus');
        const saveDot = document.getElementById('saveDot');

        let saveTimeout;
        let noteId = <?php echo $note_id; ?>;

        // Auto-save on input
        [titleInput, contentInput].forEach(el => {
            el.addEventListener('input', debounce(autoSave, 2000));
        });

        [fontSizeSelect, noteColorSelect].forEach(el => {
            el.addEventListener('change', debounce(autoSave, 1000));
        });

        function debounce(func, delay) {
            return function(...args) {
                clearTimeout(saveTimeout);
                saveDot.className = 'status-dot saving';
                saveStatus.textContent = 'Đang lưu...';
                
                saveTimeout = setTimeout(() => {
                    func(...args);
                }, delay);
            };
        }

        function autoSave() {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            const fontSize = fontSizeSelect.value;
            const color = noteColorSelect.value;

            if (!title) {
                saveStatus.textContent = 'Tiêu đề không được để trống';
                saveDot.className = 'status-dot';
                return;
            }

            // Call API to save
            fetch('api/notes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: noteId ? 'update' : 'create',
                    id: noteId,
                    title: title,
                    content: content,
                    font_size: fontSize,
                    color: color
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (!noteId && data.data.id) {
                        noteId = data.data.id;
                        window.history.replaceState({}, '', '?id=' + noteId);
                    }
                    saveDot.className = 'status-dot saved';
                    saveStatus.textContent = 'Đã lưu';
                } else {
                    saveDot.className = 'status-dot';
                    saveStatus.textContent = 'Lỗi lưu';
                }
            })
            .catch(err => {
                console.error(err);
                saveDot.className = 'status-dot';
                saveStatus.textContent = 'Lỗi kết nối';
            });
        }

        function updateFontSize() {
            const size = fontSizeSelect.value;
            contentInput.style.fontSize = size + 'px';
            autoSave();
        }

        function updateNoteColor() {
            autoSave();
        }

        function togglePin() {
            if (!noteId) {
                alert('Vui lòng lưu ghi chú trước');
                return;
            }
            
            fetch('api/notes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'toggle_pin',
                    id: noteId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('pinBtn');
                    btn.textContent = data.data.is_pinned ? '📌 Bỏ Ghim' : '📌 Ghim';
                }
            });
        }

        function deleteNote() {
            if (!noteId) return;
            
            if (!confirm('Bạn chắc chắn muốn xóa ghi chú này?')) return;
            
            fetch('api/notes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'delete',
                    id: noteId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.href = 'dashboard.php';
                }
            });
        }

        function shareNote() {
            if (!noteId) {
                alert('Vui lòng lưu ghi chú trước');
                return;
            }
            
            const email = prompt('Nhập email muốn chia sẻ:');
            if (!email) return;
            
            fetch('api/notes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'share',
                    id: noteId,
                    email: email,
                    permission: 'read'
                })
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
            });
        }

        function addLabel() {
            const select = document.getElementById('labelSelect');
            const labelId = select.value;
            if (!labelId) return;
            
            const label = <?php echo json_encode($all_labels); ?>.find(l => l.id == labelId);
            if (!label) return;
            
            const container = document.getElementById('labelsContainer');
            const badge = document.createElement('div');
            badge.className = 'label-badge';
            badge.style.backgroundColor = label.color || '#2563eb';
            badge.innerHTML = `${label.name} <button type="button" onclick="removeLabel(this)">×</button>`;
            container.appendChild(badge);
            
            select.value = '';
            autoSave();
        }

        function removeLabel(btn) {
            btn.parentElement.remove();
            autoSave();
        }
    </script>
</body>
</html>
