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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create employee table
CREATE TABLE IF NOT EXISTS `employee` (
    `ic_no` INT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(20) NOT NULL DEFAULT 'employee',
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create workshops table
CREATE TABLE IF NOT EXISTS `workshops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ic_no` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `attended_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ic_no`) REFERENCES `employee` (`ic_no`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reports table
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
