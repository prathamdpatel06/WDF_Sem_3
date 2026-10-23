-- ============================================================
-- StudentHUB database schema + seed data (Practical 8 / 9 / 10 / 11 / 12 / 14)
-- Import in phpMyAdmin:  Import tab -> choose this file -> Go
--
-- Demo logins
--   Admin   : username  admin    password  Admin@123
--   Student : username  student  password  Student@123
--   (all seeded students use the password  Student@123)
-- ============================================================

CREATE DATABASE IF NOT EXISTS studenthub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, remember_tokens, contact_inquiries, feedback, registrations, events, students, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Login accounts (admin + students) ----------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(20)  NOT NULL UNIQUE,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login    DATETIME     NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Student profile (one row per student user) ----------
CREATE TABLE students (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  student_code  VARCHAR(15)  NOT NULL UNIQUE,
  mobile        VARCHAR(10)  NOT NULL,
  department    VARCHAR(100) NOT NULL,
  year_of_study TINYINT UNSIGNED NOT NULL,
  gender        ENUM('Male','Female','Other') NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Events ----------
CREATE TABLE events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(150) NOT NULL,
  category    ENUM('Hackathon','Workshop','Symposium','Seminar','Cultural','Sports') NOT NULL,
  event_date  DATE         NOT NULL,
  location    VARCHAR(120) NOT NULL,
  seats_limit INT UNSIGNED NOT NULL DEFAULT 100,
  description TEXT         NULL,
  poster      VARCHAR(255) NULL,
  status      ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Registrations (many-to-many: students <-> events) ----------
CREATE TABLE registrations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id    INT UNSIGNED NOT NULL,
  event_id      INT UNSIGNED NOT NULL,
  registered_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Confirmed','Cancelled') NOT NULL DEFAULT 'Confirmed',
  UNIQUE KEY uq_student_event (student_id, event_id),
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Feedback from students ----------
CREATE TABLE feedback (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  feedback_type VARCHAR(30)  NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  comments      TEXT         NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Contact form messages ----------
CREATE TABLE contact_inquiries (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT         NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Remember-me tokens (only SHA-256 hashes are stored) ----------
CREATE TABLE remember_tokens (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64)     NOT NULL UNIQUE,
  expires_at  DATETIME     NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Audit log (Practical 14) ----------
CREATE TABLE audit_logs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  username   VARCHAR(50)  NOT NULL,
  action     VARCHAR(100) NOT NULL,
  details    TEXT         NULL,
  ip_address VARCHAR(45)  NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- Password hashes were made with PHP password_hash(); the plain text is shown above.
-- ============================================================
INSERT INTO users (id, username, email, password_hash, full_name, role, is_active) VALUES
(1,  'admin',         'admin@studenthub.edu',          '$2y$10$J1Sp9UpgrfxzVZacmv0uBOde2DjtVI4xsEzR5Ni2xPLjDcBRbT1UW', 'System Administrator', 'admin',   1),
(2,  'student',       'student@studenthub.edu',        '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Alex Morgan',          'student', 1),
(3,  'priya_sharma',  'priya.sharma@studenthub.edu',   '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Priya Sharma',         'student', 1),
(4,  'rahul_patel',   'rahul.patel@studenthub.edu',    '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Rahul Patel',          'student', 1),
(5,  'neha_desai',    'neha.desai@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Neha Desai',           'student', 1),
(6,  'krish_shah',    'krish.shah@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Krish Shah',           'student', 1),
(7,  'aisha_khan',    'aisha.khan@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Aisha Khan',           'student', 1),
(8,  'dhruv_mehta',   'dhruv.mehta@studenthub.edu',    '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Dhruv Mehta',          'student', 1),
(9,  'meera_joshi',   'meera.joshi@studenthub.edu',    '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Meera Joshi',          'student', 1),
(10, 'yash_trivedi',  'yash.trivedi@studenthub.edu',   '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Yash Trivedi',         'student', 1),
(11, 'ananya_rao',    'ananya.rao@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Ananya Rao',           'student', 1),
(12, 'harsh_pandya',  'harsh.pandya@studenthub.edu',   '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Harsh Pandya',         'student', 1),
(13, 'riya_singh',    'riya.singh@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Riya Singh',           'student', 1),
(14, 'kunal_soni',    'kunal.soni@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Kunal Soni',           'student', 1),
(15, 'tanvi_bhatt',   'tanvi.bhatt@studenthub.edu',    '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Tanvi Bhatt',          'student', 1),
(16, 'jay_patel',     'jay.patel@studenthub.edu',      '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Jay Patel',            'student', 1),
(17, 'sneha_iyer',    'sneha.iyer@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Sneha Iyer',           'student', 1),
(18, 'mihir_vora',    'mihir.vora@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Mihir Vora',           'student', 1),
(19, 'pooja_nair',    'pooja.nair@studenthub.edu',     '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Pooja Nair',           'student', 1),
(20, 'aryan_gupta',   'aryan.gupta@studenthub.edu',    '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Aryan Gupta',          'student', 1),
(21, 'zara_qureshi',  'zara.qureshi@studenthub.edu',   '$2y$10$YJBd84rs4IkZVnpfxV3iN.txFLAS6KtFaeFdyj12cI9k1aTP5tMWa', 'Zara Qureshi',         'student', 0);

-- student rows: student id n belongs to user id n+1
INSERT INTO students (id, user_id, student_code, mobile, department, year_of_study, gender) VALUES
(1,  2,  'STU202601', '9876543210', 'Computer Engineering',        3, 'Male'),
(2,  3,  'STU202602', '9823456781', 'Information Technology',       5, 'Female'),
(3,  4,  'STU202603', '9712345678', 'Computer Engineering',        3, 'Male'),
(4,  5,  'STU202604', '9898011223', 'Electronics & Communication',  2, 'Female'),
(5,  6,  'STU202605', '9727711002', 'Mechanical Engineering',      4, 'Male'),
(6,  7,  'STU202606', '9824400112', 'Information Technology',       1, 'Female'),
(7,  8,  'STU202607', '9913366771', 'Civil Engineering',           2, 'Male'),
(8,  9,  'STU202608', '9650090011', 'Chemical Engineering',        3, 'Female'),
(9,  10, 'STU202609', '9601234509', 'Electrical Engineering',      1, 'Male'),
(10, 11, 'STU202610', '9448812345', 'Computer Engineering',        4, 'Female'),
(11, 12, 'STU202611', '9099900011', 'Information Technology',       3, 'Male'),
(12, 13, 'STU202612', '9876001122', 'Electronics & Communication',  1, 'Female'),
(13, 14, 'STU202613', '9825510234', 'Mechanical Engineering',      2, 'Male'),
(14, 15, 'STU202614', '9712200344', 'Civil Engineering',           3, 'Female'),
(15, 16, 'STU202615', '9428899120', 'Computer Engineering',        2, 'Male'),
(16, 17, 'STU202616', '9988776655', 'Information Technology',       4, 'Female'),
(17, 18, 'STU202617', '9737001122', 'Electrical Engineering',      3, 'Male'),
(18, 19, 'STU202618', '9845512300', 'Chemical Engineering',        2, 'Female'),
(19, 20, 'STU202619', '9811122233', 'Computer Engineering',        1, 'Male'),
(20, 21, 'STU202620', '9867700456', 'Electronics & Communication',  4, 'Female');

INSERT INTO events (id, title, category, event_date, location, seats_limit, description, poster, status) VALUES
(1, '24-Hour Campus Hackathon', 'Hackathon', '2026-11-05', 'Innovation Hub, Lab 3', 120,
   'A 24-hour coding marathon. Form teams of up to four and build something useful.', 'assets/images/event_hackathon.jpg', 'Open'),
(2, 'TechFest 2026', 'Symposium', '2026-11-18', 'Main Auditorium', 250,
   'Keynote talks, technical paper presentations and student project showcases.', 'assets/images/event_techfest.jpg', 'Open'),
(3, 'Creative Photography Workshop', 'Workshop', '2026-10-30', 'Media Studio B', 60,
   'Hands-on photography: light, framing and basic editing.', 'assets/images/event_photography.jpg', 'Open'),
(4, 'Generative AI Seminar', 'Seminar', '2026-11-28', 'Seminar Hall 2', 150,
   'An introduction to generative AI tools and how they are used in industry.', NULL, 'Open'),
(5, 'Inter-Department Cricket Tournament', 'Sports', '2026-12-10', 'College Ground', 200,
   'Twenty-over matches between departments. Team of 11 plus 3 substitutes.', NULL, 'Open'),
(6, 'Cultural Night: Rhythm 2026', 'Cultural', '2026-12-20', 'Open Air Theatre', 400,
   'Music, dance and drama performances by students from every department.', NULL, 'Open'),
(7, 'Web Development Bootcamp', 'Workshop', '2026-12-05', 'Computer Lab 4', 40,
   'Two-day bootcamp on HTML, CSS, JavaScript and PHP. Registration is closed.', NULL, 'Closed'),
(8, 'Startup Pitch Day (Past Event)', 'Seminar', '2026-09-12', 'Incubation Centre', 80,
   'Student startups pitch their ideas to a panel of mentors.', NULL, 'Closed');

INSERT INTO registrations (student_id, event_id, status) VALUES
(1, 1, 'Confirmed'), (1, 3, 'Confirmed'), (1, 2, 'Cancelled'),
(2, 1, 'Confirmed'), (2, 2, 'Confirmed'), (2, 6, 'Confirmed'), (2, 8, 'Confirmed'),
(3, 1, 'Confirmed'), (3, 4, 'Confirmed'),
(4, 2, 'Confirmed'), (4, 5, 'Confirmed'), (4, 6, 'Confirmed'),
(5, 3, 'Confirmed'), (5, 6, 'Confirmed'),
(6, 1, 'Confirmed'), (6, 5, 'Confirmed'),
(7, 4, 'Confirmed'), (7, 6, 'Confirmed'),
(8, 2, 'Confirmed'), (8, 3, 'Confirmed'),
(9, 5, 'Confirmed'),
(10, 1, 'Confirmed'), (10, 2, 'Confirmed'), (10, 4, 'Confirmed'), (10, 6, 'Confirmed'), (10, 8, 'Confirmed'),
(11, 3, 'Confirmed'), (11, 4, 'Confirmed'),
(12, 6, 'Confirmed'), (12, 1, 'Confirmed'),
(13, 5, 'Confirmed'),
(14, 2, 'Confirmed'), (14, 6, 'Confirmed'),
(15, 1, 'Confirmed'), (15, 4, 'Confirmed'),
(16, 2, 'Confirmed'), (16, 3, 'Confirmed'),
(17, 5, 'Confirmed'),
(18, 6, 'Confirmed'),
(19, 1, 'Confirmed'), (19, 6, 'Confirmed'),
(20, 4, 'Confirmed');

INSERT INTO feedback (user_id, feedback_type, rating, comments) VALUES
(2, 'Experience', 5, 'Registering for events is quick and easy.'),
(2, 'Suggestion', 4, 'Please add a calendar export for registered events.'),
(3, 'Bug Report', 3, 'The profile page took a while to save my changes once.');

INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES
(1, 'admin', 'SEED', 'Demo data created by schema.sql', '127.0.0.1');
