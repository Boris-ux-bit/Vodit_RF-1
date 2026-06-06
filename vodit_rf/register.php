<?php
session_start();
require_once 'config.php';

$page_title = 'Регистрация';
$errors = [];
$success = false;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['login'] = trim($_POST['login'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['full_name'] = trim($_POST['full_name'] ?? '');
    $form_data['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    
    // Валидация логина
    if (empty($form_data['login'])) {
        $errors['login'] = 'Введите логин';
    } elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $form_data['login'])) {
        $errors['login'] = 'Логин должен содержать минимум 6 символов (латиница и цифры)';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$form_data['login']]);
        if ($stmt->fetch()) {
            $errors['login'] = 'Такой логин уже существует';
        }
    }
    
    // Валидация пароля
    if (empty($form_data['password'])) {
        $errors['password'] = 'Введите пароль';
    } elseif (strlen($form_data['password']) < 8) {
        $errors['password'] = 'Пароль должен быть минимум 8 символов';
    }
    
    // Валидация ФИО
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = 'Введите ФИО';
    }
    
    // Валидация даты рождения
    if (empty($form_data['date_of_birth'])) {
        $errors['date_of_birth'] = 'Введите дату рождения';
    } elseif (!strtotime($form_data['date_of_birth'])) {
        $errors['date_of_birth'] = 'Неверный формат даты';
    }
    
    // Валидация телефона
    if (empty($form_data['phone'])) {
        $errors['phone'] = 'Введите номер телефона';
    }
    
    // Валидация email
    if (empty($form_data['email'])) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$form_data['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Этот email уже зарегистрирован';
        }
    }
    
    // Сохранение в БД
    if (empty($errors)) {
        $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (login, password, full_name, date_of_birth, phone, email, role) 
                VALUES (?, ?, ?, ?, ?, ?, 'user')";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$form_data['login'], $hashed_password, $form_data['full_name'], 
                           $form_data['date_of_birth'], $form_data['phone'], $form_data['email']])) {
            $success = true;
            $form_data = [];
        } else {
            $errors['general'] = 'Ошибка при регистрации';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить.РФ - Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛵ Водить.РФ</h1>
            <p>Запишитесь на курсы вождения речного транспорта</p>
        </div>
        <div class="content">
            <?php if($success): ?>
                <div class="success">
                    <h3>✅ Регистрация успешна!</h3>
                    <p>Теперь вы можете войти в систему.</p>
                    <a href="login.php" class="btn">Войти</a>
                </div>
            <?php else: ?>
                <h2>📝 Регистрация</h2>
                <?php if(isset($errors['general'])): ?>
                    <div class="error"><?= $errors['general'] ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>👤 Логин *</label>
                        <input type="text" name="login" value="<?= htmlspecialchars($form_data['login'] ?? '') ?>" required>
                        <?php if(isset($errors['login'])): ?>
                            <div class="field-error"><?= $errors['login'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>🔒 Пароль *</label>
                        <input type="password" name="password" required>
                        <?php if(isset($errors['password'])): ?>
                            <div class="field-error"><?= $errors['password'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>👨‍💼 ФИО *</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($form_data['full_name'] ?? '') ?>" required>
                        <?php if(isset($errors['full_name'])): ?>
                            <div class="field-error"><?= $errors['full_name'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 Дата рождения *</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($form_data['date_of_birth'] ?? '') ?>" required>
                        <?php if(isset($errors['date_of_birth'])): ?>
                            <div class="field-error"><?= $errors['date_of_birth'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>📞 Телефон *</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" required>
                        <?php if(isset($errors['phone'])): ?>
                            <div class="field-error"><?= $errors['phone'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>📧 Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
                        <?php if(isset($errors['email'])): ?>
                            <div class="field-error"><?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn">Зарегистрироваться</button>
                    
                    <div class="nav-links">
                        <a href="login.php">🔑 Уже есть аккаунт? Войти</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>