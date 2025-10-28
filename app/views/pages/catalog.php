<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    // Check if test mode is enabled via URL parameter
    if (isset($_GET['test']) && $_GET['test'] === '1') {
        // Test mode: simulate login session with student details
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Test User';
        $_SESSION['user_email'] = 'test@example.com';
        $_SESSION['role'] = 'student';
        $_SESSION['student_number'] = '02000123456';
        $_SESSION['full_name'] = 'John Smith Doe Jr.';
    } else {
        header('Location: /login');
        exit;
    }
}

// Get user data from session
$fullName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User';
// For greeting: use first name only
$userName = htmlspecialchars(trim(explode(' ', $fullName)[0]), ENT_QUOTES, 'UTF-8');
// For library ID: use full name
$fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8');
$studentNumber = htmlspecialchars($_SESSION['student_number'] ?? 'Not Available', ENT_QUOTES, 'UTF-8');
$userProfilePic = '/assets/images/owlie_icn_transparent.png'; // Default profile picture

// Pass user data to JavaScript
$userData = [
    'userId' => $_SESSION['user_id'] ?? null,
    'userName' => $fullName,  // Full name for library ID
    'studentId' => $studentNumber,
    'profilePic' => $userProfilePic
];

// Output userData as JavaScript variable
$userDataJson = json_encode($userData);
$userDataScript = "<script>window.userData = $userDataJson;</script>";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STI DigiLibrary - Catalog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="/assets/css/catalog.css">
</head>

<body>

    <!-- ==================================== -->
    <!--              HEADER                 -->
    <!-- ==================================== -->
    <header class="navbar">
        <div class="navbar-container">
            <!-- Brand/Logo -->
            <a href="/catalog" class="navbar-brand">
                <img src="/assets/images/logo.png" alt="STI Logo" class="navbar-logo">
                <span class="navbar-title">STI DigiLibrary</span>
            </a>

            <!-- (Desktop) Search Bar -->
            <div class="navbar-search" id="navbar-search-container">
                <input type="text" id="search-input-nav" placeholder="Search by title, author, or ISBN...">
                <i class="fas fa-search"></i>
            </div>

            <!-- Actions: Notifications & Profile -->
            <div class="navbar-actions">
                <!-- Notification Bell -->
                <div class="action-item" id="notification-bell">
                    <i class="fa-solid fa-bell"></i>
                    <!-- Notification Dot (visibility controlled by JS) -->
                    <div class="notification-dot" id="notification-indicator" style="display: none;"></div>
                    <!-- Notification Dropdown -->
                    <div class="dropdown notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">
                            <p><strong>Notifications</strong></p>
                            <button id="clear-notifications-btn" class="clear-notifications-btn">Clear All</button>
                        </div>
                        <!-- Container for notification list -->
                        <div id="notification-list-container">
                            <!-- Notifications will be added here by JS -->
                            <p class="no-notifications">No new notifications.</p>
                        </div>
                    </div>
                </div>
                <!-- Profile Menu -->
                <div class="action-item" id="profile-menu">
                    <img src="<?php echo $userProfilePic; ?>" alt="User" class="profile-pic">
                    <!-- Profile Dropdown -->
                    <div class="dropdown" id="profile-dropdown">
                        <div class="profile-info">
                            <img src="<?php echo $userProfilePic; ?>" alt="User" class="profile-pic-large">
                            <span id="profile-name-dropdown"><?php echo $userName; ?></span>
                        </div>
                        <a href="/profile" class="dropdown-item">My Profile</a>
                        <a href="#" class="dropdown-item" id="pending-checkouts-btn">Pending Borrow Request</a>
                        <a href="/logout" class="dropdown-item">Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ==================================== -->
    <!--              MAIN CONTENT            -->
    <!-- ==================================== -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Slideshow -->
            <div class="slideshow-container">
                <div class="hero-slide active"><img src="/assets/images/slideshow1.png" alt="Library interior view"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow2.png" alt="Students studying together"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow3.png" alt="Rows of bookshelves"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow4.png" alt="Close-up of a book"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow5.png" alt="Modern library architecture"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow6.png" alt="Person reading a book"></div>
                <div class="hero-slide"><img src="/assets/images/slideshow7.png" alt="Library from a distance"></div>
            </div>
            <!-- Overlay Text -->
            <div class="hero-overlay">
                <h1>Welcome to the STI DigiLibrary, <?php echo $userName; ?>!</h1>
                <p>Available Resources: <strong id="total-resources">Loading...</strong> | Books Ready for Borrowing: <strong id="available-books">Loading...</strong></p>
            </div>
            <!-- Slideshow Dots -->
            <div class="slideshow-dots"></div>
        </section>

        <!-- CTA Buttons Section -->
        <section class="cta-button-section">
            <button class="cta-button" id="library-id-btn"><i class="fas fa-id-card"></i> <span id="library-id-btn-text">Loading...</span></button>
            <button class="cta-button" id="library-rules-btn"><i class="fas fa-book-reader"></i> Library Rules</button>
        </section>

        <!-- Catalog Section -->
        <section class="catalog-section">

            <!-- (Mobile Only) Search Bar -->
            <div class="catalog-search-mobile" id="catalog-search-mobile-container">
                <input type="text" id="search-input-mobile" placeholder="Search by title, author, or ISBN...">
                <i class="fas fa-search"></i>
            </div>

            <!-- Catalog Header (Title & Filters) -->
            <div class="catalog-header">
                <h2>Browse Collection</h2>
                <!-- Filter Controls -->
                <div class="catalog-controls">
                    <!-- Available Only Checkbox -->
                    <div class="filter-item">
                        <input type="checkbox" id="filter-available" name="filter-available">
                        <label for="filter-available">Available Only</label>
                    </div>
                    <!-- Custom Tag Dropdown -->
                    <div class="custom-select" id="custom-tag-filter">
                        <div class="select-button">
                            <span id="selected-tag-text">All Tags</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="select-dropdown"></div>
                    </div>
                    <!-- Book/Thesis Toggle -->
                    <div class="toggle-switch">
                        <span>Books</span>
                        <label class="switch">
                            <input type="checkbox" id="type-toggle">
                            <span class="slider round"></span>
                        </label>
                        <span>Thesis</span>
                    </div>
                    <!-- Grid/List View Toggle -->
                    <div class="view-toggle">
                        <button id="grid-view-btn" class="active"><i class="fas fa-th-large"></i></button>
                        <button id="list-view-btn"><i class="fas fa-list"></i></button>
                    </div>
                </div>
            </div>

            <!-- Thesis Rule Notification -->
            <div class="notification-box" id="thesis-notification" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <span>Thesis materials are for library use only and cannot be checked out.</span>
            </div>

            <!-- Book Catalog Grid -->
            <div id="book-catalog" class="catalog-grid">
                <!-- Book cards will be inserted here by JS -->
            </div>

            <!-- Pagination -->
            <nav class="pagination-container" id="pagination-container"></nav>
        </section>
    </main>

    <!-- ==================================== -->
    <!--              FOOTER                  -->
    <!-- ==================================== -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h4>STI DigiLibrary</h4>
                <p>&copy; <?php echo date('Y'); ?> STI College. All Rights Reserved.</p>
                <p>Taguig City, Metro Manila, Philippines</p>
            </div>
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/catalog">Home</a></li>
                    <li><a href="/profile">My Profile</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ==================================== -->
    <!--              MODALS                  -->
    <!-- ==================================== -->
    <div class="modal-overlay" id="modal-overlay"></div>

    <!-- Library ID Modal -->
    <div class="modal" id="library-id-modal">
        <button class="modal-close" data-modal-id="library-id-modal">&times;</button>
        <div id="id-card-content">
            <!-- Content generated by JS -->
        </div>
    </div>
    <!-- Apply for Digital ID Modal -->
    <div class="modal" id="apply-digital-id-modal">
        <button class="modal-close" data-modal-id="apply-digital-id-modal">&times;</button>
        <h2>Apply for Digital Library ID</h2>
        <div class="application-form">
            <form id="apply-digital-id-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo $userName; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Student Number</label>
                    <input type="text" value="<?php echo $studentNumber; ?>" readonly>
                </div>
                <div class="form-group">
                    <p><strong>Note:</strong> By submitting, you agree to follow all STI DigiLibrary rules. <br>
                        Your application will be reviewed by library staff. You'll receive a notification once it is approved.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="button-secondary modal-action-close" data-modal-id="apply-digital-id-modal">Cancel</button>
                    <button type="submit" class="button-primary">Submit Application</button>
                </div>
                <div id="application-status" style="margin-top: 15px; text-align: center;"></div>
            </form>
        </div>
    </div>

    <!-- Pending Library ID Modal -->
    <div class="modal" id="pending-library-id-modal">
        <button class="modal-close" data-modal-id="pending-library-id-modal">&times;</button>
        <div id="pending-id-content">
            <!-- Content generated by JS -->
        </div>
    </div>

    <!-- Confirm Borrow Modal -->
    <div class="modal" id="confirm-borrow-modal">
        <button class="modal-close" data-modal-id="confirm-borrow-modal">&times;</button>
        <h2>Review Borrow Request</h2>
        <div id="confirm-details">
            <!-- Content generated by JS -->
        </div>
        <div class="modal-actions">
            <button class="button-secondary modal-action-close" data-modal-id="confirm-borrow-modal">Cancel</button>
            <button class="button-primary" id="final-confirm-btn">Confirm</button>
        </div>
    </div>

    <!-- Borrow Receipt Modal -->
    <div class="modal" id="receipt-modal">
        <button class="modal-close" data-modal-id="receipt-modal">&times;</button>
        <h2><i class="fas fa-check-circle"></i> Borrow Request Sent!</h2>
        <div id="receipt-details">
            <!-- Content generated by JS -->
        </div>
        <div class="modal-actions">
            <button class="button-primary modal-action-close">Close</button>
        </div>
    </div>

    <!-- Pending Checkouts Modal -->
    <div class="modal" id="pending-checkouts-modal">
        <button class="modal-close" data-modal-id="pending-checkouts-modal">&times;</button>
        <h2>Pending Borrow Requests</h2>
        <div class="pending-list" id="pending-list">
            <!-- Content generated by JS -->
        </div>
    </div>

    <!-- Library Rules Modal -->
    <div class="modal" id="library-rules-modal">
        <button class="modal-close" data-modal-id="library-rules-modal">&times;</button>
        <h2>Library Rules</h2>
        <div class="rules-content" id="rules-content">
            <!-- Static Rules Content -->
            <p>To ensure a pleasant and productive environment for all, please observe the following rules:</p>
            <h4><i class="fas fa-user-friends"></i> General Conduct</h4>
            <ul>
                <li>Maintain a quiet atmosphere. Set phones to silent mode and take calls outside.</li>
                <li>No eating or drinking (except for water in a sealed container) is allowed.</li>
                <li>Respect library furniture and equipment. Do not move furniture.</li>
                <li>All bags are subject to inspection upon entry and exit.</li>
            </ul>
            <h4><i class="fas fa-book"></i> Borrowing &amp; Returning</h4>
            <ul>
                <li>Your digital Library ID (available on this page) is required for all transactions.</li>
                <li>Most books may be borrowed for a period of 7 days.</li>
                <li>Items may be renewed once, provided no other user has placed a reservation.</li>
                <li>Overdue items will incur a fine of ₱20.00 per day, per item.</li>
            </ul>
            <h4><i class="fas fa-microscope"></i> Special Materials</h4>
            <ul>
                <li><strong>Thesis and dissertations are for "Library Use Only" and cannot be checked out.</strong></li>
                <li>Reference books, journals, and periodicals are also for library use only.</li>
            </ul>
        </div>
        <div class="modal-actions">
            <button class="button-primary modal-action-close">Close</button>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        // User data available to JS
        window.userData = {
            userId: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
            userName: <?php echo json_encode($userName); ?>,
            userEmail: <?php echo json_encode($userEmail); ?>,
            studentId: <?php echo json_encode($studentNumber); ?>,
            profilePic: <?php echo json_encode($userProfilePic); ?>
        };
    </script>

    <!-- ==================================== -->
    <!--             JAVASCRIPT               -->
    <!-- ==================================== -->
    <script type="module" src="/assets/js/catalog/init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <?php echo $userDataScript; ?>
</body>

</html>