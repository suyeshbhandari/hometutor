-- ============================================================
--  Online Home Tutor Management System  -  Database
--  Import this file in phpMyAdmin (Import tab).
--  WARNING: it drops and re-creates the 4 tables, so any
--  existing data in home_tutor_db is erased.
-- ============================================================

CREATE DATABASE IF NOT EXISTS home_tutor_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE home_tutor_db;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS tutor_profiles;
DROP TABLE IF EXISTS users;

-- 1) users : login + identity for student, tutor and admin
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(50)  NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(10)  NOT NULL,
  role       ENUM('student','tutor','admin') NOT NULL DEFAULT 'student',
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2) tutor_profiles : extra details, one row per tutor
CREATE TABLE tutor_profiles (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NOT NULL UNIQUE,
  subjects         VARCHAR(200) NOT NULL DEFAULT '',
  qualification    VARCHAR(100) NOT NULL DEFAULT '',
  experience_years INT NOT NULL DEFAULT 0,
  location         VARCHAR(100) NOT NULL DEFAULT '',
  price_per_hour   INT NOT NULL DEFAULT 0,
  availability     VARCHAR(150) NOT NULL DEFAULT '',
  bio              VARCHAR(1000) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3) requests : booking requests between students and tutors
CREATE TABLE requests (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  tutor_id       INT NOT NULL,
  student_id     INT NOT NULL,
  subject        VARCHAR(100) NOT NULL,
  preferred_date DATE NOT NULL,
  preferred_time TIME NOT NULL,
  message        VARCHAR(300) NULL,
  status         ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tutor_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4) reviews : rating given by a student after a completed session
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL UNIQUE,
  tutor_id   INT NOT NULL,
  student_id INT NOT NULL,
  rating     TINYINT NOT NULL,
  comment    VARCHAR(300) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
  FOREIGN KEY (tutor_id)   REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sample data (safe to delete later)
-- Admin login   : admin@hometutor.com   / Admin@123
-- Other logins  : password is  Password@123
-- ------------------------------------------------------------
INSERT INTO users (id, name, email, password, phone, role, status) VALUES
(1, 'Site Admin',    'admin@hometutor.com',  '$2y$10$8enn0cub6ds9xhBdKr/RsOMD8UZ6hAoMdR9BnERRHBHKmw6Xlw3LC', '9800000000', 'admin',   'approved'),
(2, 'Ram Sharma',    'ram@example.com',      '$2y$10$QLyu9WKuYBCKsjHbQ0CfjuWVPJEX9aqXCqOoNgPdbn04T1ZJL/fbm', '9811111111', 'student', 'approved'),
(3, 'Anita Gurung',  'anita@example.com',    '$2y$10$QLyu9WKuYBCKsjHbQ0CfjuWVPJEX9aqXCqOoNgPdbn04T1ZJL/fbm', '9822222222', 'tutor',   'approved'),
(4, 'Bikash Thapa',  'bikash@example.com',   '$2y$10$QLyu9WKuYBCKsjHbQ0CfjuWVPJEX9aqXCqOoNgPdbn04T1ZJL/fbm', '9833333333', 'tutor',   'approved'),
(5, 'Pooja Karki',   'pooja@example.com',    '$2y$10$QLyu9WKuYBCKsjHbQ0CfjuWVPJEX9aqXCqOoNgPdbn04T1ZJL/fbm', '9844444444', 'tutor',   'pending');

INSERT INTO tutor_profiles (user_id, subjects, qualification, experience_years, location, price_per_hour, availability, bio) VALUES
(3, 'Mathematics, Science', 'BSc Mathematics', 3, 'Lalitpur', 500, 'Weekdays 4 PM - 7 PM', 'I help school students understand Maths and Science with simple examples.'),
(4, 'English, Computer', 'BCA', 2, 'Kathmandu', 400, 'Sat - Fri, 6 AM - 9 AM', 'Friendly tutor for English grammar and basic computer skills.'),
(5, 'Accountancy', 'BBS', 1, 'Bhaktapur', 350, 'Weekends', 'Accountancy tutor for +2 students.');

INSERT INTO requests (id, tutor_id, student_id, subject, preferred_date, preferred_time, message, status) VALUES
(1, 3, 2, 'Mathematics', '2026-09-10', '16:30:00', 'Need help with algebra for class 10.', 'completed'),
(2, 4, 2, 'English',     '2030-01-15', '07:00:00', 'Spoken English practice.',            'pending');

INSERT INTO reviews (request_id, tutor_id, student_id, rating, comment) VALUES
(1, 3, 2, 5, 'Explained everything very clearly. Highly recommended!');
