import { fetchBooks } from "./books/booksApi.js";
import { renderBooks } from "./books/bookView.js";
import {
  updatePaginationUI,
  setupSearchAndFilter,
  applyFilters,
} from "./books/bookSearchFilter.js";
import { setBooks, catalogState, setPagination } from "./books/bookState.js";

// Initialize the catalog with pagination
async function initCatalog() {
  try {
    // Set initial pagination
    setPagination(1, 12); // Start at page 1 with 10 items per page

    // Initial data fetch with pagination
    const data = await fetchBooks({
      page: 1,
      pageSize: 12,
    });

    // Update state and UI
    setBooks(data);
    renderBooks(data.data);
    updatePaginationUI();

    // Set up event listeners
    setupSearchAndFilter();
  } catch (error) {
    console.error("Error initializing catalog:", error);
    // Consider showing an error message to the user
  }
}

// Start the application when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initCatalog();
});
