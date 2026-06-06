<?php
session_start();
require_once 'config.php';

$error = '';

// Проверяем, есть ли уже сессия
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin_panel.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        // Ищем пользователя в БД
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user) {
            // ВРЕМЕННОЕ РЕШЕНИЕ: проверяем пароль в открытом виде
            // Для администратора с паролем Demo20
            if ($login === 'Admin26' && $password === 'Demo20') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: admin_panel.php');
                exit;
            }
            // Обычная проверка хеша для остальных пользователей
            elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin_panel.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Вход</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .admin-box {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            background: #e8f0fe;
            border-radius: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛵ Водить.РФ</h1>
            <p>Запишитесь на курсы вождения речного транспорта</p>
        </div>
        <div class="content">
            <h2 style="text-align: center; margin-bottom: 25px;">🔑 Вход в систему</h2>
            
            <?php if($error): ?>
                <div class="error-box">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>👤 Логин</label>
                    <input type="text" name="login" id="login" value="Admin26" required>
                </div>
                
                <div class="form-group">
                    <label>🔒 Пароль</label>
                    <input type="password" name="password" id="password" value="Demo20" required>
                </div>
                
                <button type="submit" class="btn">🚪 Войти</button>
                
                <div class="nav-links">
                    <a href="register.php">📝 Ещё не зарегистрированы? Регистрация</a>
                </div>
            </form>
            
            <hr>
            
            <div class="admin-box">
                👑 <strong>Тестовые данные для входа:</strong><br>
                Администратор: <strong>Admin26</strong> / <strong>Demo20</strong><br>
                Обычный пользователь: зарегистрируйтесь
            </div>
        </div>
    </div>
    
    <script>
        // Автоматическое заполнение полей для удобства
        document.getElementById('login').value = 'Admin26';
        document.getElementById('password').value = 'Demo20';
    </script>
</body>
</html>