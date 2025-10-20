import { fetchBooks } from "./booksApi.js";
import { renderBooks } from "./bookView.js";
import {
  updatePaginationUI,
  setupSearchAndFilter,
} from "./bookSearchFilter.js";
import { setBooks, catalogState, setPagination } from "./bookState.js";
import { onTabActivated } from "../catalog.js";

let booksInitialized = false;

/**
 * Initializes the books catalog.
 *
 * This function sets up the initial state for the books catalog,
 * fetches the first page of books, and renders them.
 * @returns {Promise<void>}
 */
export async function initBooksCatalog() {
  try {
    if (!booksInitialized) {
      setPagination(1, 12);
      setupSearchAndFilter();
      booksInitialized = true;
    }

    const data = await fetchBooks({
      page: catalogState.pagination.page,
      pageSize: catalogState.pagination.pageSize,
    });
    setBooks(data);
    renderBooks(data.data);
    updatePaginationUI();
  } catch (error) {
    console.error("Error initializing books catalog:", error);
  }
}

// DO NOT include: export { initBooksCatalog };

onTabActivated("books", () => initBooksCatalog());
