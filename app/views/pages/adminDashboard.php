<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login if not authenticated or not an admin
    header('Location: /login?error=unauthorized');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - STI DigiLibrary</title>
    <link rel="stylesheet" href="css/admin.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo.png" alt="STI Logo" class="logo" />
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#overview" data-section="overview" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Overview</a></li>
                    <li><a href="#book-management" data-section="book-management" class="nav-link"><i class="fas fa-book"></i> Book Management</a></li>
                    <li><a href="#borrow-management" data-section="borrow-management" class="nav-link"><i class="fa-solid fa-hands-holding"></i> Borrow Management</a></li>
                    <li><a href="#digital-ids" data-section="digital-ids" class="nav-link"><i class="fas fa-id-card"></i> Digital Library IDs</a></li>
                    <li><a href="#fines" data-section="fines" class="nav-link"><i class="fa-solid fa-peso-sign"></i> Fines</a></li>
                    <li><a href="#notifications" data-section="notifications" class="nav-link"><i class="fas fa-bell"></i> Notifications</a></li>
                    <li><a href="#user-management" data-section="user-management" class="nav-link"><i class="fa-solid fa-user"></i> User Management</a></li>
                    <li><a href="#analytics" data-section="analytics" class="nav-link"><i class="fas fa-chart-line"></i> Analytics</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <!-- RESET BUTTON -->
                <a href="#" id="reset-cache-btn" style="background-color: var(--danger); margin-bottom: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Clear Cache & Reset
                </a>
                <a href="index.html" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1 id="page-title">Overview</h1>
                <div class="admin-profile">
                    <span>Admin User</span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section id="overview" class="page-content active"></section>
            <section id="book-management" class="page-content"></section>
            <section id="borrow-management" class="page-content"></section>
            <section id="digital-ids" class="page-content"></section>
            <section id="fines" class="page-content"></section>
            <section id="notifications" class="page-content"></section>
            <section id="user-management" class="page-content"></section>
            <section id="analytics" class="page-content"></section>
        </main>
    </div>

    <!-- ========= MODALS ========= -->
    <!-- Modals used across different modules -->

    <!-- Book Management: Add/Edit Book -->
    <div id="add-book-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="add-book-modal-title">Add New Book</h2>
            <form id="add-book-form"></form>
        </div>
    </div>

    <!-- Book Management: Add/Edit Thesis -->
    <div id="add-thesis-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="add-thesis-modal-title">Add New Thesis</h2>
            <form id="add-thesis-form"></form>
        </div>
    </div>

    <!-- Book Management: Delete Confirmation -->
    <div id="deleteBookModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <span class="close-btn" id="closeDeleteBookModal">&times;</span>
            <h2 style="color: var(--danger); text-align: center;">Are you sure?</h2>
            <p id="deleteBookModalText" style="text-align: center; margin-bottom: 20px;">
                Do you really want to delete this item? This action cannot be undone.
            </p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                <button id="confirmDeleteBookBtn" class="btn btn-danger">Yes, Delete</button>
                <button id="cancelDeleteBookBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Fines: Issue Fine -->
    <div id="issue-fine-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Issue a New Fine</h2>
            <form id="issue-fine-form"></form>
        </div>
    </div>

    <!-- User Management: Edit User -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Edit User Information</h2>
            <form id="edit-user-form"></form>
        </div>
    </div>

    <!-- Digital IDs: Disapprove (OLD - to be removed/replaced) -->
    <div id="disapproveModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <span class="close-btn" id="closeDisapproveModal">&times;</span>
            <h2 style="color: var(--danger); text-align: center;">Are you sure?</h2>
            <p style="text-align: center;">Do you want to remove this student application from the Digital Library ID list?</p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                <button id="confirmDisapproveBtn" class="btn btn-danger">Yes, Remove</button>
                <button id="cancelDisapproveBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- NEW Digital IDs: Reject Application/Renewal Modal -->
    <div id="rejectIdModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close-btn">&times;</span>
            <h2 style="color: var(--danger);">Reject Request</h2>
            <p style="margin-bottom: 15px;">Please provide a reason for rejecting this request.</p>
            <form id="rejectIdForm">
                <input type="hidden" name="rejectType" value=""> <!-- 'application' or 'renewal' -->
                <input type="hidden" name="rejectId" value=""> <!-- ID of the application or renewal request -->
                <div class="form-group full-width">
                    <label for="rejectionReason">Rejection Reason (Optional)</label>
                    <textarea id="rejectionReason" name="reason" rows="4" class="form-control" placeholder="e.g., Missing required documents, duplicate request..."></textarea>
                </div>
                <div class="form-group full-width" style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary cancel-reject-btn" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW Digital IDs: Suspend Confirmation Modal -->
    <div id="suspendIdModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <span class="close-btn">&times;</span>
            <h2 style="color: var(--danger); text-align: center;">Suspend Library ID?</h2>
            <p id="suspendIdModalText" style="text-align: center; margin-bottom: 20px;">
                Are you sure you want to suspend this library ID? The user will not be able to borrow books.
            </p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                <button id="confirmSuspendIdBtn" class="btn btn-danger">Yes, Suspend</button>
                <button id="cancelSuspendIdBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- NEW Digital IDs: Renew Confirmation Modal -->
    <div id="renewIdModal" class="modal">
        <div class="modal-content confirm-modal-content" style="max-width: 450px;">
            <span class="close-btn">&times;</span>
            <h2 style="color: var(--success); text-align: center;">Renew Library ID?</h2>
            <p id="renewIdModalText" style="text-align: center; margin-bottom: 20px;">
                Are you sure you want to renew this library ID for another year?
            </p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                <button id="confirmRenewIdBtn" class="btn btn-success">Yes, Renew</button>
                <button id="cancelRenewIdBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- NEW Digital IDs: Set Semestral Expiry Date Modal -->
    <div id="setExpiryModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close-btn">&times;</span>
            <h2>Set Global Expiry Date</h2>
            <p style="margin-bottom: 15px;">Set the expiry date for all newly issued Library IDs (Month and Year).</p>
            <form id="setExpiryForm">
                <div class="form-group full-width">
                    <label for="newExpiryMonthYear">Expiry Month and Year</label>
                    <!-- Using a month input type for month and year selection (YYYY-MM) -->
                    <input type="month" id="newExpiryMonthYear" name="newExpiryDate" class="form-control" required>
                </div>
                <div class="form-group full-width" style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary cancel-expiry-btn" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save New Expiry</button>
                </div>
            </form>
        </div>
    </div>



    <script type="module" src="assets/js/admin/main.js"></script>
</body>

</html>