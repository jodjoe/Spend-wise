# 🇪🇹 Birr Wise — Ethiopian Student Budget Tracker

A web-based budget and expense tracking system built for Ethiopian
university students. Features AI-powered SMS parsing via Google Gemini,
smart spending insights, and a personal AI financial assistant.

---

## Requirements
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache (XAMPP recommended)
- A Google Gemini API key (free at https://aistudio.google.com)

---

## Setup Instructions

### Step 1 — Import the database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "Import"
3. Select `database.sql` from the project folder
4. Click "Go"
5. Database `birr_wise` will be created with all tables and sample data

If your MariaDB root account requires a password, run this SQL after import instead of using root in `config.php`:

```sql
CREATE USER IF NOT EXISTS 'birr_wise_user'@'localhost' IDENTIFIED BY 'Spendwise123!';
GRANT ALL PRIVILEGES ON birr_wise.* TO 'birr_wise_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2 — Configure the app
1. Open `config.php` in the project root
2. Update database credentials if needed:
	- DB_HOST: usually 'localhost'
	- DB_USER: birr_wise_user
	- DB_PASS: Spendwise123!
	- If you keep `root`, set DB_PASS to your MariaDB root password instead
3. Add your Gemini API key:
	- Get a free key at https://aistudio.google.com/app/apikey
	- Replace 'YOUR_GEMINI_API_KEY_HERE' with your key

### Step 3 — Place project files
1. Copy the `birr-wise` folder to your web server root:
	- XAMPP: C:/xampp/htdocs/birr-wise
	- WAMP: C:/wamp64/www/birr-wise
	- Linux: /var/www/html/birr-wise

### Step 4 — Run the app
1. Start Apache and MySQL in XAMPP
2. Open http://localhost/birr-wise
3. You will be redirected to the login page

---

## Test Accounts
All accounts use password: **password**

| Name | Email | Allowance | Data |
|---|---|---|---|
| Abebe Kebede | abebe@test.com | 3000 ETB | 3 months |
| Tigist Haile | tigist@test.com | 3000 ETB | 2 months |
| Dawit Mengistu | dawit@test.com | 3500 ETB | 1 month |

---

## Features
- Register and login with secure session management
- 3-step onboarding — set allowance, pick categories, first budget
- Log expenses manually or by pasting Telebirr/CBE SMS (AI reads it)
- Set monthly/weekly budgets per category with progress tracking
- Smart dashboard — daily limit, pace indicator, month prediction
- 4 analytics charts — category, weekly, budget vs actual, daily trend
- Monthly history — view and compare any past month
- AI automated spending analysis (cached daily, powered by Gemini)
- AI chat assistant — ask about your finances or how to use the app
- Smart popup notifications after every expense
- Mobile-first responsive design
- Full security — PDO, CSRF, XSS protection, password hashing

---

## Project Structure
See MASTER_PROMPT.md for full technical documentation.

---

## Technologies
- Frontend: HTML5, CSS3, JavaScript (vanilla)
- Backend: PHP 8 (vanilla)
- Database: MySQL 8 with PDO
- AI: Google Gemini API (gemini-2.0-flash)
- Charts: Chart.js
- Icons: Font Awesome