-- Create database (optional)
CREATE DATABASE IF NOT EXISTS recipebook CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE recipebook;

-- users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- categories table
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

-- recipes table
CREATE TABLE recipes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  ingredients TEXT,
  instructions TEXT,
  category_id INT,
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- seed categories
INSERT INTO categories (name) VALUES
('Street Food'),
('Chinese'),
('Punjabi'),
('South Indian'),
('Desserts'),
('Beverages'),
('Healthy'),
('Breakfast'),
('Snacks');

-- seed admin user
-- password: Admin@123  (you can change it after)
INSERT INTO users (username, email, password, is_admin)
VALUES ('admin', 'admin@example.com', '$2y$10$zOQjJHk2jZz8wZCwWQWz9e0.IJp5l3Vf7YZ4bK8pz3N6p9sQ1qW.6', 1);

-- Notes:
-- The password hash above is password_hash('Admin@123', PASSWORD_DEFAULT)
