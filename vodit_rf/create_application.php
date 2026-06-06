<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

// Получаем список курсов
$stmt = $pdo->query("SELECT * FROM courses ORDER BY vehicle_type, name");
$courses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? '';
    $start_date = trim($_POST['start_date'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Валидация
    if (empty($course_id)) {
        $error = 'Выберите курс';
    } elseif (empty($start_date)) {
        $error = 'Укажите дату начала обучения';
    } elseif (empty($payment_method)) {
        $error = 'Выберите способ оплаты';
    } else {
        // ПРОВЕРКА ФОРМАТА ДАТЫ (ДД.ММ.ГГГГ или ДД-ММ-ГГГГ)
        $date_formatted = false;
        $start_date_db = '';
        
        // Формат ДД.ММ.ГГГГ
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $start_date)) {
            $parts = explode('.', $start_date);
            $start_date_db = "$parts[2]-$parts[1]-$parts[0]";
            $date_formatted = true;
        }
        // Формат ДД-ММ-ГГГГ
        elseif (preg_match('/^\d{2}\-\d{2}\-\d{4}$/', $start_date)) {
            $parts = explode('-', $start_date);
            $start_date_db = "$parts[2]-$parts[1]-$parts[0]";
            $date_formatted = true;
        }
        // Формат ГГГГ-ММ-ДД (уже правильный)
        elseif (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $start_date)) {
            $start_date_db = $start_date;
            $date_formatted = true;
        }
        
        if (!$date_formatted) {
            $error = 'Неверный формат даты. Используйте ДД.ММ.ГГГГ (например, 25.12.2024)';
        } else {
            // Проверка, что дата не в прошлом
            $today = date('Y-m-d');
            if ($start_date_db < $today) {
                $error = 'Дата начала не может быть в прошлом';
            } else {
                // Сохраняем заявку
                $sql = "INSERT INTO applications (user_id, course_id, start_date, payment_method, status) 
                        VALUES (?, ?, ?, ?, 'Новая')";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$user_id, $course_id, $start_date_db, $payment_method])) {
                    $success = true;
                } else {
                    $error = 'Ошибка при создании заявки';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Новая заявка</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .date-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .example-date {
            font-family: monospace;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛵ Водить.РФ</h1>
            <p>Запись на курсы вождения</p>
        </div>
        <div class="content">
            <?php if($success): ?>
                <div class="success">
                    <h3>✅ Заявка успешно создана!</h3>
                    <p>Ваша заявка отправлена на согласование администратору.</p>
                    <a href="profile.php" class="btn">📋 В личный кабинет</a>
                </div>
            <?php else: ?>
                <h2 style="text-align: center; margin-bottom: 20px;">📝 Новая заявка на обучение</h2>
                
                <?php if($error): ?>
                    <div class="error-message">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="applicationForm">
                    <div class="form-group">
                        <label>⛵ Выберите курс</label>
                        <select name="course_id" required>
                            <option value="">-- Выберите курс --</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"
                                    <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['name']) ?> 
                                    (<?= $course['vehicle_type'] ?>, 
                                     <?= $course['duration_hours'] ?> ч., 
                                     <?= number_format($course['price'], 0, '', ' ') ?> ₽)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Желаемая дата начала</label>
                        <input type="text" 
                               name="start_date" 
                               id="start_date"
                               placeholder="ДД.ММ.ГГГГ" 
                               value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>"
                               required>
                        <div class="date-hint">
                            📅 <strong>Примеры правильного ввода:</strong> 
                            <span class="example-date">25.12.2024</span> или 
                            <span class="example-date">25-12-2024</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>💳 Способ оплаты</label>
                        <select name="payment_method" required>
                            <option value="">-- Выберите способ --</option>
                            <option value="Наличные" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Наличные') ? 'selected' : '' ?>>💰 Наличные</option>
                            <option value="Карта" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Карта') ? 'selected' : '' ?>>💳 Банковская карта</option>
                            <option value="Безналичный расчёт" <?= (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Безналичный расчёт') ? 'selected' : '' ?>>🏦 Безналичный расчёт</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">🎓 Отправить заявку</button>
                    <a href="profile.php" class="btn btn-secondary" style="text-align: center; display: block; margin-top: 10px;">← Назад</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Автоматическая маска для ввода даты
        const dateInput = document.getElementById('start_date');
        if (dateInput) {
            dateInput.addEventListener('input', function(e) {
                let value = this.value.replace(/[^\d]/g, '');
                if (value.length >= 2 && value.length < 5) {
                    value = value.slice(0, 2) + '.' + value.slice(2);
                } else if (value.length >= 5 && value.length < 9) {
                    value = value.slice(0, 2) + '.' + value.slice(2, 4) + '.' + value.slice(4, 8);
                } else if (value.length >= 9) {
                    value = value.slice(0, 2) + '.' + value.slice(2, 4) + '.' + value.slice(4, 8);
                }
                this.value = value;
            });
        }
        
        // Валидация перед отправкой
        const form = document.getElementById('applicationForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const dateValue = document.getElementById('start_date').value;
                const dateRegex = /^\d{2}\.\d{2}\.\d{4}$/;
                
                if (!dateRegex.test(dateValue)) {
                    e.preventDefault();
                    alert('Пожалуйста, введите дату в формате ДД.ММ.ГГГГ (например, 25.12.2024)');
                    return false;
                }
                return true;
            });
        }
    </script>
</body>
</html>