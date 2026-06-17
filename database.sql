-- Online Leerplatform — Complete database schema for XAMPP MySQL
-- Users split into: admins, students, leerkrachten

DROP TABLE IF EXISTS activity_feed;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS course_ratings;
DROP TABLE IF EXISTS module_completions;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS course_modules;
DROP TABLE IF EXISTS user_stats;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS leerkrachten;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS admins;

-- ---------------------------------------------------------------------------
-- Account tables (3 roles, 3 tables)
-- ---------------------------------------------------------------------------

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leerkrachten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Core tables
-- ---------------------------------------------------------------------------

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    instructor VARCHAR(100) NOT NULL,
    teacher_id INT NULL,
    duration_hours INT DEFAULT 0,
    price DECIMAL(10, 2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES leerkrachten(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subscription_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Feature tables
-- ---------------------------------------------------------------------------

CREATE TABLE user_stats (
    account_type ENUM('admin', 'student', 'leerkracht') NOT NULL,
    account_id INT NOT NULL,
    xp INT NOT NULL DEFAULT 0,
    streak_days INT NOT NULL DEFAULT 0,
    last_streak_date DATE NULL,
    PRIMARY KEY (account_type, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(20) NOT NULL DEFAULT 'primary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('admin', 'student', 'leerkracht') NOT NULL,
    account_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_account_badge (account_type, account_id, badge_id),
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE course_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE module_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    module_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_module (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE course_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_course_rating (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('admin', 'student', 'leerkracht') NOT NULL,
    account_id INT NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'bi-bell',
    title VARCHAR(150) NOT NULL,
    message VARCHAR(500) NULL,
    link VARCHAR(200) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_account (account_type, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('admin', 'student', 'leerkracht') NOT NULL,
    account_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'bi-activity',
    link VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_created (created_at),
    INDEX idx_activity_account (account_type, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------------

INSERT INTO admins (username, email, password_hash, first_name, last_name) VALUES
('admin', 'admin@leerplatform.nl', '$2y$10$SSt5uMK9EZECo1d.23pf/epslDzTdy/ujh6X2JPHcrsadoFNB8HG.', 'Admin', 'User');

INSERT INTO leerkrachten (username, email, password_hash, first_name, last_name) VALUES
('leerkracht1', 'leerkracht1@leerplatform.nl', '$2y$10$Zghe83fWTfG2KcxmftuP6.bCduLmkmimNcoB0JpK5Zv/WQc31HbJ6', 'Jan', 'Jansen');

INSERT INTO students (username, email, password_hash, first_name, last_name) VALUES
('student1', 'student1@leerplatform.nl', '$2y$10$8R9Nk/rIvlAEBAoPDWMXeOrrWHYyV5J8C7rDcTpjTidayMWJPsmOe', 'Student', 'Een');

INSERT INTO user_stats (account_type, account_id, xp) VALUES
('admin', 1, 0),
('leerkracht', 1, 0),
('student', 1, 0);

INSERT INTO courses (title, description, instructor, teacher_id, duration_hours, price, is_active) VALUES
('PHP Fundamentals', 'Leer de basis van PHP programmeren', 'Jan Jansen', 1, 40, 299.00, TRUE),
('MySQL Database Design', 'Database ontwerp en optimalisatie', 'Maria de Vries', NULL, 30, 249.00, TRUE),
('Web Development Advanced', 'Geavanceerde web development technieken', 'Peter Bakker', NULL, 50, 399.00, TRUE),
('JavaScript Basics', 'Introductie tot JavaScript', 'Lisa van der Berg', NULL, 25, 199.00, TRUE);

INSERT INTO subscriptions (student_id, subscription_type, start_date, end_date, is_active) VALUES
(1, 'Premium', '2024-01-01', '2024-12-31', TRUE);

INSERT INTO badges (code, name, description, icon, color) VALUES
('first_enroll',  'Eerste stap',      'Schreef je in voor je eerste cursus',         'bi-rocket-takeoff', 'primary'),
('five_courses',  'Leergierig',       'Volgt 5 of meer cursussen',                  'bi-collection',     'info'),
('ten_courses',   'Kennishonger',     'Volgt 10 of meer cursussen',                 'bi-stars',          'warning'),
('first_module',  'Eerste les',       'Voltooide je eerste module',                 'bi-check-circle',   'success'),
('course_done',   'Diploma!',         'Voltooide een complete cursus',              'bi-mortarboard',    'warning'),
('streak_3',      'Op dreef',         '3 dagen op rij ingelogd',                    'bi-fire',           'danger'),
('streak_7',      'Week-strijder',    '7 dagen op rij ingelogd',                    'bi-fire',           'warning'),
('streak_30',     'Maand-master',     '30 dagen op rij ingelogd',                   'bi-trophy',         'warning'),
('xp_100',        'Centurion',        '100 XP verzameld',                           'bi-lightning',      'info'),
('xp_500',        'XP Power',         '500 XP verzameld',                           'bi-lightning-charge','warning'),
('xp_1000',       'XP Legend',        '1000 XP verzameld',                          'bi-gem',            'danger'),
('reviewer',      'Recensent',        'Schreef een review voor een cursus',         'bi-chat-quote',     'info');

INSERT INTO course_modules (course_id, title, description, position) VALUES
(1, 'Introductie', 'Maak kennis met de cursus en de leerdoelen.', 1),
(1, 'Kern concepten', 'De belangrijkste theorie en voorbeelden.', 2),
(1, 'Praktijk opdracht', 'Pas het geleerde toe in een opdracht.', 3),
(1, 'Eindopdracht', 'Lever het eindresultaat in.', 4),
(2, 'Introductie', 'Maak kennis met de cursus en de leerdoelen.', 1),
(2, 'Kern concepten', 'De belangrijkste theorie en voorbeelden.', 2),
(2, 'Praktijk opdracht', 'Pas het geleerde toe in een opdracht.', 3),
(2, 'Eindopdracht', 'Lever het eindresultaat in.', 4),
(3, 'Introductie', 'Maak kennis met de cursus en de leerdoelen.', 1),
(3, 'Kern concepten', 'De belangrijkste theorie en voorbeelden.', 2),
(3, 'Praktijk opdracht', 'Pas het geleerde toe in een opdracht.', 3),
(3, 'Eindopdracht', 'Lever het eindresultaat in.', 4),
(4, 'Introductie', 'Maak kennis met de cursus en de leerdoelen.', 1),
(4, 'Kern concepten', 'De belangrijkste theorie en voorbeelden.', 2),
(4, 'Praktijk opdracht', 'Pas het geleerde toe in een opdracht.', 3),
(4, 'Eindopdracht', 'Lever het eindresultaat in.', 4);