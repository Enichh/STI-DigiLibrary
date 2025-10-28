---

## STI DigiLibrary: Installation Guide

### Prerequisites

1. **Server Stack**:

   - XAMPP (includes Apache, MySQL/MariaDB, PHP) – [Download XAMPP](https://www.apachefriends.org/download.html)[1]
   - PHP 8.0+ (Recommended: 8.1 or 8.2 for best compatibility)
     - Enable extensions: PDO, MySQLi, OpenSSL, cURL
   - MySQL 5.7+ or MariaDB 10.3+ (included in XAMPP)

2. **Development Tools**:

   - Git – [Download Git](https://git-scm.com/downloads)
   - Composer (PHP dependency manager) – [Download Composer](https://getcomposer.org/download/)

3. **Recommended:**
   - Text editor (VSCode, Sublime Text, PhpStorm, etc.)
   - Latest Chrome/Firefox browser (for local project testing)

---

### Setup Steps

1. **Download and Install XAMPP**

   - Choose the correct XAMPP version for your OS and PHP requirements.[1]
   - Run the installer and launch the XAMPP Control Panel.
   - Start Apache and MySQL services (they should turn green if successful).

2. **Clone the Repository**

   ```bash
   git clone https://github.com/Enichh/STI-DigiLibrary.git
   cd STI-DigiLibrary
   ```

3. **Install PHP Dependencies**

   ```bash
   composer install
   ```

   This command fetches core dependencies:

   - `dompdf/dompdf` (^3.1)
   - `vlucas/phpdotenv` (^5.6)
   - `phpmailer/phpmailer` (^7.0)

4. **Database Setup**

   - Launch phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a database named `librarydb`
   - Import `librarydb.sql`

5. **Environment Configuration**

   - Copy `.env.example` to `.env`
   - Update credentials in `.env`:
     ```
     DB_HOST=localhost
     DB_PORT=3306
     DB_NAME=librarydb
     DB_USER=root
     DB_PASS=
     SMTP_USER=your_email@gmail.com
     SMTP_PASS=your_email_password
     ```
   - Confirm your SMTP credentials and sender details if using PHPMailer for notifications

6. **Run the Web Server**
   - Move or symlink the repository folder to your XAMPP `htdocs` directory (`C:\xampp\htdocs\STI-DigiLibrary`)
   - Access in browser: `http://localhost/STI-DigiLibrary`

---

### Additional Configuration

- **Enable Extensions:** Open `php.ini` (from XAMPP Control Panel → Config) and ensure these lines are uncommented:
  ```
  extension=pdo_mysql
  extension=openssl
  extension=curl
  ```

---
