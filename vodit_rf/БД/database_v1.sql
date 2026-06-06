-- =====================================================
-- БАЗА ДАННЫХ ДЛЯ ПОРТАЛА «Водить.РФ»
-- Вариант №1
-- =====================================================

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS vodit_rf;
USE vodit_rf;

-- =====================================================
-- 1. ТАБЛИЦА users (пользователи)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. ТАБЛИЦА courses (курсы/виды транспорта)
-- =====================================================
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    duration_hours INT,
    price DECIMAL(10, 2)
);

-- =====================================================
-- 3. ТАБЛИЦА applications (заявки на обучение)
-- =====================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    start_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('Новая', 'Идет обучение', 'Обучение завершено') DEFAULT 'Новая',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- =====================================================
-- 4. ТАБЛИЦА reviews (отзывы)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- =====================================================

-- Добавление курсов (видов транспорта)
INSERT IGNORE INTO courses (id, name, vehicle_type, duration_hours, price) VALUES
(1, 'Вождение катера', 'Катер', 16, 15000),
(2, 'Управление яхтой', 'Яхта', 24, 25000),
(3, 'Круизный лайнер: базовый курс', 'Круизный лайнер', 40, 45000),
(4, 'Круизный лайнер: продвинутый', 'Круизный лайнер', 60, 65000),
(5, 'Скоростной катер', 'Катер', 20, 20000),
(6, 'Капитан яхты', 'Яхта', 36, 35000);

-- Добавление администратора (пароль: Demo20)
INSERT IGNORE INTO users (id, login, password, full_name, date_of_birth, phone, email, role) 
VALUES (1, 'Admin26', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Администратор', '1990-01-01', '+7 (000) 000-00-00', 'admin@vodit.ru', 'admin');

-- =====================================================
-- ГОТОВО!
-- =====================================================