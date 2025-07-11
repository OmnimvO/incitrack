
CREATE DATABASE IF NOT EXISTS incitrack;
USE incitrack;

CREATE TABLE Users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(100) UNIQUE,
  password VARCHAR(255), 
  status ENUM('Active', 'Inactive'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  failed_attempts INT DEFAULT 0,
  lockout_time DATETIME DEFAULT NULL;

);

CREATE TABLE Residents (
  resident_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE,  
  fname VARCHAR(255),
  lname VARCHAR(255),
  contact_number VARCHAR(20),
  purok ENUM('Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'),
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE Barangay_Officials (
  official_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE, 
  name VARCHAR(255),
  position ENUM('Secretary', 'Chairperson'),
  contact_number VARCHAR(20),
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE Tanods (
  tanod_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE, 
  name VARCHAR(255),
  contact_number VARCHAR(20),
  assigned_area VARCHAR(255),
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    default_urgency ENUM('Low', 'Medium', 'High') DEFAULT 'Medium'
);

CREATE TABLE incident_reports (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL, 
    category_id INT NOT NULL, 
    urgency_level ENUM('High', 'Medium', 'Low'),
    purok ENUM('Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7') NOT NULL,
    landmark VARCHAR(255) DEFAULT NULL, 
    latitude DECIMAL(9,6) NOT NULL, 
    longitude DECIMAL(9,6) NOT NULL, 
    details TEXT NOT NULL, 
    status ENUM('open', 'in progress', 'resolved') DEFAULT 'open',
    reported_datetime DATETIME DEFAULT CURRENT_TIMESTAMP, 
    verified_by INT DEFAULT NULL,
    verified_datetime DATETIME DEFAULT NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id)
);

CREATE TABLE incident_victims (
    victim_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    resident_id INT DEFAULT NULL, 
    name VARCHAR(255) NOT NULL, 
    age INT DEFAULT NULL,
    contact_number VARCHAR(15) DEFAULT NULL,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE SET NULL
);

CREATE TABLE incident_perpetrators (
    perpetrator_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    resident_id INT DEFAULT NULL, 
    name VARCHAR(255) NOT NULL, 
    age INT DEFAULT NULL,
    contact_number VARCHAR(15) DEFAULT NULL,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE SET NULL
);

CREATE TABLE incident_witnesses (
    witness_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    resident_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL, 
    contact_number VARCHAR(15) DEFAULT NULL,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE SET NULL
);
CREATE TABLE incident_evidence (
    evidence_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
);

INSERT INTO categories (category_name, default_urgency)
VALUES 
    ('Fire', 'High'),
    ('Flood', 'High'),
    ('Medical Emergency', 'High'),
    ('Road Accident', 'High');

