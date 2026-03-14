-- ============================================
-- Expense Tracker Web App - PostgreSQL Schema
-- ============================================

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id           SERIAL PRIMARY KEY,
    full_name    VARCHAR(150) NOT NULL,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    avatar       VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    color      VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, color) VALUES
    ('Food & Dining',    '#e74c3c'),
    ('Transportation',   '#3498db'),
    ('Housing',          '#2ecc71'),
    ('Healthcare',       '#e91e63'),
    ('Entertainment',    '#9b59b6'),
    ('Shopping',         '#f39c12'),
    ('Education',        '#1abc9c'),
    ('Utilities',        '#34495e'),
    ('Travel',           '#e67e22'),
    ('Other',            '#95a5a6');

-- Expenses table (now linked to user)
CREATE TABLE expenses (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title        VARCHAR(255) NOT NULL,
    amount       NUMERIC(10, 2) NOT NULL CHECK (amount > 0),
    category_id  INTEGER NOT NULL REFERENCES categories(id) ON DELETE SET NULL,
    expense_date DATE NOT NULL DEFAULT CURRENT_DATE,
    description  TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_expenses_user     ON expenses(user_id);
CREATE INDEX idx_expenses_date     ON expenses(expense_date DESC);
CREATE INDEX idx_expenses_category ON expenses(category_id);

-- Auto-update updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_expenses_updated_at
    BEFORE UPDATE ON expenses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();
