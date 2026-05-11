# GUVI Internship Task (Register → Login → Profile)

## Folder structure (matches your screenshot)

```
assets/
css/
  style.css
js/
  login.js
  profile.js
  register.js
php/
  login.php
  profile.php
  register.php
index.html
login.html
profile.html
register.html
schema.sql
```

## Tech used (as required by PDF)

- **HTML/CSS/JS/PHP**: Separate files (no mixing)
- **Bootstrap**: Responsive forms
- **jQuery AJAX**: Only way the frontend talks to backend (no form submit)
- **MongoDB**: Stores registration data (name/email/password_hash)
- **MySQL**: Stores profile details (age/dob/contact/address)
- **Redis**: Stores backend session (token → user_id)
- **Browser localStorage**: Stores only the token (no PHP sessions)

## Step-by-step: How to run (Windows)

### 1) Install required services

- **MySQL** (server running)
- **MongoDB** (server running)
- **Redis** (server running)
- **PHP 8+**
- **Composer**

### 2) Create the MySQL table

Open a MySQL client (MySQL Workbench / CLI) and run:

```sql
SOURCE schema.sql;
```

This creates database `guvi_task` and table `user_profiles`.

### 3) Install PHP MongoDB library

In the project root (`D:\KARTHIKA\Guvi-Task`) run:

```bash
composer install
```

### 4) Enable PHP extensions

Make sure these are enabled in your `php.ini`:

- `extension=mysqli`
- `extension=mongodb`  (MongoDB driver)
- `extension=redis`    (phpredis)

Restart any PHP/Apache service after enabling extensions.

### 5) Configure DB/Redis connection settings (if needed)

If your local credentials differ, edit these files:

- `php/register.php` (MongoDB + MySQL)
- `php/login.php` (MongoDB + Redis)
- `php/profile.php` (MongoDB + MySQL + Redis)

Look for the section:
`// ---- Configuration (edit these to match your local setup) ----`

### 6) Start the PHP server

From the project root:

```bash
php -S 127.0.0.1:8000
```

### 7) Open the app

In your browser open:

- `http://127.0.0.1:8000/index.html`

### 8) Test the required flow

- Go to **Register**, create an account
- Go to **Login**, login with the same credentials
- You’ll be redirected to **Profile**
- Update age/dob/contact/address and save
- Logout clears localStorage token and deletes session from Redis

## Notes for the evaluator

- **No form submission** is used (`type="button"` + jQuery AJAX).
- **MySQL uses prepared statements only** (`prepare()` + `bind_param()`).
- **No PHP sessions** are used; session is **token in localStorage**, validated via **Redis**.

