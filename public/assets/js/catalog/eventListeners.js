// public/assets/js/catalog/eventListeners.js

import { fetchCatalogItems, fetchTags } from "./helpers.js";
import {
  setCatalogItems,
  setPagination,
  setTags,
  setCurrentFilters,
  getCurrentFilters,
} from "./stateManager.js";
import { renderBooks } from "./bookRenderer.js";
import { renderPagination } from "./pagination.js";
import { populateTags } from "./tagFilter.js";
import { getLibraryIdStatus } from "./libraryId.js";

// Helper: Reload and re-render catalog for given filter changes
async function reloadCatalog(overrides = {}) {
  const current = getCurrentFilters();
  const filters = { ...current, ...overrides, page: 1 };
  setCurrentFilters(filters);
  try {
    const { data, pagination } = await fetchCatalogItems(filters);
    setCatalogItems(data);
    setPagination(pagination);
    await renderBooks();
    renderPagination();
    console.info("[DEBUG] Catalog updated with:", filters);
  } catch (err) {
    console.error("Catalog reload failed", err);
    alert("Catalog reload failed: " + err.message);
  }
}

export function setupEventListeners() {
  // Initialize filter visibility and toggle state based on current state
  const currentFilters = getCurrentFilters();
  const typeToggle = document.getElementById("type-toggle");

  // Set initial toggle state based on state or default to books
  if (typeToggle) {
    const shouldBeThesis = currentFilters.type === "thesis";
    typeToggle.checked = shouldBeThesis;

    // Initialize filter visibility based on initial state
    const filterItem = document.querySelector(".filter-item");
    const customSelect = document.querySelector(".custom-select");
    const tagFilter = document.getElementById("custom-tag-filter");

    if (shouldBeThesis) {
      // Hide filters for theses
      if (filterItem) filterItem.classList.add("thesis-filters-hidden");
      if (customSelect) customSelect.classList.add("thesis-filters-hidden");
      if (tagFilter) tagFilter.classList.add("thesis-filters-hidden");
      // Show thesis notification
      document.getElementById("thesis-notification").style.display = "block";
    } else {
      // Show filters for books
      if (filterItem) filterItem.classList.remove("thesis-filters-hidden");
      if (customSelect) customSelect.classList.remove("thesis-filters-hidden");
      if (tagFilter) tagFilter.classList.remove("thesis-filters-hidden");
      // Hide thesis notification
      document.getElementById("thesis-notification").style.display = "none";
    }
  }
  // === Search input(s): listen for 'Enter' or 'blur' ===
  document
    .querySelectorAll("#search-input-nav, #search-input-mobile")
    .forEach((input) => {
      input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          reloadCatalog({ search: input.value.trim() });
        }
      });
      input.addEventListener("blur", (e) => {
        reloadCatalog({ search: input.value.trim() });
      });
    });

  // === Available only filter toggle ===
  const availChk = document.getElementById("filter-available");
  if (availChk) {
    availChk.addEventListener("change", () => {
      reloadCatalog({ available_only: availChk.checked });
    });
  }

  // === Tag filter dropdown (delegation: listen on dropdown root) ===
  // We use event delegation for efficiency and easier dynamic list management.
  const tagDropdown = document.getElementById("custom-tag-filter");
  if (tagDropdown) {
    tagDropdown.addEventListener("click", (e) => {
      const opt = e.target.closest(".select-option");
      if (opt) {
        const selectedTag = opt.dataset.tag || "";
        document.getElementById("selected-tag-text").textContent =
          selectedTag.length ? selectedTag : "All Tags";
        reloadCatalog({ tag: selectedTag });
      }
    });
  }

  // === Type toggle (Books/Thesis), reload catalog/tags and switch UI notification ===
  const typeToggleElement = document.getElementById("type-toggle");
  if (typeToggleElement) {
    typeToggleElement.addEventListener("change", async () => {
      const isThesis = typeToggleElement.checked;
      const type = isThesis ? "thesis" : "book";
      // Load and update tags
      try {
        const tags = await fetchTags(type);
        setTags(tags);
        populateTags();
      } catch (err) {
        console.error("[DEBUG] Failed to fetch tags for type:", type, err);
      }
      // Reset filters for new type and rerender
      reloadCatalog({ type, tag: "" });

      // Show/hide filter elements based on type
      const filterItem = document.querySelector(".filter-item");
      const customSelect = document.querySelector(".custom-select");
      const tagFilter = document.getElementById("custom-tag-filter");

      if (isThesis) {
        // Hide filters for theses (they don't use genres/tags and can't be borrowed)
        if (filterItem) filterItem.classList.add("thesis-filters-hidden");
        if (customSelect) customSelect.classList.add("thesis-filters-hidden");
        if (tagFilter) tagFilter.classList.add("thesis-filters-hidden");
      } else {
        // Show filters for books (they use genres and can be borrowed)
        if (filterItem) filterItem.classList.remove("thesis-filters-hidden");
        if (customSelect)
          customSelect.classList.remove("thesis-filters-hidden");
        if (tagFilter) tagFilter.classList.remove("thesis-filters-hidden");
      }

      // Show/hide thesis rule notification
      document.getElementById("thesis-notification").style.display = isThesis
        ? "block"
        : "none";
    });
  }

  // === Catalog View Switcher (Grid/List), accessible via keyboard ===
  const gridBtn = document.getElementById("grid-view-btn");
  const listBtn = document.getElementById("list-view-btn");
  // Keyboard & click accessible toggles
  [gridBtn, listBtn].forEach((btn) => {
    if (btn) {
      btn.addEventListener("click", switchCatalogView);
      btn.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          switchCatalogView.call(btn, e);
          e.preventDefault();
        }
      });
    }
  });
  async function switchCatalogView(e) {
    const isGrid = this.id === "grid-view-btn";
    gridBtn.classList.toggle("active", isGrid);
    listBtn.classList.toggle("active", !isGrid);
    const catalog = document.getElementById("book-catalog");
    catalog.classList.toggle("catalog-grid", isGrid);
    catalog.classList.toggle("catalog-list", !isGrid);
    await renderBooks();
    console.debug(
      "[DEBUG] Switched to " + (isGrid ? "grid" : "list") + " view."
    );
  }

  // === Pagination: delegate to parent container ===
  document
    .getElementById("pagination-container")
    .addEventListener("click", function (e) {
      const btn = e.target.closest("button[data-page]");
      if (btn) {
        const pageNum = parseInt(btn.dataset.page, 10);
        if (!isNaN(pageNum)) {
          reloadCatalog({ page: pageNum });
          // Optionally, scroll catalog into view
          document
            .getElementById("book-catalog")
            .scrollIntoView({ behavior: "smooth" });
        }
      }
    });

  // === [Optional] UI enhancements, keyboard accessibility, or modal/event control can be added below. ===
  // Handler to show a modal
  function showModal(id) {
    document.getElementById("modal-overlay").style.display = "block";
    document.getElementById(id).style.display = "block";
  }

  // Handler to close modals
  function closeModal(id) {
    document.getElementById("modal-overlay").style.display = "none";
    document.getElementById(id).style.display = "none";
  }

  // Show modal when "Library ID" button is clicked
  const libraryIdBtn = document.getElementById("library-id-btn");
  if (libraryIdBtn) {
    // First remove any existing click handlers to prevent duplicates
    const newBtn = libraryIdBtn.cloneNode(true);
    libraryIdBtn.parentNode.replaceChild(newBtn, libraryIdBtn);

    // Add a single click handler
    newBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      e.stopPropagation();

      // Add a small debounce to prevent double-clicks
      if (newBtn.dataset.processing === "true") return;
      newBtn.dataset.processing = "true";

      try {
        await (window.handleLibraryIdButton && window.handleLibraryIdButton());
      } finally {
        // Reset the processing flag after a short delay
        setTimeout(() => {
          newBtn.dataset.processing = "false";
        }, 500);
      }
    });
  }

  // Show modal when "Library Rules" button is clicked
  const rulesBtn = document.getElementById("library-rules-btn");
  if (rulesBtn) {
    rulesBtn.addEventListener("click", () => showModal("library-rules-modal"));
  }

  // Close modal logic for "x" and "Close" buttons
  document
    .querySelectorAll(".modal-close, .modal-action-close")
    .forEach((btn) => {
      btn.addEventListener("click", function () {
        closeModal(this.dataset.modalId || this.closest(".modal").id);
      });
    });

  // === Borrow button validation ===
  document.addEventListener("click", async (e) => {
    const borrowBtn = e.target.closest(".borrow-btn");
    if (borrowBtn && !borrowBtn.disabled) {
      e.preventDefault();

      const userId = window.userData?.userId;
      const bookId = borrowBtn.dataset.bookId;

      if (!userId) {
        alert("Please log in to borrow books.");
        return;
      }

      if (!bookId) {
        alert("Error: Book ID not found.");
        return;
      }

      try {
        const { status } = await getLibraryIdStatus(userId);

        if (status === "active") {
          // User can borrow - proceed with borrowing logic (to be implemented)
          alert(
            `Borrowing functionality will be implemented soon!\nBook ID: ${bookId}\nLibrary ID: Active ✅`
          );

          // TODO: Implement actual borrowing logic here
          // This would typically open a borrow confirmation modal
          // showModal("confirm-borrow-modal");
        } else if (status === "pending") {
          alert(
            "Your library ID application is still under review. You cannot borrow books until it is approved."
          );
        } else {
          // No library ID or other status
          const shouldApply = confirm(
            "You need an active Library ID to borrow books.\n\nWould you like to apply for a Library ID now?"
          );
          if (shouldApply) {
            // Show the library ID application modal
            document.getElementById("apply-digital-id-modal").style.display =
              "block";
            document.getElementById("modal-overlay").style.display = "block";
          }
        }
      } catch (error) {
        console.error("Error checking library ID status:", error);
        alert("Error checking your library ID status. Please try again.");
      }
    }
  });

  console.info(
    "[DEBUG] setupEventListeners: All main catalog listeners attached"
  );
}
