# STI DigiLibrary - Comprehensive Project Documentation

## Project Overview

**STI DigiLibrary** is a full-stack web-based library management system created as a course project for the **Application Development** subject. It provides digital library services with user authentication, book catalog management, borrowing workflows, and administrative functions. The system implements a PHP backend with a responsive HTML/CSS/JavaScript frontend, following security best practices and SOLID design principles.

## Architecture & Technology Stack

### Backend Architecture (PHP-based)

#### Core Technologies

- **Framework**: Custom PHP MVC architecture with modular design
- **Database**: MySQL with PDO for secure operations
- **Authentication**: Token-based system with reCAPTCHA integration
- **Environment Management**: Configurable settings for flexibility
- **Email Services**: Support for notifications
- **Security Features**:
  - Bcrypt password hashing
  - Account lockout mechanisms
  - Prepared statements (SQL injection prevention)
  - CSRF token protection
  - Input validation and sanitization
  - Session management with secure cookies

#### SOLID Principles Implementation

- **Single Responsibility**: Each class handles one concern
- **Open/Closed**: Extensible through interfaces
- **Liskov Substitution**: Base classes can be substituted with derived classes
- **Interface Segregation**: Specific interfaces for different functionalities
- **Dependency Inversion**: Dependencies injected through constructors

### Frontend Architecture

#### Technologies

- **HTML5**: Semantic markup with accessibility considerations
- **CSS3**: Responsive design with Grid/Flexbox
- **JavaScript (ES6+)**: Modular architecture with import/export
- **Responsive Design**: Mobile-first approach with breakpoints

#### Design System

- Consistent color palette
- Reusable components
- Typography scale
- Spacing system

## Key Features

### User Management

- Account registration with email verification
- Secure login with bcrypt password hashing
- Password recovery via email
- Role-based access (User/Admin)
- Profile management

### Book Management

- Browse book catalog
- Search functionality
- View book details
- Category filtering
- Availability status

### Borrowing System

- Request books
- Borrow duration tracking
- Return management
- Late fee calculation
- Borrowing history

### Admin Functions

- User management (CRUD)
- Book inventory management (CRUD)
- Transaction oversight
- Report generation
- System settings configuration

## System Workflow Example

### Book Borrowing Flow

```
User → Frontend → Backend → Database
1. User selects book
2. Borrow request sent to backend
3. Availability check
4. Create transaction record
5. Update book status
6. Send notification
7. Response to user → Success/Error message
```

## Security Practices

- Input validation and sanitization
- Token-based authentication with expiration
- Role-based access control (RBAC)
- Session timeout management
- HTTPS enforcement (production)
- XSS and CSRF protection
- Graceful error handling and logging

## Database Schema

Typical tables are used to support the system's functionality, including those for **users**, **books**, **borrows**, **admins**, and related supporting entities. These tables handle account management, catalog data, and transaction tracking in a way consistent with standard library management systems.

## Setup and Installation

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/your-username/STI-DigiLibrary.git
    cd STI-DigiLibrary
    ```

2.  **Backend Setup**
    - Ensure you have **PHP 8.0+** and **Composer** installed.
    - Navigate to the project root and install PHP dependencies:
      ```bash
      composer install
      ```
    - Create a `.env` file in the `server/` directory by copying the example file:
      ```bash
      cp server/.env.example server/.env
      ```
    - Update the `server/.env` file with your database credentials, SMTP server details for email, and Google reCAPTCHA keys.

3.  **Database Setup**
    - Create a MySQL database for the project.
    - Import the database schema from `database/schema.sql` into your newly created database.

4.  **Frontend Setup**
    - No special build steps are required for the frontend. The application is served directly through a PHP server (like XAMPP, WAMP, or MAMP).

5.  **Running the Application**
    - Place the entire project directory in the `htdocs` (for XAMPP) or `www` (for WAMP) folder of your local server.
    - Start your Apache and MySQL services.
    - Access the application in your browser, typically at `http://localhost/STI-DigiLibrary/frontend/html/login.html`.

## Usage

-   **Student Access**: Students can register, log in, browse the book and thesis catalogs, and manage their profiles.
-   **Admin Access**: Administrators have elevated privileges to manage users, books, and theses. Admin registration requires a special code generated by a superadmin.
-   **Superadmin Access**: The superadmin dashboard provides an overview of all users and includes a tool to generate single-use codes for new admin registrations.

## Development Guidelines

- Follow PSR-12 coding standards for PHP.
- Use JSDoc for documenting JavaScript code.
- Write clear and meaningful function, variable, and class names.
- Ensure all new features are thoroughly tested.

## Dependencies

### Backend Dependencies

- **PHP 8.0+** - Server-side scripting language
- **MySQL 5.7+** - Database management system
- **Composer** - PHP package manager
- **dompdf/dompdf (^3.1)** - HTML to PDF converter
- **vlucas/phpdotenv (^5.6)** - Environment variable loader
- **phpmailer/phpmailer (^7.0)** - Email sending functionality

### Frontend Dependencies

- **jQuery 3.6.0+** - JavaScript library for DOM manipulation
- **Bootstrap 5.2.0+** - Frontend framework for responsive design
- **Font Awesome 6.0+** - Icon toolkit
- **Google reCAPTCHA** - Bot protection for forms

### Development Dependencies

- **XAMPP/WAMP/MAMP** - Local development environment
- **Git** - Version control system

## License

This project is an **academic requirement** for the Application Development subject.

## Contributors

Developed by a student of STI College as part of coursework.
