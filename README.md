# STI DigiLibrary - Library Management System

## Project Overview

**STI DigiLibrary** is a full-stack web-based library management system created as a course project for the **Application Development** subject. It provides digital library services with user authentication, book catalog management, borrowing workflows, and administrative functions.

## Technology Stack

### Backend

- **PHP 8.0+** with custom MVC architecture
- **MySQL 5.7+** with PDO for secure database operations
- **Composer** for dependency management
- **PHPMailer** for email notifications

### Frontend

- **HTML5** with semantic markup
- **CSS3** with responsive design (Grid/Flexbox)
- **JavaScript (ES6+)** with modular architecture
- **jQuery 3.6.0+** for DOM manipulation
- **Bootstrap 5.2.0+** for responsive layout
- **Font Awesome 6.0+** for icons

### Security Features

- Bcrypt password hashing
- Token-based authentication with JWT
- CSRF protection
- Input validation and sanitization
- reCAPTCHA integration
- Role-based access control (RBAC)
- Secure session management

## Key Features

### User Management

- Account registration with email verification
- Secure login with password recovery
- Role-based access (Student/Admin)
- Profile management

### Book Management

- Browse and search book catalog
- Book details with availability status
- Category and advanced filtering

### Borrowing System

- Book request and checkout
- Due date tracking
- Return management
- Late fee calculation
- Borrowing history

### Admin Dashboard

- User management (CRUD)
- Book inventory management
- Transaction monitoring
- Report generation
- System configuration

## Quick Start

### Prerequisites

- PHP 8.0+ with PDO, MySQLi, OpenSSL, cURL extensions
- MySQL 5.7+ or MariaDB 10.3+
- Composer (PHP package manager)
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/your-username/STI-DigiLibrary.git
   cd STI-DigiLibrary
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Set up the database**

   - Create a new MySQL database named `librarydb`
   - Import the provided `librarydb.sql` file using one of these methods:

     **Using phpMyAdmin:**

     1. Log in to phpMyAdmin
     2. Create a new database named `librarydb`
     3. Select the database
     4. Click "Import"
     5. Choose the `librarydb.sql` file
     6. Click "Go" to import

     **Using MySQL Command Line:**

     ```bash
     # Create the database
     mysql -u root -p -e "CREATE DATABASE librarydb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

     # Import the SQL file
     mysql -u root -p librarydb < path/to/your/librarydb.sql
     ```

4. **Configure environment**

   ```bash
   cp server/.env.example server/.env
   ```

   Update `.env` with your database credentials and other settings.

5. **Run the application**
   - Place the project in your web server's root directory (e.g., `htdocs` or `www`)
   - Start your web server and MySQL
   - Access the application at `http://localhost/STI-DigiLibrary/frontend/html/login.html`

## User Roles

### Student

- Browse and search books/theses
- Manage personal profile
- View borrowing history
- Request book checkouts

### Admin

- All student privileges
- Manage book inventory
- Process checkouts/returns
- Generate reports
- Manage user accounts (except other admins)

## Development

### Code Style

- **PHP**: Follow PSR-12 standards
- **JavaScript**:
  - Use ES6+ features
  - Follow Airbnb style guide
  - Use JSDoc for documentation
- **Git**:
  - Branch naming: `feature/name`, `fix/name`, `docs/name`
  - Write clear, concise commit messages
  - Keep commits atomic

### Testing

- Write unit tests for new features
- Test all user flows
- Verify cross-browser compatibility
- Test on mobile devices

### Documentation

- Update README for major changes
- Document API endpoints
- Add JSDoc to all functions
- Keep inline comments clear and concise

## Environment Variables

Required `.env` configuration:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=librarydb
DB_USER=your_db_user
DB_PASS=your_db_password

# App
APP_ENV=development
SESSION_SECRET=your-secret-key

# Email (SMTP)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your-email@example.com
SMTP_PASS=your-email-password

# reCAPTCHA
RECAPTCHA_SITE_KEY=your-site-key
RECAPTCHA_SECRET=your-secret-key
```

## Troubleshooting

### Common Issues

1. **Database Connection**

   - Verify MySQL service is running
   - Check `.env` credentials
   - Ensure database user has proper permissions

2. **File Permissions**

   ```bash
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

3. **Dependencies**

   ```bash
   composer install --no-scripts
   composer update --no-scripts
   ```

4. **Frontend Assets**
   - Clear browser cache
   - Verify web server configuration
   - Check browser console for errors

## Database Schema

### Core Tables

#### Users & Authentication

- `tbl_users` - User accounts and authentication
- `tbl_roles` - User roles and permissions
- `tbl_studentdetails` - Student information
- `tbl_programs` - Academic programs

#### Book Management

- `tbl_books` - Book metadata
- `tbl_authors` - Author information
- `tbl_publishers` - Publisher details
- `tbl_book_authors` - Book-author relationships
- `tbl_book_copies` - Physical book copies
- `tbl_call_number` - Library classification

#### Borrowing System

- `tbl_borrowing_records` - Checkouts/returns
- `tbl_fines` - Overdue/lost item fines
- `tbl_fine_payments` - Payment records

#### Theses

- `tbl_theses` - Academic theses

### Key Relationships

- Books Authors: Many-to-many
- Books Copies: One-to-many
- Users Borrowing Records: One-to-many
- Borrowing Records Fines: One-to-one

## License

This project is an academic requirement for the Application Development subject at STI College.

## Credits

Developed by Enoch, James, Angelavianca, and Princess as part of coursework.

---

_Last updated: October 2025_
