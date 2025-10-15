import {
  setSearchQuery,
  setFilters,
  catalogState,
  setBooks,
} from "./bookState.js";
import { renderBooks } from "./bookView.js";

// Attach search and filter event listeners to the catalog page
export function setupSearchAndFilter() {
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");
  const genreSelect = document.getElementById("genreSelect");
  const titleSelect = document.getElementById("titleSelect");
  const availableCheck = document.getElementById("availableCheck");

  // Search input logic
  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    setSearchQuery(searchInput.value.trim());
    applyFilters();
  });

  // Filter logic
  [genreSelect, titleSelect, availableCheck].forEach((element) => {
    element.addEventListener("change", () => {
      setFilters({
        genre: genreSelect.value,
        title: titleSelect.value,
        available: availableCheck.checked,
      });
      applyFilters();
    });
  });
}

// Filters catalogState.books according to query and filters
function applyFilters() {
  let filtered = catalogState.books;
  const { searchQuery, filters } = catalogState;

  if (searchQuery) {
    filtered = filtered.filter(
      (book) =>
        (book.title &&
          book.title.toLowerCase().includes(searchQuery.toLowerCase())) ||
        (book.isbn && book.isbn.includes(searchQuery))
    );
  }

  if (filters.genre && filters.genre !== "all") {
    filtered = filtered.filter(
      (book) => book.genre && book.genre === filters.genre
    );
  }

  if (filters.title && filters.title !== "all") {
    filtered = filtered.filter(
      (book) => book.title && book.title === filters.title
    );
  }

  if (filters.available) {
    filtered = filtered.filter((book) => book.status === "Available");
  }

  // Update filteredBooks and re-render
  catalogState.filteredBooks = filtered;
  renderBooks(filtered, "book-list");
}
