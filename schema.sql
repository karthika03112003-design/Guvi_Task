-- MySQL schema for profile storage (prepared statements are used in PHP)
CREATE DATABASE IF NOT EXISTS guvi_task;
USE guvi_task;

CREATE TABLE IF NOT EXISTS user_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL UNIQUE,
  age INT NULL,
  dob DATE NULL,
  contact VARCHAR(32) NULL,
  address TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

