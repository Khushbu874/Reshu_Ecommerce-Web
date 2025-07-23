-- Create the database
CREATE DATABASE reshu;

-- Use the database
USE reshu;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    description TEXT
);

-- Create cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

ALTER TABLE orders ADD COLUMN status VARCHAR(20) DEFAULT 'Pending';
ALTER TABLE products ADD COLUMN user_id INT;
ALTER TABLE products ADD COLUMN category VARCHAR(100) NOT NULL AFTER description;

ALTER TABLE products 
ADD COLUMN market_price DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN stock INT DEFAULT 0,
ADD COLUMN colors VARCHAR(255) DEFAULT NULL,
ADD COLUMN additional_images TEXT DEFAULT NULL;
ALTER TABLE cart ADD chosen_color VARCHAR(50);

DROP TABLE IF EXISTS orders;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE,
    user_id INT,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50),
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    chosen_color VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

CREATE INDEX idx_order_id ON order_items(order_id);
ALTER TABLE users ADD mobile VARCHAR(15);
ALTER TABLE users 
ADD address TEXT,
ADD city VARCHAR(100),
ADD pincode VARCHAR(10),
ADD state VARCHAR(100);

ALTER TABLE order_items ADD name VARCHAR(255);
ALTER TABLE order_items ADD image VARCHAR(255);