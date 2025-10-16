// State holder for catalog
export const catalogState = {
  books: [], // List of books on current page
  filteredBooks: [], // (not used in server-side pagination, kept for compatibility)
  filters: {},
  searchQuery: "",
  pagination: {
    page: 1,
    pageSize: 10,
    totalItems: 0,
    totalPages: 1,
  },
  loading: false,
};

// Set books and update pagination from API response
export function setBooks(data) {
  // data: { data: [...], pagination: { page, pageSize, totalItems, totalPages } }
  catalogState.books = data.data || [];
  catalogState.filteredBooks = data.data || [];
  if (data.pagination) {
    catalogState.pagination = {
      page: data.pagination.page,
      pageSize: data.pagination.pageSize,
      totalItems: data.pagination.totalItems,
      totalPages: data.pagination.totalPages,
    };
  }
}

// Update the current search query and clear precise fields in filters to avoid conflicts
export function setSearchQuery(query) {
  catalogState.searchQuery = query;
}

// Update applied filters and clear the main search box to avoid dual queries
export function setFilters(filters) {
  catalogState.filters = { ...filters };
  // If filters change (genre/title dropdowns), clear search to prevent ambiguity
  catalogState.searchQuery = "";
}

// Set pagination state
export function setPagination(page, pageSize) {
  catalogState.pagination.page = page;
  catalogState.pagination.pageSize = pageSize;
}

export function getPagedBooks() {
  return catalogState.books;
}
