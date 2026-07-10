-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `karyashala`;
USE `karyashala`;

-- Create Employee table
CREATE TABLE IF NOT EXISTS `Employee` (
    `ic_number` INT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `remark` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create role_table
CREATE TABLE IF NOT EXISTS `role_table` (
    `ic_number` INT NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    PRIMARY KEY (`ic_number`, `role`),
    FOREIGN KEY (`ic_number`) REFERENCES `Employee` (`ic_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create workshop table
CREATE TABLE IF NOT EXISTS `workshop` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ic_number` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `attended_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ic_number`) REFERENCES `Employee` (`ic_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create verified_record table
CREATE TABLE IF NOT EXISTS `verified_record` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ic_number` INT NOT NULL,
    `year` INT NOT NULL,
    `verified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_by` INT NOT NULL,
    UNIQUE KEY `unique_ic_year` (`ic_number`, `year`),
    FOREIGN KEY (`ic_number`) REFERENCES `Employee` (`ic_number`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `Employee` (`ic_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

