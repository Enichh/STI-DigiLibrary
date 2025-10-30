// assets/js/admin/apiOverview.js

import { apiRequest } from "./helpers.js";
import { getConfig } from "../config.js";

/**
 * Fetches the count of unique books.
 * @returns {Promise<number>}
 */
export async function fetchTotalUniqueBooks() {
  const { api } = await getConfig();
  const res = await apiRequest(`${api.baseUrl}${api.endpoints.books}/count`);
  return res.count;
}

/**
 * Fetches the count of total book copies in the collection.
 * @returns {Promise<number>}
 */
export async function fetchTotalCopies() {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.books}/copies/count`
  );
  return res.count;
}

/**
 * Fetches the count of checked-out book copies.
 * @returns {Promise<number>}
 */
export async function fetchCheckedOutCount() {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.books}/copies/count?status=checked_out`
  );
  return res.count;
}

/**
 * Fetches the count of available book copies.
 * @returns {Promise<number>}
 */
export async function fetchAvailableCopies() {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.books}/copies/count?status=available`
  );
  return res.count;
}

/**
 * Fetches recent borrowing activity for dashboard display.
 * @param {number} limit - Number of activities to fetch.
 * @returns {Promise<any[]>}
 */
export async function fetchRecentActivity(limit = 5) {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.loans}/recent?limit=${limit}`
  );
  return res;
}

/**
 * Fetches recent admin notifications for dashboard display.
 * @param {number} limit - Number of notifications to fetch.
 * @returns {Promise<any[]>}
 */
export async function fetchAdminNotifications(limit = 5) {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.notifications}/recent?limit=${limit}`
  );
  return res;
}

export async function fetchUpcomingDueDates(limit = 3) {
  const { api } = await getConfig();
  return apiRequest(
    `${api.baseUrl}${api.endpoints.loans}/upcoming-due?limit=${limit}`
  );
}

export async function fetchAdminActionItems() {
  const { api } = await getConfig();
  return apiRequest(`${api.baseUrl}${api.endpoints.loans}/action-items`);
}

export async function fetchBorrowerCount() {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.users}/borrower-count`
  );
  return res.count;
}

export async function fetchLibraryIdCount() {
  const { api } = await getConfig();
  const res = await apiRequest(
    `${api.baseUrl}${api.endpoints.libraryIds}/count`
  );
  return res.count;
}

export async function fetchTotalTheses() {
  const { api } = await getConfig();
  const res = await apiRequest(`${api.baseUrl}${api.endpoints.theses}/count`);
  return res.count;
}
