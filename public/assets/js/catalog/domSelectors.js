// public/assets/js/catalog/domSelectors.js

// Header - Search
export const searchInputNav = document.getElementById("search-input-nav");
export const searchInputMobile = document.getElementById("search-input-mobile");

// Navbar - Notifications & Profile
export const notificationBell = document.getElementById("notification-bell");
export const notificationIndicator = document.getElementById(
  "notification-indicator"
);
export const notificationDropdown = document.getElementById(
  "notification-dropdown"
);
export const notificationListContainer = document.getElementById(
  "notification-list-container"
);
export const clearNotificationsBtn = document.getElementById(
  "clear-notifications-btn"
);
export const profileMenu = document.getElementById("profile-menu");
export const profileDropdown = document.getElementById("profile-dropdown");
export const profileNameDropdown = document.getElementById(
  "profile-name-dropdown"
);
export const pendingCheckoutsBtn = document.getElementById(
  "pending-checkouts-btn"
);

// Hero Section
export const totalResourcesEl = document.getElementById("total-resources");
export const availableBooksEl = document.getElementById("available-books");

// Slideshow
export const slideshowContainer = document.querySelector(
  ".slideshow-container"
);
export const slideshowDots = document.querySelector(".slideshow-dots");

// CTA Buttons
export const libraryRulesBtn = document.getElementById("library-rules-btn");

// Library ID Button Selector Function
export function getLibraryIdBtn() {
  return document.getElementById("library-id-btn");
}

// Catalog filters
export const availableOnlyFilter = document.getElementById("filter-available");
export const customTagFilter = document.getElementById("custom-tag-filter");
export const selectedTagText = document.getElementById("selected-tag-text");
export const tagSelectDropdown = customTagFilter
  ? customTagFilter.querySelector(".select-dropdown")
  : null;
export const typeToggle = document.getElementById("type-toggle");
export const gridViewBtn = document.getElementById("grid-view-btn");
export const listViewBtn = document.getElementById("list-view-btn");

// Catalog display
export const thesisNotification = document.getElementById(
  "thesis-notification"
);
export const bookCatalog = document.getElementById("book-catalog");
export const paginationContainer = document.getElementById(
  "pagination-container"
);

// Modals & overlay
export const modalOverlay = document.getElementById("modal-overlay");
export const libraryIdModal = document.getElementById("library-id-modal");
export const idCardContent = document.getElementById("id-card-content");
export const confirmBorrowModal = document.getElementById(
  "confirm-borrow-modal"
);
export const confirmDetails = document.getElementById("confirm-details");
export const finalConfirmBtn = document.getElementById("final-confirm-btn");
export const receiptModal = document.getElementById("receipt-modal");
export const receiptDetails = document.getElementById("receipt-details");
export const pendingCheckoutsModal = document.getElementById(
  "pending-checkouts-modal"
);
export const pendingList = document.getElementById("pending-list");
export const libraryRulesModal = document.getElementById("library-rules-modal");
export const rulesContent = document.getElementById("rules-content");

// Export as needed for other files
