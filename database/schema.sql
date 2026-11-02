CREATE DATABASE php_form_validation
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE php_form_validation;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    family_name VARCHAR(50) NOT NULL,

    email VARCHAR(255) NOT NULL UNIQUE,
    mobile VARCHAR(14) NOT NULL,
    password VARCHAR(255) NOT NULL,

    date_of_birth DATE NOT NULL,

    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL DEFAULT NULL
);