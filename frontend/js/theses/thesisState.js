import { fetchTheses } from "./thesisApi.js";

// State holder for theses catalog
export const thesisState = {
  items: [], // current page of theses
  filteredItems: [], // reserved for client-side filtering if needed
  filters: {
    title: "", // title prefix search
    year: "", // exact year filter (string or int)
  },
  pagination: {
    page: 1,
    pageSize: 12,
    totalItems: 0,
    totalPages: 1,
  },
  loading: false,
};

// Replace list and pagination from API payload { data, pagination }
export function setTheses(payload) {
  thesisState.items = payload?.data || [];
  thesisState.filteredItems = payload?.data || [];
  if (payload?.pagination) {
    thesisState.pagination = {
      page: payload.pagination.page,
      pageSize: payload.pagination.pageSize,
      totalItems: payload.pagination.totalItems,
      totalPages: payload.pagination.totalPages,
    };
  }
}

// Setters for filters
export function setTitleFilter(title) {
  thesisState.filters.title = (title || "").trim();
}

export function setYearFilter(year) {
  thesisState.filters.year = String(year || "").trim();
}

// Pagination setters
export function setThesisPage(page) {
  const newPage = Math.max(1, Number(page) || 1);
  if (thesisState.pagination.page !== newPage) {
    thesisState.pagination.page = newPage;
    return true; // Indicates page changed
  }
  return false; // No change
}

export function setThesisPageSize(size) {
  const n = Number(size) || 12;
  const newSize = Math.max(1, Math.min(100, n));
  if (thesisState.pagination.pageSize !== newSize) {
    thesisState.pagination.pageSize = newSize;
    return true; // Indicates size changed
  }
  return false; // No change
}

// Update pagination and refresh data
export async function updateThesisPagination(page, pageSize) {
  try {
    const pageChanged = setThesisPage(page);
    const sizeChanged = setThesisPageSize(pageSize);

    if (pageChanged || sizeChanged) {
      console.log("Fetching theses with params:", {
        page: thesisState.pagination.page,
        pageSize: thesisState.pagination.pageSize,
        ...thesisState.filters,
      });

      const payload = await fetchTheses({
        page: thesisState.pagination.page,
        pageSize: thesisState.pagination.pageSize,
        ...thesisState.filters,
      });

      if (!payload) {
        throw new Error("No payload received from fetchTheses");
      }

      console.log("Received payload:", payload);
      setTheses(payload);
      return payload;
    }
    return null;
  } catch (error) {
    console.error("Error in updateThesisPagination:", error);
    // Optionally revert the page number on error
    setThesisPage(
      thesisState.pagination.page > 1 ? thesisState.pagination.page - 1 : 1
    );
    return null;
  }
}

// Accessor for current page list
export function getPagedTheses() {
  return thesisState.items;
}
