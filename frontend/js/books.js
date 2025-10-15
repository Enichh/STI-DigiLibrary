import { fetchBooks } from "./books/booksApi.js";
import { renderBooks } from "./books/bookView.js";
import { setBooks, catalogState } from "./books/bookState.js";
import { setupSearchAndFilter } from "./books/bookSearchFilter.js";

document.addEventListener("DOMContentLoaded", async () => {
  setupSearchAndFilter();
  const books = await fetchBooks();
  setBooks(books);
  renderBooks(catalogState.filteredBooks, "book-list");
});
