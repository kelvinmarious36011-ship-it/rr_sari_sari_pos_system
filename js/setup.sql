-- RR Sari-Sari Store POS Database
CREATE DATABASE IF NOT EXISTS rr_sari_sari_pos;
USE rr_sari_sari_pos;

-- Users table (simple login)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default login: username = admin | password = admin123
INSERT INTO users (username, password, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Store Owner');

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions (header)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(20) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255) DEFAULT NULL
);

-- Transaction items (details)
CREATE TABLE transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

-- Sample products
INSERT INTO products (name, price, stock) VALUES
('Lucky Me Pancit Canton', 15.00, 50),
('Lucky Me Instant Noodles', 12.00, 40),
('Coca-Cola 1.5L', 65.00, 20),
('Sprite 1.5L', 65.00, 15),
('Royal Tru-Orange 1.5L', 65.00, 15),
('C2 Green Tea 500ml', 25.00, 30),
('Kopiko Coffee 3-in-1', 8.00, 100),
('Nescafe Classic 3-in-1', 10.00, 80),
('San Marino Corned Tuna', 28.00, 25),
('Century Tuna Flakes', 32.00, 20),
('Argentina Corned Beef', 45.00, 15),
('Purefoods Hotdog 1kg', 180.00, 10),
('Gardenia Bread', 55.00, 12),
('Egg (per piece)', 8.00, 60),
('Rice (per kilo)', 55.00, 30),
('Sabon (Bath Soap)', 25.00, 20),
('Colgate Toothpaste', 45.00, 15),
('Safeguard Soap', 30.00, 25),
('Detergent Powder (small)', 15.00, 40),
('Matches (box)', 5.00, 50),
('Candle (pcs)', 10.00, 30),
('Battery AA (pair)', 35.00, 20),
('Load Card 50', 50.00, 100),
('Load Card 100', 100.00, 50),
('Load Card 200', 200.00, 30);