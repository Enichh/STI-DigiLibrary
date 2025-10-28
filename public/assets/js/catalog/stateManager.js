// public/assets/js/catalog/stateManager.js

// Central reactive state (private within module)
let catalogItems = [];
let pagination = {};
let tags = [];
let totalResources = 0;
let availableBooks = 0;

// Initialize filters from URL parameters or use defaults
function initializeFiltersFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  const urlType = urlParams.get("type") || "book";
  const urlSearch = urlParams.get("search") || "";
  const urlTag = urlParams.get("tag") || "";
  const urlAvailableOnly = urlParams.get("available_only") === "true";
  const urlPage = parseInt(urlParams.get("page"), 10) || 1;
  const urlLimit = parseInt(urlParams.get("limit"), 10) || 12;

  return {
    type: urlType,
    search: urlSearch,
    tag: urlTag,
    available_only: urlAvailableOnly,
    page: urlPage,
    limit: urlLimit,
  };
}

let currentFilters = initializeFiltersFromURL();

// Catalog data
export function setCatalogItems(items) {
  catalogItems = items;
}
export function getCatalogItems() {
  return catalogItems;
}

// Pagination info
export function setPagination(pag) {
  pagination = pag;
}
export function getPagination() {
  return pagination;
}

// Tags
export function setTags(newTags) {
  tags = newTags;
}
export function getTags() {
  return tags;
}

// Stats
export function setTotalResources(num) {
  totalResources = num;
}
export function getTotalResources() {
  return totalResources;
}

export function setAvailableBooks(num) {
  availableBooks = num;
}
export function getAvailableBooks() {
  return availableBooks;
}

// Filters (Object for easy extension)
export function setCurrentFilters(filters) {
  currentFilters = { ...currentFilters, ...filters };
}
export function getCurrentFilters() {
  return { ...currentFilters };
}

// ==========================
// Pending Loans State
// ==========================

// Track pending user loan requests (used by borrowFlow.js)
let pendingLoans = [];

/**
 * Set all pending loans (override existing list)
 * @param {Array} loans
 */
export function setPendingLoans(loans) {
  pendingLoans = Array.isArray(loans) ? loans : [];
}

/**
 * Get all current pending loans
 * @returns {Array}
 */
export function getPendingLoans() {
  return [...pendingLoans];
}

/**
 * Add a new pending loan if not already registered
 * @param {Object} loan - loan object containing at least an ID
 */
export function addPendingLoan(loan) {
  if (!loan || !loan.id) {
    console.warn("[StateManager] Attempted to add invalid loan:", loan);
    return;
  }
  const exists = pendingLoans.some((l) => l.id === loan.id);
  if (!exists) {
    pendingLoans.push(loan);
    console.info("[StateManager] Added pending loan:", loan);
  }
}

/**
 * Remove a pending loan after approval/cancellation/rejection
 * @param {number|string} loanId
 */
export function removePendingLoan(loanId) {
  const before = pendingLoans.length;
  pendingLoans = pendingLoans.filter((l) => l.id !== loanId);
  const after = pendingLoans.length;
  if (before !== after) {
    console.info(`[StateManager] Removed pending loan ID: ${loanId}`);
  }
}

// Reset state (useful if switching catalogs/types etc.)
export function resetState() {
  catalogItems = [];
  pagination = {};
  tags = [];
  totalResources = 0;
  availableBooks = 0;
  currentFilters = initializeFiltersFromURL();
}
