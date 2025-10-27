// public/assets/js/catalog/tagFilter.js

import {
  customTagFilter,
  selectedTagText,
  tagSelectDropdown,
} from "./domSelectors.js";
import {
  getTags,
  setCurrentFilters,
  getCurrentFilters,
  setCatalogItems,
  setPagination,
} from "./stateManager.js";
import { fetchCatalogItems } from "./helpers.js";
import { renderBooks } from "./bookRenderer.js";
import { renderPagination } from "./pagination.js";

// Renders the dropdown of tags from state
export function populateTags() {
  const tags = getTags();
  tagSelectDropdown.innerHTML = "";

  // All Tags option
  const allTagOption = document.createElement("div");
  allTagOption.className = "select-option";
  allTagOption.dataset.value = "all";
  allTagOption.textContent = "All Tags";
  tagSelectDropdown.appendChild(allTagOption);

  // Other tags
  tags.sort().forEach((tag) => {
    const option = document.createElement("div");
    option.className = "select-option";
    option.dataset.value = tag;
    option.textContent = tag;
    tagSelectDropdown.appendChild(option);
  });
}

// Setup tag filter dropdown and its event handling
export function setupTagFilterListeners() {
  // Toggle dropdown open/close
  customTagFilter
    .querySelector(".select-button")
    .addEventListener("click", () => {
      customTagFilter.classList.toggle("open");
    });

  // Handle selection
  tagSelectDropdown.addEventListener("click", async (e) => {
    if (e.target.classList.contains("select-option")) {
      const newValue = e.target.dataset.value;
      const newText = e.target.textContent;

      // Update filter state (empty string for all)
      setCurrentFilters({
        tag: newValue === "all" ? "" : newValue,
        page: 1, // reset to first page
      });

      selectedTagText.textContent = newText;
      customTagFilter.classList.remove("open");

      // Fetch new results and rerender
      const filters = getCurrentFilters();
      try {
        const { data, pagination } = await fetchCatalogItems(filters);
        setCatalogItems(data);
        setPagination(pagination);
        await renderBooks();
        renderPagination();
      } catch (err) {
        // Handle error here if needed
      }
    }
  });

  // Close dropdown if clicking outside
  document.addEventListener("click", (e) => {
    if (!customTagFilter.contains(e.target)) {
      customTagFilter.classList.remove("open");
    }
  });
}
