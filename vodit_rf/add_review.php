<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$application_id = (int)$_GET['application_id'];
$user_id = $_SESSION['user_id'];

// Проверяем, что заявка принадлежит пользователю и имеет статус "Обучение завершено"
$stmt = $pdo->prepare("
    SELECT a.*, c.name as course_name 
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.user_id = ? AND a.status = 'Обучение завершено'
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch();

if (!$application) {
    die("<div class='container'><div class='content'><h2>❌ Ошибка</h2><p>Отзыв можно оставить только после завершения обучения.</p><a href='profile.php'>← Вернуться</a></div></div>");
}

// Проверяем, нет ли уже отзыва
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE application_id = ?");
$stmt->execute([$application_id]);
if ($stmt->fetch()) {
    die("<div class='container'><div class='content'><h2>ℹ️ Информация</h2><p>Вы уже оставляли отзыв для этой заявки.</p><a href='profile.php'>← Вернуться</a></div></div>");
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (application_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        if ($stmt->execute([$application_id, $user_id, $rating, $comment])) {
            $success = true;
        } else {
            $error = 'Ошибка при сохранении отзыва';
        }
    } else {
        $error = 'Пожалуйста, заполните все поля';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Отзыв</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .rating-stars {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .star {
            font-size: 30px;
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s ease;
        }
        .star:hover, .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        .booking-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛵ Водить.РФ</h1>
            <p>Оставить отзыв</p>
        </div>
        <div class="content">
            <?php if($success): ?>
                <div class="success">
                    <h3>✅ Спасибо за отзыв!</h3>
                    <a href="profile.php" class="btn">Вернуться</a>
                </div>
            <?php else: ?>
                <div class="booking-info">
                    <strong>📚 Курс:</strong> <?= htmlspecialchars($application['course_name']) ?><br>
                    <strong>📅 Дата начала:</strong> <?= date('d.m.Y', strtotime($application['start_date'])) ?>
                </div>
                
                <?php if($error): ?>
                    <div class="error">❌ <?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>⭐ Оценка</label>
                        <div class="rating-stars" id="ratingStars">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" required>
                    </div>
                    
                    <div class="form-group">
                        <label>💬 Ваш отзыв</label>
                        <textarea name="comment" rows="5" placeholder="Расскажите о своём опыте обучения..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">✍️ Отправить отзыв</button>
                    <a href="profile.php" class="btn btn-secondary" style="text-align:center;">← Отмена</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingValue');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.dataset.value;
                ratingInput.value = value;
                
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const value = this.dataset.value;
                stars.forEach((s, index) => {
                    if (index < value) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentValue = ratingInput.value;
                stars.forEach((s, index) => {
                    if (currentValue && index < currentValue) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html>