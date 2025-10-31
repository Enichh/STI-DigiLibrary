// apiBookManagement.js
// Provides API calls for Book Management (CRUD + counts)

import { apiRequest } from "./helpers.js";
import { getConfig } from "../config.js";

/**
 * Fetch catalog items (books + theses) with filters
 */
export async function fetchCatalogItems(filters = {}, page = 1, pageSize = 20) {
  const { api } = await getConfig();

  // Create clean copy of filters
  const cleanFilters = { ...filters };

  // Handle genre filter to prevent double encoding
  if (cleanFilters.genre && cleanFilters.genre !== "all") {
    const textarea = document.createElement("textarea");
    textarea.innerHTML = cleanFilters.genre;
    cleanFilters.genre = textarea.value.trim();
    console.log("Genre after HTML entity decode:", cleanFilters.genre);
  }

  // Build URLSearchParams for proper URL encoding
  const params = new URLSearchParams();
  params.append("type", cleanFilters.type || "book");

  // Add all other filters
  Object.entries(cleanFilters).forEach(([key, value]) => {
    if (
      value !== undefined &&
      value !== null &&
      value !== "" &&
      key !== "type"
    ) {
      params.append(key, value);
    }
  });

  // Add pagination
  params.append("page", page);
  params.append("limit", pageSize);

  const endpoint = `${api.baseUrl}${
    api.endpoints.catalog
  }/items?${params.toString()}`;
  console.log("API Request URL:", endpoint);

  try {
    const response = await apiRequest(endpoint);

    // Log raw API response
    console.log("Raw API Response:", JSON.parse(JSON.stringify(response)));

    console.log("API Response Summary:", {
      success: response.success,
      count: response.data?.length || 0,
      total: response.pagination?.total || 0,
      hasMore: response.pagination?.hasMore || false,
    });

    return response;
  } catch (error) {
    console.error("Error fetching catalog items:", {
      error: error.message,
      endpoint,
      filters: cleanFilters,
      page,
      pageSize,
    });
    throw error;
  }
}

/**
 * Fetch total count of books
 */
export async function fetchBookCount(filters = {}) {
  const { api } = await getConfig();
  const params = new URLSearchParams(filters);
  return apiRequest(`${api.baseUrl}${api.endpoints.books}/count?${params}`);
}

/**
 * Fetch a single book by ID
 */
export async function fetchBookById(id) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.books}/${id}`);
}

/**
 * Create a new book
 */
export async function createBook(data) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.books}`, {
    method: "POST",
    body: JSON.stringify(data),
  });
}

/**
 * Update an existing book
 */
export async function updateBook(id, data) {
  const { api } = await getConfig();
  const endpoint = `${api.baseUrl}${api.endpoints.books}/${id}`;

  console.log("=== updateBook DEBUG ===");
  console.log("ID:", id, "Type:", typeof id);
  console.log("api.baseUrl:", api.baseUrl);
  console.log("api.endpoints.books:", api.endpoints.books);
  console.log("Full endpoint:", endpoint);
  console.log("Method: PUT");
  console.log("Data payload:", data);

  return apiRequest(endpoint, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

/**
 * Delete a book by ID
 */
export async function deleteBook(id) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.books}/${id}`, {
    method: "DELETE",
  });
}

/**
 * Fetch total number of book copies (all statuses)
 */
export async function fetchTotalBookCopies() {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.books}/copies/count`);
}

/**
 * Fetch available copies count
 */
export async function fetchAvailableBookCopies() {
  const { api } = await getConfig();
  return apiRequest(
    `${api.baseUrl}${api.endpoints.books}/copies/count?status=available`
  );
}

/**
 * Fetch checked out copies count
 */
export async function fetchCheckedOutBookCopies() {
  const { api } = await getConfig();
  return apiRequest(
    `${api.baseUrl}${api.endpoints.books}/copies/count?status=checked_out`
  );
}

/**
 * Create a new thesis
 */
export async function createThesis(data) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.theses}`, {
    method: "POST",
    body: JSON.stringify(data),
  });
}

/**
 * Update an existing thesis
 */
export async function updateThesis(id, data) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.theses}/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

/**
 * Delete a thesis by ID
 */
export async function deleteThesis(id) {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.theses}/${id}`, {
    method: "DELETE",
  });
}
