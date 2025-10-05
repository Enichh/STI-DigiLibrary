# STI DigiLibrary - Comprehensive Project Analysis

## **Project Overview**

**STI DigiLibrary** is a full-stack web-based library management system designed for STI College. It provides digital library services with user authentication, book catalog management, and administrative functions. The system uses a modern PHP backend with a responsive HTML/CSS/JavaScript frontend.

## **Architecture & Technology Stack**

### **Backend (PHP-based)**

- **Framework**: Custom PHP MVC architecture (not using established frameworks like Laravel)
- **Database**: MySQL with PDO for secure database operations
- **Authentication**: JWT-like token system with reCAPTCHA integration
- **Environment Management**: PHP dotenv for configuration management
- **Email Services**: PHPMailer for transactional emails
- **Security Features**: Password hashing, account lockout, prepared statements

### **Frontend**

- **HTML5**: Semantic markup with responsive design
- **CSS3**: Modern styling with CSS Grid/Flexbox
- **JavaScript (ES6+)**: Modular architecture with import/export
- **External Dependencies**: Font Awesome icons, Google reCAPTCHA

## **Project Structure**

```
STI-DigiLibrary/
├── frontend/                    # Client-side application
│   ├── assets/                  # Static assets (images, icons)
│   ├── css/                     # Stylesheets
│   │   ├── adminDashboard.css
│   │   ├── catalog.css
│   │   ├── main.css
│   │   ├── modalAdmin.css
│   │   └── superadmin.css
│   ├── html/                    # HTML pages
│   │   ├── adminDashboard.html
│   │   ├── catalog.html
│   │   ├── login.html
│   │   └── superadmin.html
│   └── js/                      # JavaScript modules
│       ├── adminDashboard.js
│       ├── api.js
│       ├── authHandler.js
│       ├── catalog.js
│       ├── config.js
│       ├── main.js
│       ├── modal.js
│       ├── session.js
│       ├── superadmin.js
│       └── ui.js
├── server/                      # Backend API server
│   ├── config/                  # Configuration files
│   │   └── database.php         # PDO database connection
│   ├── controllers/             # Request handlers
│   │   ├── authController.php   # Authentication endpoints
│   │   └── userController.php   # User management endpoints
│   ├── models/                  # Data access layer
│   │   └── userModel.php        # User database operations
│   ├── services/                # Business logic layer
│   │   ├── authService.php      # Authentication business logic
│   │   ├── emailService.php     # Email sending services
│   │   └── userService.php      # User management logic
│   ├── routes/                  # Route definitions
│   │   ├── authRoutes.php       # Authentication routes
│   │   ├── configRoutes.php     # Configuration routes
│   │   └── userRoutes.php       # User management routes
│   ├── utils/                   # Utility functions
│   │   ├── authUtils.php        # Authentication utilities
│   │   └── validationUtils.php  # Input validation utilities
│   ├── public/                  # Public web root
│   │   ├── index.php            # Main entry point
│   │   ├── auth/                # Authentication sub-routes
│   │   ├── test.php             # Testing endpoints
│   │   └── users.php            # User endpoints
│   ├── vendor/                  # Composer dependencies (empty in current state)
│   └── composer.json            # PHP dependency management
└── Root Files
    ├── debug_path.php           # URL path debugging utility
    └── test_dotenv.php          # Environment testing utility
```

## **Key Features**

### **Authentication System**

- **Multi-role Support**: Students, Admins, SuperAdmins
- **Account Security**: Failed login attempt tracking with account lockout
- **Password Management**: Secure password reset with email verification
- **Two-Factor Authentication**: 6-digit verification codes for sensitive operations
- **reCAPTCHA Integration**: Bot protection for login/signup forms

### **User Interface Pages**

1. **Login Page** - Dual login (Student/Admin) with tabbed interface
2. **Catalog Page** - Book search and browsing interface
3. **Admin Dashboard** - Library statistics, user management, book management
4. **SuperAdmin Page** - Advanced administrative functions

### **Security Measures**

- **Input Validation**: Client and server-side validation
- **SQL Injection Protection**: PDO prepared statements throughout
- **XSS Prevention**: Proper input sanitization
- **CSRF Protection**: Session-based request validation
- **Password Security**: Bcrypt hashing with proper salt rounds
- **Rate Limiting**: Login attempt restrictions

### **Business Logic Layers**

#### **Controllers**

- Handle HTTP requests and responses
- Validate input data
- Call appropriate service methods
- Return JSON responses

#### **Services**

- Contain core business logic
- Handle complex operations (email sending, authentication flows)
- Interact with models for data operations

#### **Models**

- Pure data access layer
- PDO database operations
- No business logic contamination

## **Development Environment**

### **Server Configuration**

- **Web Server**: Apache
- **PHP Version**: Compatible with PHP 7.4+ (uses modern features)
- **Database**: MySQL 5.7+
- **URL Rewriting**: Clean URLs

### **Frontend Architecture**

- **Modular JavaScript**: ES6 modules with clear separation of concerns
- **Responsive Design**: Mobile-first CSS approach
- **Component-based UI**: Reusable modal systems and form handlers
- **State Management**: Session-based user state management

## **Deployment Considerations**

### **Dependencies**

- **PHP**: vlucas/phpdotenv, phpmailer/phpmailer
- **Frontend**: Font Awesome, Google reCAPTCHA API
- **Build Tools**: None (vanilla PHP/JS)

## **Code Quality & Standards**

### **PHP Standards**

- PSR-4 autoloading compatible structure
- Consistent naming conventions (camelCase)
- Proper error handling with try-catch blocks
- Secure coding practices (prepared statements, input validation)

### **JavaScript Standards**

- ES6+ modern syntax
- Modular architecture with clear imports/exports
- Consistent error handling
- Proper async/await usage for API calls

### **CSS Standards**

- BEM-like naming conventions
- Mobile-first responsive design
- Consistent color schemes and typography

---

This is a well-structured educational/institutional library management system with solid security foundations and modern web development practices.
