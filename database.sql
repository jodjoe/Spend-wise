-- ============================================================
-- Birr Wise — Ethiopian Student Budget Tracking System
-- Database Export
-- Course: Web Programming
-- ============================================================

-- Drop and recreate database
DROP DATABASE IF EXISTS birr_wise;
CREATE DATABASE birr_wise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE birr_wise;

-- Create a dedicated app database user for local development
CREATE USER IF NOT EXISTS 'birr_wise_user'@'localhost' IDENTIFIED BY 'Spendwise123!';
GRANT ALL PRIVILEGES ON birr_wise.* TO 'birr_wise_user'@'localhost';

-- ── TABLE 1: users ──────────────────────────────────────────
-- Stores student accounts
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed with password_hash()',
    monthly_allowance DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
    onboarding_complete TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TABLE 2: categories ─────────────────────────────────────
-- Expense categories per user
-- is_default = 1 means student cannot delete it
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(10) DEFAULT '💰',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TABLE 3: expenses ───────────────────────────────────────
-- Every transaction logged by student
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TABLE 4: budgets ────────────────────────────────────────
-- Spending limits per category per period
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    period ENUM('weekly','monthly') NOT NULL DEFAULT 'monthly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_budget (user_id, category_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TABLE 5: ai_analysis ────────────────────────────────────
-- Cached AI spending analysis per user (one per user, refreshed daily)
CREATE TABLE ai_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    analysis_text TEXT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- All seeded accounts use password: "password"
-- ============================================================

-- ── USERS ───────────────────────────────────────────────────
INSERT INTO users (name, email, password, monthly_allowance, onboarding_complete) VALUES
('Abebe Kebede',   'abebe@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3000.00, 1),
('Tigist Haile',   'tigist@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3000.00, 1),
('Dawit Mengistu', 'dawit@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3500.00, 1);

-- ── DEFAULT CATEGORIES — Abebe (user_id = 1) ────────────────
INSERT INTO categories (user_id, name, icon, is_default) VALUES
(1, 'Food',           '🍛', 1),
(1, 'Transport',      '🚌', 1),
(1, 'Clothing',       '👕', 1),
(1, 'School Supplies','📚', 1),
(1, 'Mobile Top-up',  '📱', 1),
(1, 'Café',           '☕', 1),
(1, 'Entertainment',  '🎮', 1),
(1, 'Health',         '🏥', 1),
(1, 'Other',          '🔀', 1),
(1, 'Gym',            '🏋️', 0);

-- ── DEFAULT CATEGORIES — Tigist (user_id = 2) ───────────────
INSERT INTO categories (user_id, name, icon, is_default) VALUES
(2, 'Food',           '🍛', 1),
(2, 'Transport',      '🚌', 1),
(2, 'Clothing',       '👕', 1),
(2, 'School Supplies','📚', 1),
(2, 'Mobile Top-up',  '📱', 1),
(2, 'Café',           '☕', 1),
(2, 'Entertainment',  '🎮', 1),
(2, 'Health',         '🏥', 1),
(2, 'Other',          '🔀', 1);

-- ── DEFAULT CATEGORIES — Dawit (user_id = 3) ────────────────
INSERT INTO categories (user_id, name, icon, is_default) VALUES
(3, 'Food',           '🍛', 1),
(3, 'Transport',      '🚌', 1),
(3, 'Clothing',       '👕', 1),
(3, 'School Supplies','📚', 1),
(3, 'Mobile Top-up',  '📱', 1),
(3, 'Café',           '☕', 1),
(3, 'Entertainment',  '🎮', 1),
(3, 'Health',         '🏥', 1),
(3, 'Other',          '🔀', 1),
(3, 'Salon',          '💇', 0);

-- ── BUDGETS — Abebe ─────────────────────────────────────────
-- category IDs for Abebe: Food=1, Transport=2, Café=6
INSERT INTO budgets (user_id, category_id, amount, period) VALUES
(1, 1, 800.00,  'monthly'),
(1, 2, 200.00,  'monthly'),
(1, 6, 300.00,  'monthly');

-- ── BUDGETS — Tigist ───────────────────────────────────────
-- category IDs for Tigist: Food=11, Entertainment=17
INSERT INTO budgets (user_id, category_id, amount, period) VALUES
(2, 11, 600.00, 'monthly'),
(2, 17, 200.00, 'monthly');

-- ── BUDGETS — Dawit ─────────────────────────────────────────
-- category IDs for Dawit: Food=20, Clothing=22
INSERT INTO budgets (user_id, category_id, amount, period) VALUES
(3, 20, 700.00, 'monthly'),
(3, 22, 400.00, 'monthly');

-- ── EXPENSES — Abebe (3 months) ─────────────────────────────
-- Generate 25-30 expenses spread across 3 months
-- Use realistic ETB amounts for Ethiopian student context
-- Dates: current month, last month, 2 months ago
-- category_id references Abebe's categories (1-10)

-- Current month expenses
INSERT INTO expenses (user_id, category_id, amount, note, expense_date) VALUES
(1, 1,  45.00,  'Lunch at student cafeteria',    DATE_FORMAT(NOW(), '%Y-%m-02')),
(1, 2,  8.00,   'Minibus to campus',              DATE_FORMAT(NOW(), '%Y-%m-02')),
(1, 6,  35.00,  'Morning bunna with friends',     DATE_FORMAT(NOW(), '%Y-%m-03')),
(1, 5,  100.00, 'Ethio Telecom top-up',           DATE_FORMAT(NOW(), '%Y-%m-04')),
(1, 1,  60.00,  'Dinner with roommate',           DATE_FORMAT(NOW(), '%Y-%m-05')),
(1, 2,  15.00,  'Minibus fare weekend',           DATE_FORMAT(NOW(), '%Y-%m-06')),
(1, 4,  55.00,  'Exercise books and pens',        DATE_FORMAT(NOW(), '%Y-%m-07')),
(1, 1,  40.00,  'Breakfast and lunch',            DATE_FORMAT(NOW(), '%Y-%m-08')),
(1, 6,  25.00,  'Café study session',             DATE_FORMAT(NOW(), '%Y-%m-09')),
(1, 10, 150.00, 'Monthly gym membership',         DATE_FORMAT(NOW(), '%Y-%m-10')),
(1, 1,  50.00,  'Food market supplies',           DATE_FORMAT(NOW(), '%Y-%m-11')),
(1, 2,  12.00,  'Transport to town',              DATE_FORMAT(NOW(), '%Y-%m-12')),
(1, 7,  80.00,  'Movie and snacks',               DATE_FORMAT(NOW(), '%Y-%m-13')),
(1, 4,  120.00, 'Textbook for semester',          DATE_FORMAT(NOW(), '%Y-%m-14')),
(1, 1,  35.00,  'Lunch',                          DATE_FORMAT(NOW(), '%Y-%m-15'));

-- Last month expenses
INSERT INTO expenses (user_id, category_id, amount, note, expense_date) VALUES
(1, 1,  55.00,  'Weekly food budget',             DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-05')),
(1, 2,  20.00,  'Bus fare week',                  DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-06')),
(1, 6,  40.00,  'Café meetup',                    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-08')),
(1, 3,  250.00, 'New shirt from market',          DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-10')),
(1, 5,  50.00,  'Telebirr recharge',              DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-12')),
(1, 1,  70.00,  'Food for the week',              DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-15')),
(1, 4,  85.00,  'Printing and stationery',        DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-18')),
(1, 8,  60.00,  'Pharmacy — cold medicine',       DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-20')),
(1, 2,  18.00,  'Weekend transport',              DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-22')),
(1, 1,  45.00,  'Dinner',                         DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-25'));

-- 2 months ago expenses
INSERT INTO expenses (user_id, category_id, amount, note, expense_date) VALUES
(1, 1,  65.00,  'Food week 1',                    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-03')),
(1, 6,  30.00,  'Bunna',                          DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-05')),
(1, 2,  25.00,  'Transport month',                DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-08')),
(1, 3,  180.00, 'Shoes from merkato',             DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-10')),
(1, 5,  100.00, 'Phone top-up',                   DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-12')),
(1, 4,  95.00,  'Books for exam prep',            DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-15')),
(1, 7,  120.00, 'Weekend entertainment',          DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-18')),
(1, 1,  50.00,  'Food',                           DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-22'));

-- ── EXPENSES — Tigist (2 months) ────────────────────────────
-- category IDs for Tigist start at 11
-- Food=11, Transport=12, Clothing=13, School=14, Mobile=15, Café=16, Entertainment=17, Health=18, Other=19

INSERT INTO expenses (user_id, category_id, amount, note, expense_date) VALUES
-- Current month
(2, 11, 50.00,  'Lunch',                          DATE_FORMAT(NOW(), '%Y-%m-03')),
(2, 12, 10.00,  'Minibus',                        DATE_FORMAT(NOW(), '%Y-%m-04')),
(2, 16, 30.00,  'Coffee with classmates',         DATE_FORMAT(NOW(), '%Y-%m-05')),
(2, 15, 75.00,  'Mobile recharge',                DATE_FORMAT(NOW(), '%Y-%m-06')),
(2, 11, 45.00,  'Food',                           DATE_FORMAT(NOW(), '%Y-%m-08')),
(2, 17, 90.00,  'Cinema and snacks',              DATE_FORMAT(NOW(), '%Y-%m-10')),
(2, 14, 65.00,  'Stationery',                     DATE_FORMAT(NOW(), '%Y-%m-12')),
(2, 11, 55.00,  'Groceries',                      DATE_FORMAT(NOW(), '%Y-%m-14')),
(2, 13, 200.00, 'Dress for occasion',             DATE_FORMAT(NOW(), '%Y-%m-15')),
-- Last month
(2, 11, 60.00,  'Weekly food',                    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-05')),
(2, 12, 15.00,  'Transport',                      DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-07')),
(2, 16, 45.00,  'Café afternoon',                 DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-10')),
(2, 17, 75.00,  'Weekend fun',                    DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-12')),
(2, 15, 100.00, 'Telebirr top-up',                DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-15')),
(2, 11, 40.00,  'Lunch and dinner',               DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-18')),
(2, 18, 80.00,  'Doctor visit',                   DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-20')),
(2, 14, 55.00,  'Paper and printing',             DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-23'));

-- ── EXPENSES — Dawit (1 month) ──────────────────────────────
-- category IDs for Dawit start at 20
-- Food=20, Transport=21, Clothing=22, School=23, Mobile=24, Café=25, Entertainment=26, Health=27, Other=28, Salon=29

INSERT INTO expenses (user_id, category_id, amount, note, expense_date) VALUES
(3, 20, 70.00,  'Food week 1',                    DATE_FORMAT(NOW(), '%Y-%m-02')),
(3, 21, 12.00,  'Minibus to class',               DATE_FORMAT(NOW(), '%Y-%m-03')),
(3, 25, 40.00,  'Bunna and snack',                DATE_FORMAT(NOW(), '%Y-%m-04')),
(3, 24, 100.00, 'Phone credit',                   DATE_FORMAT(NOW(), '%Y-%m-05')),
(3, 22, 350.00, 'New jeans and shirt',            DATE_FORMAT(NOW(), '%Y-%m-07')),
(3, 20, 55.00,  'Lunch this week',                DATE_FORMAT(NOW(), '%Y-%m-08')),
(3, 29, 120.00, 'Hair salon',                     DATE_FORMAT(NOW(), '%Y-%m-09')),
(3, 23, 75.00,  'Textbook',                       DATE_FORMAT(NOW(), '%Y-%m-10')),
(3, 26, 100.00, 'Entertainment weekend',          DATE_FORMAT(NOW(), '%Y-%m-11')),
(3, 20, 60.00,  'Groceries',                      DATE_FORMAT(NOW(), '%Y-%m-12')),
(3, 21, 20.00,  'Weekend transport',              DATE_FORMAT(NOW(), '%Y-%m-13')),
(3, 25, 35.00,  'Study café session',             DATE_FORMAT(NOW(), '%Y-%m-14')),
(3, 27, 90.00,  'Pharmacy',                       DATE_FORMAT(NOW(), '%Y-%m-15'));

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_expenses_user_id ON expenses(user_id);
CREATE INDEX idx_expenses_category_id ON expenses(category_id);
CREATE INDEX idx_expenses_date ON expenses(expense_date);
CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_budgets_user_id ON budgets(user_id);
