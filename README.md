# GUVI Internship Task - User Authentication System

A complete user authentication system with registration, login, and profile management using MongoDB Atlas, Aiven MySQL, Upstash Redis, and PHP.

## Features

- **User Registration** - Create account with name, email, password
- **User Login** - Authenticate with email/password, receive token
- **Profile Management** - Update age, DOB, contact, address
- **Session Management** - Token-based auth via Upstash Redis with auto-expiry
- **Responsive UI** - Modern design with Bootstrap 5, works on all devices

## Tech Stack

| Component | Technology | Purpose |
|-----------|------------|---------|
| Frontend | HTML, CSS, JavaScript, Bootstrap 5, jQuery | Responsive UI |
| Backend | PHP 8.5 (Homebrew) | Server-side logic |
| User Data | Aiven MySQL (Cloud) | Stores id, hash, email, password, createdAt |
| Profile Data | MongoDB Atlas (Cloud) | Stores userId, age, dob, address, phone |
| Session | Upstash Redis (Cloud) | Stores auth tokens with TTL |
| Auth | Token-based (localStorage) | No PHP sessions |

## Folder Structure

```
Guvi-Task/
├── .env                    # Database credentials (not in git)
├── composer.json           # PHP dependencies
├── schema.sql              # MySQL table schema
├── index.html              # Landing page
├── register.html           # Registration page
├── login.html              # Login page
├── profile.html            # Profile page
├── css/
│   ├── index.css           # Landing page styles
│   ├── register.css        # Registration styles
│   ├── login.css           # Login styles
│   └── profile.css         # Profile styles
├── js/
│   ├── register.js         # Registration form handler
│   ├── login.js            # Login form handler
│   └── profile.js          # Profile form handler
└── php/
    ├── config.php          # Environment loader
    ├── register.php        # Registration endpoint
    ├── login.php           # Login endpoint
    ├── profile.php         # Profile endpoint
    └── db/
        ├── mongo.php       # MongoDB Atlas connection
        ├── mysql.php       # Aiven MySQL connection (SSL)
        └── redis.php       # Upstash Redis connection (TLS)
```

## Architecture

```
┌─────────────┐    AJAX/JSON    ┌─────────────┐
│   Browser   │ ◄─────────────► │   PHP API   │
│ (localStorage)│               │             │
└─────────────┘                 └──────┬──────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    ▼                  ▼                  ▼
            ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
            │  MongoDB    │    │    MySQL    │    │    Redis    │
            │   Atlas     │    │   (Aiven)   │    │  (Upstash)  │
            │             │    │             │    │             │
            │ user_profiles│   │   users     │    │   tokens    │
            └─────────────┘    └─────────────┘    └─────────────┘
```

## Data Flow

### Registration
1. User submits name, email, password
2. Password hashed with `sha512`
3. User record created in MySQL (`users` table) - stores id, hash, email, password, createdAt
4. Redirect to login

### Login
1. User submits email, password
2. Find user in MySQL by email
3. Verify password with `hash_equals` (sha512)
4. Generate random token, store session in Upstash Redis with 24-hour TTL
5. Return token to client, store in localStorage

### Profile Access
1. Client sends token in request
2. Server validates token in Upstash Redis
3. Fetch user from MySQL, profile from MongoDB
4. Return combined data to client

### Profile Update
1. Client sends updated profile data with token
2. Server validates token in Upstash Redis
3. Upsert profile document in MongoDB (`user_profiles` collection) - stores userId, age, dob, address, phone

### Logout
1. Delete token from Redis
2. Clear localStorage
3. Redirect to login

## Prerequisites

- **PHP 8.2+** with extensions: `mysqli`, `mongodb`, `redis`
- **Composer** for PHP dependencies
- **Upstash Redis** account (cloud)
- **MongoDB Atlas** account (cloud)
- **Aiven MySQL** account (cloud)

## Installation

### 1. Clone the repository

```bash
git clone <repo-url>
cd Guvi-Task
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create `.env` file

```env
# MongoDB Atlas
MONGO_URI=mongodb+srv://username:password@cluster.mongodb.net/?tlsAllowInvalidCertificates=true
MONGO_DB=guvi_task

# Aiven MySQL
MYSQL_HOST=mysql-host.aivencloud.com
MYSQL_PORT=23120
MYSQL_USER=avnadmin
MYSQL_PASSWORD=your-password
MYSQL_DB=defaultdb

# Upstash Redis
REDIS_URL=rediss://default:your-password@your-instance.upstash.io:6379
```

### 4. Create MySQL table

Connect to Aiven MySQL and run:

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hash VARCHAR(128) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(128) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_hash (hash)
);
```

### 5. Configure MongoDB Atlas

1. Go to MongoDB Atlas Dashboard
2. Navigate to **Network Access**
3. Add IP: `0.0.0.0/0` (Allow Access from Anywhere) or your specific IP

### 6. Start PHP server

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Guvi-Task
php -S localhost:8000
```

### 7. Access the application

Open browser: `http://localhost:8000`

## API Endpoints

### POST `/php/register.php`

Register a new user.

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "ok": true,
  "message": "Registration successful"
}
```

### POST `/php/login.php`

Login and receive auth token.

**Request:**
```json
{
  "email": "john@example.com",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "ok": true,
  "token": "abc123...",
  "name": "John Doe",
  "email": "john@example.com"
}
```

### POST `/php/profile.php`

Get or update profile. Requires `token` in all requests.

**Get Profile:**
```json
{
  "action": "get",
  "token": "abc123..."
}
```

**Response:**
```json
{
  "ok": true,
  "name": "John Doe",
  "email": "john@example.com",
  "profile": {
    "age": 25,
    "dob": "1999-01-15",
    "contact": "+91-9876543210",
    "address": "Chennai, India"
  }
}
```

**Update Profile:**
```json
{
  "action": "update",
  "token": "abc123...",
  "name": "John Doe",
  "age": 26,
  "dob": "1999-01-15",
  "contact": "+91-9876543210",
  "address": "Updated address"
}
```

**Logout:**
```json
{
  "action": "logout",
  "token": "abc123..."
}
```

## Security Features

- Passwords hashed with `sha512`
- Prepared statements for all MySQL queries
- Token-based authentication (no session fixation)
- Upstash Redis tokens auto-expire after 24 hours
- Credentials stored in `.env` (excluded from git)
- SSL/TLS for MongoDB Atlas, Aiven MySQL, and Upstash Redis connections

## UI Design

- **Landing Page** - Hero section, feature cards, tech stack display, data flow diagram
- **Register/Login** - Split-card design with gradient visual panel, floating labels, icons
- **Profile** - Header card with avatar, session badge, organized form sections

## Development Notes

- Uses Homebrew PHP 8.5 instead of XAMPP PHP (XAMPP's OpenSSL 1.1.1 incompatible with MongoDB Atlas TLS)
- All AJAX requests send JSON body (not form-urlencoded)
- MySQL stores user credentials (id, hash, email, password), MongoDB stores profile data (userId, age, dob, address, phone)
- Upstash Redis provides fast token lookup with automatic expiration (TLS enabled)

## Troubleshooting

### MongoDB Connection Error
- Check IP whitelist in Atlas Dashboard
- Verify connection string in `.env`
- Ensure `mongodb` PHP extension is enabled

### MySQL Connection Error
- Verify Aiven service is running
- Check SSL certificate configuration
- Confirm port 23120 (not default 3306)

### Upstash Redis Connection Error
- Verify `REDIS_URL` in `.env` matches Upstash dashboard
- Ensure using `rediss://` scheme (TLS required)
- Check Upstash service is active in dashboard

### PHP Extensions
```bash
php -m | grep -E 'mysqli|mongodb|redis'
```

## License

This project is for educational purposes as part of the GUVI Internship Task.