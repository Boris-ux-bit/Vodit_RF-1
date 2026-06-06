<?php
// config.php - подключение к базе данных
$host = 'localhost';
$dbname = 'vodit_rf';
$username = 'root';
$password = '';

try {
    // Создаём подключение PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Устанавливаем режим ошибок: исключения
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Устанавливаем режим выборки по умолчанию: ассоциативный массив
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Для отладки - раскомментируйте, если нужно проверить подключение
    // echo "Подключение к базе данных успешно!";
} catch(PDOException $e) {
    // Если ошибка подключения - показываем понятное сообщение и останавливаем скрипт
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>