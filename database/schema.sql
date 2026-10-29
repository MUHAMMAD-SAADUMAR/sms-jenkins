-- Student Management System: database schema and starting data.
-- Run against an empty database, e.g.:  mysql -u root -p sms < schema.sql

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    reg_no        VARCHAR(20)  NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE courses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code         VARCHAR(10)  NOT NULL UNIQUE,
    title        VARCHAR(150) NOT NULL,
    description  TEXT NULL,
    credit_hours TINYINT UNSIGNED NOT NULL DEFAULT 3,
    instructor   VARCHAR(100) NOT NULL,
    capacity     SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED NOT NULL,
    course_id   INT UNSIGNED NOT NULL,
    grade       VARCHAR(2) NULL,
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    graded_at   DATETIME NULL,
    UNIQUE KEY uq_student_course (student_id, course_id),
    CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollment_course  FOREIGN KEY (course_id)  REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default administrator. Login: admin@sms.local / Admin@123 (change it after first login).
INSERT INTO users (name, email, reg_no, password_hash, role) VALUES
('System Administrator', 'admin@sms.local', NULL,
 '$2y$10$PImIXo2rVk1ulXb.qLFNOOBT/MV.FeXpqZ4SzHMn/nRTSdtj94fFi', 'admin');

INSERT INTO courses (code, title, description, credit_hours, instructor, capacity) VALUES
('CSC418', 'DevOps for Cloud Computing', 'CI/CD, containers and cloud deployment on AWS.', 3, 'Dr. Imran Shah', 40),
('CSC462', 'Artificial Intelligence', 'Search, knowledge representation and machine learning basics.', 3, 'Dr. Ayesha Khan', 35),
('CSC354', 'Machine Learning', 'Supervised and unsupervised learning with Python.', 3, 'Dr. Bilal Ahmed', 30),
('CSC371', 'Database Systems', 'Relational design, SQL and transactions.', 4, 'Dr. Sara Iqbal', 40),
('CSC339', 'Data Communication & Networks', 'Network layers, protocols and routing.', 3, 'Mr. Usman Tariq', 30),
('HUM110', 'Islamic Studies', 'Core concepts of Islamic teachings.', 2, 'Dr. Hafiz Rehman', 60);
