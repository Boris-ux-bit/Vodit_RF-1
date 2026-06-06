<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['user_login'];

// Получаем заявки пользователя
$stmt = $pdo->prepare("
    SELECT a.*, c.name as course_name, c.vehicle_type,
           (SELECT COUNT(*) FROM reviews WHERE application_id = a.id) as has_review
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Личный кабинет</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛵ Водить.РФ</h1>
            <p>Добро пожаловать, <?= htmlspecialchars($user_login) ?>!</p>
        </div>
        <div class="content">
            <!-- СЛАЙДЕР -->
            <div id="boat-slider" style="margin-bottom: 30px;"></div>
            
            <h2>📋 Мои заявки на обучение</h2>
            
            <?php if(empty($applications)): ?>
                <p style="text-align:center; color:#999;">У вас пока нет заявок. Создайте первую!</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr><th>Курс</th><th>Тип</th><th>Дата начала</th><th>Оплата</th><th>Статус</th><th>Отзыв</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['course_name']) ?></td>
                                <td><?= htmlspecialchars($app['vehicle_type']) ?></td>
                                <td><?= date('d.m.Y', strtotime($app['start_date'])) ?></td>
                                <td><?= htmlspecialchars($app['payment_method']) ?></td>
                                <td><?= $app['status'] ?></td>
                                <td>
                                    <?php if($app['status'] === 'Обучение завершено' && $app['has_review'] == 0): ?>
                                        <a href="add_review.php?application_id=<?= $app['id'] ?>" class="action-link">✍️ Оставить отзыв</a>
                                    <?php elseif($app['has_review'] > 0): ?>
                                        <span style="color:green;">✓ Отзыв оставлен</span>
                                    <?php else: ?>
                                        <span style="color:#999;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="nav-links" style="margin-top: 20px;">
                <a href="create_application.php" class="btn" style="display:inline-block; width:auto;">➕ Новая заявка</a>
                <a href="logout.php" class="btn btn-secondary" style="display:inline-block; width:auto;">🚪 Выйти</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/slider.js"></script>
</body>
</html>