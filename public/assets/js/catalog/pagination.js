// public/assets/js/catalog/pagination.js

import {
  getPagination,
  setCurrentFilters,
  getCurrentFilters,
  setCatalogItems,
  setPagination,
} from "./stateManager.js";
import { fetchCatalogItems } from "./helpers.js";
import { renderBooks } from "./bookRenderer.js";
import { paginationContainer } from "./domSelectors.js";

export function renderPagination() {
  const pagination = getPagination();
  paginationContainer.innerHTML = "";

  if (!pagination || pagination.totalPages <= 1) return;

  const { page, totalPages } = pagination;
  const maxDisplayed = 7; // Change if you want more/less links in the window

  const createLink = (i, text = null, disabled = false) => {
    const link = document.createElement("a");
    link.href = "#";
    link.textContent = text || i;
    link.className = "pagination-link";
    if (i === page) link.classList.add("active");
    if (disabled) {
      link.classList.add("disabled");
      link.tabIndex = -1;
      link.style.pointerEvents = "none";
    } else {
      link.addEventListener("click", async (e) => {
        e.preventDefault();
        if (i === page) return;
        setCurrentFilters({ page: i });
        const filters = getCurrentFilters();
        try {
          const { data, pagination: newPagination } = await fetchCatalogItems(
            filters
          );
          setCatalogItems(data);
          setPagination(newPagination);
          await renderBooks();
          renderPagination();
        } catch (err) {
          paginationContainer.innerHTML = `<span>Error loading pages</span>`;
        }
      });
    }
    return link;
  };

  // Always show the first page
  paginationContainer.appendChild(createLink(1));

  // Show ellipsis if needed
  if (page > Math.floor(maxDisplayed / 2) + 1)
    paginationContainer.appendChild(createLink(-1, "...", true));

  // Windowed links around current page
  const window = Math.floor((maxDisplayed - 3) / 2);
  const start = Math.max(2, page - window);
  const end = Math.min(totalPages - 1, page + window);
  for (let i = start; i <= end; i++) {
    if (i === 1 || i === totalPages) continue;
    paginationContainer.appendChild(createLink(i));
  }

  if (page < totalPages - Math.floor(maxDisplayed / 2))
    paginationContainer.appendChild(createLink(-1, "...", true));

  // Always show the last page
  if (totalPages > 1) paginationContainer.appendChild(createLink(totalPages));
}
