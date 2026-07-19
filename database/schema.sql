-- Business Risk Analyzer Database Schema
-- Use this file to initialize the database in MySQL

CREATE DATABASE IF NOT EXISTS business_risk_analyzer;
USE business_risk_analyzer;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    category ENUM('F&B', 'Jasa', 'Retail') NOT NULL,
    target_pasar_utama VARCHAR(255) NOT NULL,
    market_saturation ENUM('Belum ada', 'Sedikit', 'Banyak', 'Sangat Jenuh') NOT NULL,
    capex DECIMAL(15, 2) NOT NULL CHECK (capex >= 0),
    opex DECIMAL(15, 2) NOT NULL CHECK (opex >= 0),
    average_price DECIMAL(15, 2) NOT NULL CHECK (average_price >= 0),
    gemini_raw_response JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
