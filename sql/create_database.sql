CREATE DATABASE IF NOT EXISTS fmc_fisheries;
USE fmc_fisheries;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_time DATETIME,
    profile_picture VARCHAR(255)
);

-- Vessel Owners Table
CREATE TABLE IF NOT EXISTS vessel_owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    company_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(50),
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vessels Table
CREATE TABLE IF NOT EXISTS vessels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imo_number VARCHAR(50) UNIQUE,
    vessel_name VARCHAR(100) NOT NULL,
    vessel_type VARCHAR(50),
    owner_id INT,
    call_sign VARCHAR(20),
    mmsi VARCHAR(20),
    flag_state VARCHAR(50),
    length_overall DECIMAL(10, 2),
    beam DECIMAL(10, 2),
    gross_tonnage DECIMAL(10, 2),
    contact_number VARCHAR(20),
    last_known_position VARCHAR(100),
    last_position_update DATETIME,
    status ENUM('active', 'inactive', 'under_maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES vessel_owners(id)
);

-- Border Crossing Alerts Table
CREATE TABLE IF NOT EXISTS border_crossing_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(100) NOT NULL,
    imo_number VARCHAR(50),
    owner_name VARCHAR(100),
    crossing_point VARCHAR(100),
    crossing_date DATETIME,
    departure_country VARCHAR(50),
    destination_country VARCHAR(50),
    status ENUM('pending', 'notified', 'cleared', 'denied') DEFAULT 'pending',
    remarks TEXT,
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Silent Vessel Alerts Table
CREATE TABLE IF NOT EXISTS silent_vessel_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(100) NOT NULL,
    imo_number VARCHAR(50),
    owner_name VARCHAR(100),
    owner_contact VARCHAR(20),
    relevant_harbour VARCHAR(100),
    last_known_position VARCHAR(100),
    last_signal_time DATETIME,
    owner_informed ENUM('yes', 'no') DEFAULT 'no',
    sms_to_owner VARCHAR(20),
    username VARCHAR(50),
    remarks TEXT,
    status ENUM('silent', 'active', 'resolved') DEFAULT 'silent',
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Distress Alerts Table
CREATE TABLE IF NOT EXISTS distress_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(100) NOT NULL,
    imo_number VARCHAR(50),
    owner_name VARCHAR(100),
    distress_type VARCHAR(100),
    location VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    distress_time DATETIME,
    description TEXT,
    persons_onboard INT,
    status ENUM('pending', 'notified', 'escalated', 'resolved') DEFAULT 'pending',
    rescue_status VARCHAR(50),
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    owner_id INT NOT NULL,
    payment_type VARCHAR(100),
    amount DECIMAL(12, 2),
    currency VARCHAR(10),
    payment_date DATE,
    due_date DATE,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id),
    FOREIGN KEY (owner_id) REFERENCES vessel_owners(id)
);

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    report_type VARCHAR(50),
    generated_by INT,
    generated_date DATETIME,
    start_date DATE,
    end_date DATE,
    content LONGTEXT,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Border Crossings Table
CREATE TABLE IF NOT EXISTS border_crossings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_imo_number VARCHAR(50) NOT NULL,
    eez VARCHAR(100),
    owner_informed_datetime DATETIME,
    phone_number VARCHAR(20),
    first_notice VARCHAR(100),
    after_72hr_boat_status VARCHAR(255),
    date_of_investigation DATETIME,
    called_owner_to_inform_dc VARCHAR(255),
    test_message_correct VARCHAR(255),
    departure_date DATETIME,
    after_72hr_remark TEXT,
    remarks TEXT,
    username VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Silent Vessels Table
CREATE TABLE IF NOT EXISTS silent_vessels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100),
    owner_contact_number VARCHAR(20),
    relevant_harbour VARCHAR(100),
    owner_information_date DATE,
    owner_informed VARCHAR(50),
    sms_to_owner VARCHAR(50),
    date_to_investigate DATE,
    comment TEXT,
    remarks TEXT,
    username VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Distress Vessels Table
CREATE TABLE IF NOT EXISTS distress_vessels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE,
    vessel_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100),
    contact_number VARCHAR(20),
    address VARCHAR(255),
    status VARCHAR(100),
    speed VARCHAR(50),
    position VARCHAR(100),
    date_time_detection DATETIME,
    distance_last_position VARCHAR(100),
    notes TEXT,
    remark TEXT,
    departure_form VARCHAR(255),
    voyage VARCHAR(255),
    reason TEXT,
    username VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Indexes for better query performance
CREATE INDEX idx_vessel_name ON vessels(vessel_name);
CREATE INDEX idx_imo_number ON vessels(imo_number);
CREATE INDEX idx_owner_id ON vessels(owner_id);
CREATE INDEX idx_alert_vessel ON border_crossing_alerts(vessel_id);
CREATE INDEX idx_alert_status ON border_crossing_alerts(status);
CREATE INDEX idx_silent_vessel ON silent_vessel_alerts(vessel_id);
CREATE INDEX idx_distress_vessel ON distress_alerts(vessel_id);
CREATE INDEX idx_payment_vessel ON payments(vessel_id);
CREATE INDEX idx_activity_user ON activity_logs(user_id);
CREATE INDEX idx_activity_timestamp ON activity_logs(timestamp);

--for silent_vessels:
ALTER TABLE silent_vessels
ADD COLUMN created_by INT NULL,
ADD COLUMN created_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD COLUMN updated_at DATETIME NULL;

--For distress_vessels:
ALTER TABLE distress_vessels
ADD COLUMN created_by INT NULL,
ADD COLUMN created_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD COLUMN updated_at DATETIME NULL;

--For border_crossings:
ALTER TABLE border_crossings
ADD COLUMN created_by INT NULL,
ADD COLUMN created_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD COLUMN updated_at DATETIME NULL;