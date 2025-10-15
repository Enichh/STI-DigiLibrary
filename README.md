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
- **External Dependencies**:  
  - Font Awesome icons  
  - Google reCAPTCHA  

#### Design Patterns
- Module pattern for organization  
- Async/await for API communications  
- Event-driven architecture  
- Separation of concerns (API layer, UI layer, business logic)  

## Key Features

### User Features
- **Authentication System**: Secure login, reCAPTCHA, password reset  
- **Book Catalog**: Browse, search, filter, and view availability  
- **Borrowing System**: Request, track, and return books with due date notifications  

### Admin Features
- **Book Management**: Add, edit, delete, and categorize books  
- **User Management**: Manage accounts and roles  
- **Transaction Management**: Approve/reject requests, process returns, generate reports  

### Super Admin Features
- **System Administration**: Manage admin accounts, configure system settings, view logs  

## Data Flow & Integration Points

### Authentication Flow
```
1. User submits credentials → Frontend
2. Request sent via API layer
3. Backend validates request
4. Database verification
5. Token generation
6. Response with token → Frontend
7. Token stored securely
8. Subsequent requests include token in headers
```

### Book Borrowing Flow
```
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

## Development Guidelines
- Follow PSR-12 coding standards for PHP  
- Use ESLint for JavaScript  
- Meaningful variable and function names  
- Comprehensive code comments  
- Feature branch workflow with clear commit messages  

## Future Enhancements
- Mobile application (React Native/Flutter)  
- Advanced search with filters  
- Book recommendation system  
- E-book reader integration  
- Analytics dashboard  
- Multi-language support  
- Push notifications  
- API rate limiting  
- Caching implementation (Redis)  

## License
This project is an **academic requirement** for the Application Development subject.  

## Contributors
Developed by a student of STI College as part of coursework.
