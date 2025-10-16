import {
  setSearchQuery,
  setFilters,
  catalogState,
  setBooks,
  setPagination,
} from "./bookState.js";
import { renderBooks } from "./bookView.js";
import { fetchBooks } from "./booksApi.js";

// Attach search, filter, and pagination event listeners
export function setupSearchAndFilter() {
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");
  const genreSelect = document.getElementById("genreSelect");
  const titleSelect = document.getElementById("titleSelect");
  const availableCheck = document.getElementById("availableCheck");
  const prevPageBtn = document.getElementById("prevPage");
  const nextPageBtn = document.getElementById("nextPage");
  const pageInfo = document.getElementById("pageInfo");

  // Search input logic
  searchForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    setSearchQuery(searchInput.value.trim());
    await applyFilters();
  });

  // Filter logic
  [genreSelect, titleSelect, availableCheck].forEach((element) => {
    element.addEventListener("change", async () => {
      setFilters({
        genre: genreSelect.value,
        title: titleSelect.value,
        available: availableCheck.checked,
      });
      setPagination(1, catalogState.pagination.pageSize); // reset to first page
      await applyFilters();
    });
  });

  // Pagination controls
  if (prevPageBtn && nextPageBtn) {
    prevPageBtn.addEventListener("click", async () => {
      if (catalogState.pagination.page > 1) {
        setPagination(
          catalogState.pagination.page - 1,
          catalogState.pagination.pageSize
        );
        await applyFilters();
      }
    });

    nextPageBtn.addEventListener("click", async () => {
      if (catalogState.pagination.page < catalogState.pagination.totalPages) {
        setPagination(
          catalogState.pagination.page + 1,
          catalogState.pagination.pageSize
        );
        await applyFilters();
      }
    });
  }
}

// Fetch books using API for current filters/paging, then render
export async function applyFilters() {
  try {
    const { searchQuery, filters, pagination } = catalogState;
    const params = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      ...filters,
    };
    if (searchQuery) {
      params.search = searchQuery;
    }
    const data = await fetchBooks(params);
    setBooks(data);
    updatePaginationUI();
    renderBooks(data.data);
  } catch (err) {
    console.error("Error applying filters:", err);
  }
}

// Update paging UI with current page info and disable/enable buttons
export function updatePaginationUI() {
  const { page, totalPages } = catalogState.pagination;
  const pageInfo = document.getElementById("pageInfo");
  const prevPageBtn = document.getElementById("prevPage");
  const nextPageBtn = document.getElementById("nextPage");
  if (pageInfo) {
    pageInfo.textContent = `Page ${page} of ${totalPages}`;
  }
  if (prevPageBtn) {
    prevPageBtn.disabled = page <= 1;
  }
  if (nextPageBtn) {
    nextPageBtn.disabled = page >= totalPages;
  }
}
