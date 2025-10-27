// public/assets/js/catalog/init.js

import { fetchCatalogStats, fetchCatalogItems, fetchTags } from "./helpers.js";
import {
  setCatalogItems,
  setPagination,
  setTotalResources,
  setAvailableBooks,
  setTags,
  setCurrentFilters,
  getCurrentFilters,
} from "./stateManager.js";
import { renderBooks } from "./bookRenderer.js"; // Implement after this!
import { renderPagination } from "./pagination.js";
import { updateHeroStats } from "./heroStats.js";
import { populateTags } from "./tagFilter.js";
import { setupEventListeners } from "./eventListeners.js";
import { initLibraryIdUi } from "./libraryId.js";

async function init() {
  try {
    // 1. Fetch stats, store in state, update UI
    const stats = await fetchCatalogStats();
    setTotalResources(stats.totalResources);
    setAvailableBooks(stats.availableBooks);
    updateHeroStats();

    // 2. Fetch all tags (for default type "book")
    const tags = await fetchTags("book");
    setTags(tags);
    populateTags(); // Draw the tag dropdown

    // 3. Fetch initial catalog items (type=book, page=1, etc.)
    const filters = getCurrentFilters();
    const { data, pagination } = await fetchCatalogItems(filters);
    setCatalogItems(data);
    setPagination(pagination);

    // 4. Render the catalog grid/list and pagination
    renderBooks();
    renderPagination();

    // 5. Set up event listeners (search, filters, etc.)
    setupEventListeners();

    initLibraryIdUi();
  } catch (err) {
    // You can add error display/notification logic here
    alert("Failed to load data: " + err.message);
  }
}

// DOM loaded event
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
