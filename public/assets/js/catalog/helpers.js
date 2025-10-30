import { configPromise } from "../config.js";
//public/assets/js/catalog/helpers.js

function buildQuery(params = {}) {
  const query = Object.entries(params)
    .filter(
      ([_, value]) => value !== undefined && value !== null && value !== ""
    )
    .map(
      ([key, value]) =>
        `${encodeURIComponent(key)}=${encodeURIComponent(value)}`
    )
    .join("&");
  return query ? `?${query}` : "";
}

async function getCatalogBaseUrl() {
  try {
    const config = await configPromise;
    const baseUrl = `${config.api.baseUrl.replace(/\/$/, "")}${
      config.api.endpoints?.catalog || "/catalog"
    }`;
    return baseUrl;
  } catch (err) {
    throw new Error("Failed to resolve catalog base URL due to config error.");
  }
}

export async function fetchCatalogStats() {
  try {
    const BASE_URL = await getCatalogBaseUrl();
    const url = `${BASE_URL}/stats`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success)
      return {
        totalResources: data.totalResources,
        availableBooks: data.availableBooks,
      };
    throw new Error(data.error || "Failed to fetch stats");
  } catch (err) {
    throw err;
  }
}

export async function fetchCatalogItems(params = {}) {
  try {
    const BASE_URL = await getCatalogBaseUrl();
    const query = buildQuery(params);
    const url = `${BASE_URL}/items${query}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
      console.log("[fetchCatalogItems] Raw API response:", data);
      const transformedData = data.data.map((item) =>
        transformBackendItemToFrontend(item, params.type)
      );
      return {
        data: transformedData,
        pagination: data.pagination,
        total: data.total,
      };
    }
    throw new Error(data.error || "Failed to fetch catalog items");
  } catch (err) {
    throw err;
  }
}

export async function fetchTags(type) {
  try {
    if (type === "thesis") return [];
    const BASE_URL = await getCatalogBaseUrl();
    const query = buildQuery(type ? { type } : {});
    const url = `${BASE_URL}/tags${query}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) return data.tags;
    throw new Error(data.error || "Failed to fetch tags");
  } catch (err) {
    throw err;
  }
}
/**
 * Fetch a single book's details by its copyId.
 * Uses the /loans/book/:copyId endpoint.
 */
export async function fetchBookByCopyId(copyId) {
  const config = await configPromise;
  const baseApi = config.api.baseUrl.replace(/\/$/, "");
  const endpoint = config.api.endpoints?.loans || "/loans";
  const url = `${baseApi}${endpoint}/book/${copyId}`;

  const res = await fetch(url);

  let data;
  try {
    data = await res.json();
  } catch (err) {
    throw new Error("Invalid JSON in backend response");
  }

  const isSuccess =
    data.success === true || data.status === "success" || !!data.book;

  if (isSuccess) {
    return transformBackendItemToFrontend(data.book || data.data || data);
  }

  throw new Error(data.error || "Failed to fetch book by copyId");
}

export async function fetchBookById(bookId) {
  const config = await configPromise;
  const baseApi = config.api.baseUrl.replace(/\/$/, "");
  const endpoint = config.api.endpoints?.books || "/books";
  const url = `${baseApi}${endpoint}/${bookId}`;
  const res = await fetch(url);
  const data = await res.json();
  console.log(`[fetchBookById] Response from ${url}:`, data);

  // Accept either 'status' or 'success' from backend
  const isSuccess = data.success === true || data.status === "success";

  if (isSuccess) {
    // BooksController should still return the book under data.data or data (per your backend shape)
    return transformBackendItemToFrontend(data.data || data.book || data);
  }

  throw new Error(data.error || "Failed to fetch book by ID");
}
/**
 * Cancels a pending loan by its loanId.
 * PATCH /loans/{loanId}/cancel
 */
export async function cancelLoan(loanId) {
  const config = await configPromise;
  const baseApi = config.api.baseUrl.replace(/\/$/, "");
  const endpoint = config.api.endpoints?.loans || "/loans";
  const url = `${baseApi}${endpoint}/${loanId}/cancel`;

  const res = await fetch(url, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
  });

  let data;
  try {
    data = await res.json();
  } catch (err) {
    throw new Error("Invalid JSON in backend response");
  }

  if (data.success || data.status === "success") {
    return data;
  }
  throw new Error(data.error || "Failed to cancel loan");
}

/**
 * Sends a new loan request to the backend
 * @param {number} userId
 * @param {number} copyId
 * @param {string} borrowedDate (ISO string or yyyy-mm-dd HH:MM:SS)
 * @param {string} dueDate (yyyy-mm-dd)
 * @param {string|null} remarks
 */
export async function createLoan(userId, copyId, borrowedDate, dueDate) {
  console.log("[createLoan] Sending:", {
    user_id: userId,
    copy_id: copyId,
    borrowed_date: borrowedDate,
    due_date: dueDate,
  });

  const config = await configPromise;
  const baseApi = config.api.baseUrl.replace(/\/$/, "");
  const endpoint = "/loans/borrow";
  const url = `${baseApi}${endpoint}`;

  try {
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        user_id: userId,
        copy_id: copyId,
        borrowed_date: borrowedDate,
        due_date: dueDate,
      }),
    });
    const data = await res.json();
    if (data.success) return data;
    throw new Error(data.error || "Failed to create loan");
  } catch (err) {
    throw err;
  }
}

export function formatDate(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Fetches all pending loans for a user (returns a Set of copy_ids)
 */
export async function fetchUserPendingCopyIds(userId) {
  try {
    const config = await configPromise;
    const baseApi = config.api.baseUrl.replace(/\/$/, "");
    const endpoint = config.api.endpoints?.loans || "/loans";
    const url = `${baseApi}${endpoint}/user/${userId}?status=pending`;
    const res = await fetch(url);
    const data = await res.json();
    // Return set of copy_id for fast lookup
    return new Set((data.loans || []).map((loan) => loan.copy_id));
  } catch {
    return new Set();
  }
}

/**
 * Fetch the number of pending loan requests for a user.
 * @param {number} userId
 * @returns {Promise<number>}
 */
export async function fetchUserPendingCount(userId) {
  try {
    const config = await configPromise;
    const baseApi = config.api.baseUrl.replace(/\/$/, "");
    const endpoint = config.api.endpoints?.loans || "/loans";
    const url = `${baseApi}${endpoint}/user/${userId}?status=pending`;

    const res = await fetch(url);
    const data = await res.json();

    if (data && Array.isArray(data.loans)) {
      return data.loans.length;
    }
    return 0;
  } catch (err) {
    console.error("Failed to fetch pending loan count:", err);
    return 0;
  }
}

export async function fetchUserCopyLoanStatuses(userId) {
  try {
    console.log(
      "[fetchUserCopyLoanStatuses] Requesting loan statuses for user:",
      userId
    );
    const res = await fetch(`/api/loans/user/${userId}`);
    const data = await res.json();
    console.log("[fetchUserCopyLoanStatuses] Raw API response:", data);

    if (data && Array.isArray(data.loans)) {
      const statusMap = {};
      data.loans.forEach((loan) => {
        statusMap[loan.copy_id] = loan.status; // or loan.loan_status, based on your field!
      });
      console.log("[fetchUserCopyLoanStatuses] Built statusMap:", statusMap);
      return statusMap;
    }

    console.warn(
      "[fetchUserCopyLoanStatuses] No loans array found, returning empty object"
    );
    return {};
  } catch (err) {
    console.error("Failed to fetch user copy loan statuses:", err);
    return {};
  }
}

export function transformBackendItemToFrontend(item, type = "book") {
  return {
    id: item.book_id ?? item.thesis_id ?? null,
    title: item.title ?? "",
    author: item.author ?? item.authors ?? "Unknown",
    year: item.year ?? item.publication_year ?? undefined,
    desc: item.desc ?? item.description ?? "",
    tags:
      type === "thesis"
        ? []
        : Array.isArray(item.tags)
        ? item.tags
        : Array.isArray(item.genres)
        ? item.genres
        : (item.tags || item.genres || "")
            .toString()
            .split(",")
            .map((tag) => tag.trim())
            .filter((tag) => tag.length > 0) || [],
    cover_image: item.cover ?? item.cover_image ?? "/assets/images/nocover.png",
    available: item.available ?? item.available_copies ?? 0,
    copy_id: item.copy_id ?? null,
    copies: item.copies ?? [],
    type: type,
  };
}
