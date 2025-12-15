-- Database: reporting_system

CREATE DATABASE IF NOT EXISTS reporting_system;
USE reporting_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reports Table (Updated with Category & Priority)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    evidence VARCHAR(255),
    status ENUM('open', 'progress', 'closed') DEFAULT 'open',
    last_reply_by ENUM('user', 'admin') DEFAULT 'user',
    category VARCHAR(50) DEFAULT 'Other',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Comments Table (Chat)
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default Admin (Password: admin123)
-- Hash: $2y$10$wK1k... (This is just an example, ensure you have a valid hash)
-- INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@example.com', '$2y$10$YourHashHere', 'admin');
