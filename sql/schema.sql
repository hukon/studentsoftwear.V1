-- schema.sql - Complete database schema for Student Organizer
-- Updated: 2026-04-02

CREATE DATABASE IF NOT EXISTS student_organizer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_organizer;

-- ─── Users ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Classes ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Students ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  dob DATE NULL,
  bio TEXT NULL,
  pic_path VARCHAR(255) NULL,
  -- Difficulty category fields (boolean)
  comprehension_orale TINYINT(1) DEFAULT 0,
  ecriture TINYINT(1) DEFAULT 0,
  vocabulaire TINYINT(1) DEFAULT 0,
  grammaire TINYINT(1) DEFAULT 0,
  conjugaison TINYINT(1) DEFAULT 0,
  production_ecrite TINYINT(1) DEFAULT 0,
  -- Legacy fields
  category1 VARCHAR(120) NULL,
  difficulties JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seating ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seating (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  student_id INT NOT NULL,
  row_num INT NOT NULL,
  col_num INT NOT NULL,
  seat_num INT NOT NULL,
  UNIQUE KEY unique_seat (class_id, row_num, col_num, seat_num),
  UNIQUE KEY unique_student_seat (class_id, student_id),
  CONSTRAINT fk_seating_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_seating_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Holidays ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  recurring TINYINT(1) DEFAULT 0,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Schedules ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  day_of_week VARCHAR(20) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  subject VARCHAR(120) NOT NULL,
  teacher VARCHAR(120) NULL,
  room VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_schedules_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Evaluations ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  school_year VARCHAR(20) DEFAULT '2025/2026',
  oral_1 VARCHAR(1) NULL,
  oral_2 VARCHAR(1) NULL,
  oral_3 VARCHAR(1) NULL,
  reading_1 VARCHAR(1) NULL,
  reading_2 VARCHAR(1) NULL,
  reading_3 VARCHAR(1) NULL,
  comp_1 VARCHAR(1) NULL,
  comp_2 VARCHAR(1) NULL,
  comp_3 VARCHAR(1) NULL,
  prod_1 VARCHAR(1) NULL,
  prod_2 VARCHAR(1) NULL,
  prod_3 VARCHAR(1) NULL,
  prod_4 VARCHAR(1) NULL,
  global_mastery VARCHAR(50) NULL,
  evaluated_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_year (student_id, school_year),
  CONSTRAINT fk_evaluations_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
