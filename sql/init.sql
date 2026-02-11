CREATE DATABASE IF NOT EXISTS sample_app CHARACTER SET utf8mb4;
USE sample_app;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(255),
    long_name VARCHAR(255),
    digit INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stock_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_id INT NOT NULL, 
    date DATE NOT NULL,
    open DECIMAL(10,2),
    high DECIMAL(10,2),
    low DECIMAL(10,2),
    close DECIMAL(10,2),
    volume BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (stock_id, date),
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stock_id INT NOT NULL,
    sort_order INT NOT NULL,
    is_visible TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_stock (user_id, stock_id)
);

CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stock_id INT NOT NULL, 
    date DATE,
    price DECIMAL(10,2),
    quantity BIGINT,
    type INT,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_trades_user_id (user_id),
    INDEX idx_trades_stock_id (stock_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE

);

-- INSERT INTO posts (title, content) VALUES
-- ('テスト投稿1', 'これはダミーデータです。'),
-- ('テスト投稿2', '2件目のダミーデータです。'),
-- ('テスト投稿3', '3件目のダミーデータです。');

-- INSERT INTO users (uuid, name, email, password, role) 
-- VALUES (
--     UUID(),
--     '管理者',
--     'admin@example.com',
--     '$2y$10$yyaI9Au8Il5ZSmUz4FaXjuxgvSvkLXvF2rL4wX2Ug2iYb.HoiNMIm',
--     'admin'
-- ), 
-- (
--     UUID(),
--     'ユーザー1',
--     'user@example.com',
--     '$2y$10$yyaI9Au8Il5ZSmUz4FaXjuxgvSvkLXvF2rL4wX2Ug2iYb.HoiNMIm',
--     'user'
-- ), 
-- (
--     UUID(),
--     'ユーザー2',
--     'user2@example.com',
--     '$2y$10$yyaI9Au8Il5ZSmUz4FaXjuxgvSvkLXvF2rL4wX2Ug2iYb.HoiNMIm',
--     'user'
-- );

-- INSERT INTO trades (user_id, stock_id, date, price, quantity,type, content) VALUES
-- (2, 11, '2026-01-23', 1000, 100, 1, 'test'),
-- (2, 11, '2026-01-22', 1000, 200, 2, 'test2');
