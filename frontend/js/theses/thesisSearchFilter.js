import {
  thesisState,
  setTheses,
  setTitleFilter,
  setYearFilter,
  setThesisPage,
  updateThesisPagination,
} from "./thesisState.js";
import { fetchTheses } from "./thesisApi.js";
import {
  renderTheses,
  renderPagination as renderThesesPager,
} from "./thesisView.js";

// Wire up search input, year select, and pager for the Theses tab
export function setupThesisSearchAndFilter() {
  const searchInput = document.getElementById("thesisSearch");
  const yearSelect = document.getElementById("thesisYear");
  const pager = document.getElementById("thesisPager");

  // Debounced search by title (prefix)
  let t;
  searchInput?.addEventListener("input", (e) => {
    clearTimeout(t);
    t = setTimeout(async () => {
      setTitleFilter(e.target.value.trim());
      setThesisPage(1);
      await applyThesisFilters();
    }, 250);
  });

  // Year filter
  yearSelect?.addEventListener("change", async (e) => {
    setYearFilter(e.target.value);
    setThesisPage(1);
    await applyThesisFilters();
  });

  // Pager clicks - Use event delegation on document to handle dynamic content
  document.addEventListener("click", async (e) => {
    // Only handle clicks on pagination buttons
    const pager = document.getElementById("thesisPager");
    if (!pager || !pager.contains(e.target)) return;

    const act = e.target?.closest("[data-act]")?.dataset?.act;
    if (!act) return;

    e.preventDefault();

    try {
      let newPage = thesisState.pagination.page;

      switch (act) {
        case "prev":
          if (thesisState.pagination.page > 1) {
            newPage = thesisState.pagination.page - 1;
          } else {
            return; // Already on first page
          }
          break;

        case "next":
          console.log("Next button clicked");
          if (thesisState.pagination.page < thesisState.pagination.totalPages) {
            newPage = thesisState.pagination.page + 1;
          } else {
            return; // Already on last page
          }
          break;

        default:
          return; // Unknown action
      }

      // Update pagination and fetch new data
      const payload = await updateThesisPagination(
        newPage,
        thesisState.pagination.pageSize
      );

      if (payload) {
        // Update the UI with new data
        renderTheses(thesisState.items, "thesisList");
        renderThesesPager(
          document.getElementById("thesisPager"),
          thesisState.pagination
        );
      }
    } catch (error) {
      console.error("Error handling pagination:", error);
    }
  });

  // No need for the setup function override anymore since we're using event delegation
  // on the document level which persists across re-renders
  // and we're only attaching the listener once
}

// Apply current filters/pagination and render
export async function applyThesisFilters() {
  const { filters, pagination } = thesisState;

  // Build params for backend: title (prefix), year (exact), page, pageSize
  const params = {
    page: pagination.page,
    pageSize: pagination.pageSize,
  };
  if (filters.title) params.title = filters.title;
  if (filters.year) params.year = filters.year;

  // Call the list endpoint (equivalent to search when using precise params)
  const payload = await fetchTheses(params);

  setTheses(payload);
  renderTheses(document.getElementById("thesisList"), thesisState.items);
  renderThesesPager(
    document.getElementById("thesisPager"),
    thesisState.pagination
  );
}

// Helper to set pager text/buttons if you keep a separate UI updater
export function updateThesisPaginationUI() {
  const { page, totalPages } = thesisState.pagination;
  const pager = document.getElementById("thesisPager");
  if (!pager) return;
  pager.querySelector("span")?.replaceWith(
    Object.assign(document.createElement("span"), {
      textContent: `Page ${page} of ${Math.max(1, totalPages)}`,
    })
  );
}
