-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `karyashala`;
USE `karyashala`;

-- Create admin table
CREATE TABLE IF NOT EXISTS `admin` (
    `ic_no` INT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(20) NOT NULL DEFAULT 'admin',
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create karyashala_admin table
CREATE TABLE IF NOT EXISTS `karyashala_admin` (
    `ic_no` INT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(20) NOT NULL DEFAULT 'karyashala_admin',
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `remark` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create workshops table
CREATE TABLE IF NOT EXISTS `workshops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ic_no` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `attended_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ic_no`) REFERENCES `karyashala_admin` (`ic_no`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reports table
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create verified_records table
CREATE TABLE IF NOT EXISTS `verified_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ic_no` INT NOT NULL,
    `year` INT NOT NULL,
    `verified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_by` INT NOT NULL,
    UNIQUE KEY `unique_ic_year` (`ic_no`, `year`),
    FOREIGN KEY (`ic_no`) REFERENCES `karyashala_admin` (`ic_no`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
