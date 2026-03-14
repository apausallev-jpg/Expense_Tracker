# 💰 Expense Tracker Web App

A full-featured CRUD web application built with **HTML, JavaScript, PHP, and PostgreSQL**.

---

## 📁 Project Structure

```
expense-tracker/
├── index.html          ← Main page (UI)
├── config.php          ← DB connection settings
├── database.sql        ← PostgreSQL schema + seed data
├── css/
│   └── style.css       ← Stylesheet
├── js/
│   └── app.js          ← Frontend logic (fetch, render, CRUD)
└── api/
    ├── expenses.php    ← CRUD endpoint (GET/POST/PUT/DELETE)
    ├── categories.php  ← List categories
    └── summary.php     ← Totals & category breakdown
```

---

## ⚙️ Setup Instructions

### 1. PostgreSQL — Create the Database

Open `psql` and run:

```sql
CREATE DATABASE expense_tracker;
\c expense_tracker
\i database.sql
```

Or via command line:

```bash
psql -U postgres -c "CREATE DATABASE expense_tracker;"
psql -U postgres -d expense_tracker -f database.sql
```

---

### 2. Configure `config.php`

Edit the constants at the top of `config.php`:

```php
define('DB_HOST',     'localhost');
define('DB_PORT',     '5432');
define('DB_NAME',     'expense_tracker');
define('DB_USER',     'postgres');       // ← your PostgreSQL username
define('DB_PASSWORD', 'your_password'); // ← your PostgreSQL password
```

---

### 3. Run a Local PHP Server

From the project folder:

```bash
php -S localhost:8000
```

Then open your browser at: **http://localhost:8000**

---

## 🧱 Database Design

### `categories` table

| Column     | Type         | Notes                   |
|------------|--------------|-------------------------|
| id         | SERIAL PK    | Auto-increment          |
| name       | VARCHAR(100) | Unique category name    |
| color      | VARCHAR(7)   | Hex color code          |
| created_at | TIMESTAMP    | Auto set on insert      |

### `expenses` table

| Column       | Type           | Notes                          |
|--------------|----------------|--------------------------------|
| id           | SERIAL PK      | Auto-increment                 |
| title        | VARCHAR(255)   | Name of the expense            |
| amount       | NUMERIC(10,2)  | Must be > 0                    |
| category_id  | INTEGER FK     | References categories(id)      |
| expense_date | DATE           | Date of the expense            |
| description  | TEXT           | Optional notes                 |
| created_at   | TIMESTAMP      | Auto set on insert             |
| updated_at   | TIMESTAMP      | Auto updated on change         |

---

## ✅ Features

- ➕ **Add** new expense records
- 📋 **View** all expenses in a sortable table
- ✏️ **Edit** existing expenses via modal
- 🗑️ **Delete** expenses with confirmation
- 🔍 **Filter** by search term, category, or month
- 📊 **Summary** — total, this month, by category
- 🎨 Color-coded categories

---

## 🛠 Technologies

| Layer     | Technology        |
|-----------|-------------------|
| Frontend  | HTML5, JavaScript |
| Backend   | PHP 8+            |
| Database  | PostgreSQL        |
| Styling   | CSS3 (no framework) |
