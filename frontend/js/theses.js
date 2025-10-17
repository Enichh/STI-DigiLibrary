import { fetchTheses } from "./theses/thesisApi.js";
import {
  renderTheses,
  renderPagination as renderThesesPager,
} from "./theses/thesisView.js";
import { setupThesisSearchAndFilter } from "./theses/thesisSearchFilter.js";
import {
  thesisState,
  setTheses,
  setThesisPage,
  setThesisPageSize,
} from "./theses/thesisState.js";
import { onTabActivated } from "./catalog.js";

let thesesInitialized = false;

/**
 * Initializes the thesis catalog.
 *
 * This function sets up the initial state for the thesis catalog,
 * fetches the first page of theses, and renders them along with pagination.
 * @returns {Promise<void>}
 */
export async function initThesisCatalog() {
  try {
    if (!thesesInitialized) {
      setThesisPage(1);
      setThesisPageSize(12);
      setupThesisSearchAndFilter();
      thesesInitialized = true;
    }

    const payload = await fetchTheses({
      page: thesisState.pagination.page,
      pageSize: thesisState.pagination.pageSize,
    });

    setTheses(payload);
    renderTheses(thesisState.items, "thesisList");
    renderThesesPager(
      document.getElementById("thesisPager"),
      thesisState.pagination
    );
  } catch (error) {
    console.error("Error initializing theses catalog:", error);
  }
}

// Initialize on tab activation
onTabActivated("theses", () => initThesisCatalog());
