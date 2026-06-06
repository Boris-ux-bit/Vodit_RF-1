<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_message = '';

// Обработка смены статуса
if (isset($_GET['change_status']) && isset($_GET['application_id'])) {
    $new_status = $_GET['change_status'];
    $application_id = (int)$_GET['application_id'];
    $allowed = ['Новая', 'Идет обучение', 'Обучение завершено'];
    
    if (in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $application_id]);
        $success_message = "Статус заявки #$application_id изменён на «$new_status»";
    }
}

// Фильтрация
$status_filter = $_GET['status_filter'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT a.*, u.login, u.full_name, c.name as course_name, c.vehicle_type
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN courses c ON a.course_id = c.id
    WHERE 1=1
";
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (u.login LIKE ? OR u.full_name LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Пагинация
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_sql = str_replace("SELECT a.*, u.login, u.full_name, c.name as course_name, c.vehicle_type", "SELECT COUNT(*)", $sql);
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

$sql .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Админ-панель</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; margin-bottom: 5px; display: block; color: #666; }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .filter-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            height: 38px;
        }
        .reset-btn { background: #6c757d; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-new { background: #ffc107; color: #000; }
        .status-learning { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .admin-btn {
            display: inline-block;
            padding: 5px 10px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 11px;
            margin: 2px;
        }
        .admin-btn.learning { background: #17a2b8; }
        .admin-btn.completed { background: #28a745; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            background: #f8f9fa;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
        }
        .pagination .active { background: #667eea; color: white; }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Панель администратора</h1>
            <p>Управление заявками на обучение</p>
        </div>
        <div class="content">
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label>🔍 Поиск</label>
                    <input type="text" name="search" placeholder="Логин, ФИО, курс" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label>📊 Фильтр по статусу</label>
                    <select name="status_filter">
                        <option value="all">Все заявки</option>
                        <option value="Новая" <?= $status_filter === 'Новая' ? 'selected' : '' ?>>🟡 Новая</option>
                        <option value="Идет обучение" <?= $status_filter === 'Идет обучение' ? 'selected' : '' ?>>🔵 Идет обучение</option>
                        <option value="Обучение завершено" <?= $status_filter === 'Обучение завершено' ? 'selected' : '' ?>>🟢 Обучение завершено</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Применить</button>
                <a href="admin_panel.php" class="filter-btn reset-btn">Сбросить</a>
            </form>
            
            <?php if(count($applications) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Пользователь</th><th>Курс</th><th>Дата</th><th>Оплата</th><th>Статус</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                                <?php
                                    $status_class = match($app['status']) {
                                        'Новая' => 'status-new',
                                        'Идет обучение' => 'status-learning',
                                        'Обучение завершено' => 'status-completed',
                                        default => ''
                                    };
                                ?>
                                <tr>
                                    <td><?= $app['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($app['login']) ?></strong><br><small><?= htmlspecialchars($app['full_name']) ?></small></td>
                                    <td><?= htmlspecialchars($app['course_name']) ?><br><small><?= $app['vehicle_type'] ?></small></td>
                                    <td><?= date('d.m.Y', strtotime($app['start_date'])) ?></td>
                                    <td><?= htmlspecialchars($app['payment_method']) ?></td>
                                    <td><span class="status-badge <?= $status_class ?>"><?= $app['status'] ?></span></td>
                                    <td>
                                        <?php if($app['status'] !== 'Обучение завершено'): ?>
                                            <a href="?change_status=Идет обучение&application_id=<?= $app['id'] ?>&page=<?= $page ?>" 
                                               class="admin-btn learning"
                                               onclick="return confirm('Начать обучение для заявки №<?= $app['id'] ?>?')">
                                                📚 Начать
                                            </a>
                                            <a href="?change_status=Обучение завершено&application_id=<?= $app['id'] ?>&page=<?= $page ?>" 
                                               class="admin-btn completed"
                                               onclick="return confirm('Завершить обучение для заявки №<?= $app['id'] ?>?')">
                                                ✅ Завершить
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&status_filter=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999;">Нет заявок</p>
            <?php endif; ?>
            
            <div class="nav-links" style="margin-top: 20px;">
                <a href="logout.php" class="btn" style="display:inline-block; width:auto;">🚪 Выйти</a>
            </div>
        </div>
    </div>
    
    <?