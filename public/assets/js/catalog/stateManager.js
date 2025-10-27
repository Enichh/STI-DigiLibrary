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
  const urlType = urlParams.get('type') || 'book';
  const urlSearch = urlParams.get('search') || '';
  const urlTag = urlParams.get('tag') || '';
  const urlAvailableOnly = urlParams.get('available_only') === 'true';
  const urlPage = parseInt(urlParams.get('page'), 10) || 1;
  const urlLimit = parseInt(urlParams.get('limit'), 10) || 12;

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

// Reset state (useful if switching catalogs/types etc.)
export function resetState() {
  catalogItems = [];
  pagination = {};
  tags = [];
  totalResources = 0;
  availableBooks = 0;
  currentFilters = initializeFiltersFromURL();
}
