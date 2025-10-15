// State holder for catalog
export const catalogState = {
  books: [],
  filteredBooks: [],
  filters: {},
  searchQuery: "",
  pagination: {
    page: 1,
    pageSize: 10,
    total: 0,
  },
};

// Set entire book list and update count
export function setBooks(books) {
  catalogState.books = books;
  catalogState.filteredBooks = books; // replace with filtering later
  catalogState.pagination.total = books.length;
}

// Update the current search query
export function setSearchQuery(query) {
  catalogState.searchQuery = query;
}

// Update applied filters
export function setFilters(filters) {
  catalogState.filters = filters;
}

// Set pagination state
export function setPagination(page, pageSize) {
  catalogState.pagination.page = page;
  catalogState.pagination.pageSize = pageSize;
}

export function getPagedBooks() {
  const { page, pageSize } = catalogState.pagination;
  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  return catalogState.filteredBooks.slice(start, end);
}
