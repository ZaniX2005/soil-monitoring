CREATE DATABASE IF NOT EXISTS soil_monitoring
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE soil_monitoring;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    device_key VARCHAR(100) NOT NULL UNIQUE,
    last_seen DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sensor_readings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id INT UNSIGNED NOT NULL,
    moisture_percent DECIMAL(5,2) NOT NULL,
    adc_value INT NOT NULL,
    fuzzy_status VARCHAR(30) NOT NULL,
    watering_seconds DECIMAL(6,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reading_device
        FOREIGN KEY (device_id) REFERENCES devices(id)
        ON DELETE CASCADE,
    INDEX idx_reading_created_at (created_at),
    INDEX idx_reading_device (device_id)
);

INSERT INTO devices (device_name, device_key)
SELECT 'ESP32 Soil Sensor 01', 'ESP32-SOIL-001'
WHERE NOT EXISTS (
    SELECT 1 FROM devices WHERE device_key = 'ESP32-SOIL-001'
);
