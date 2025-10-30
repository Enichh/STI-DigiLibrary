// helpers.js

/**
 * Generic API request wrapper with error handling.
 * @param {string} url - The endpoint URL.
 * @param {object} options - Fetch options (method, headers, body, etc.).
 * @returns {Promise<any>} - Parsed JSON response.
 */
export async function apiRequest(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { "Content-Type": "application/json" },
      credentials: "include", // include cookies/session if needed
      ...options,
    });

    if (!res.ok) {
      const errorText = await res.text();
      throw new Error(`API error ${res.status}: ${errorText}`);
    }

    return await res.json();
  } catch (err) {
    handleApiError(err);
    throw err;
  }
}

/**
 * Centralized error handler.
 * @param {Error} error - The error object.
 */
export function handleApiError(error) {
  console.error("API Error:", error);
  alert("Something went wrong. Please try again.");
}

/**
 * Serialize a form into a plain object.
 * @param {HTMLFormElement} formElement
 * @returns {object}
 */
export function serializeForm(formElement) {
  const formData = new FormData(formElement);
  return Object.fromEntries(formData.entries());
}

/**
 * Open a modal by ID.
 * @param {string} id
 */
export function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = "block";
}

/**
 * Close a modal by ID.
 * @param {string} id
 */
export function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = "none";
}

/**
 * Format a date string into a human‑readable format.
 * @param {string|Date} dateStr
 * @returns {string}
 */
export function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString();
}

/**
 * Format a number into Philippine Peso currency.
 * @param {number} amount
 * @returns {string}
 */
export function formatCurrency(amount) {
  return `₱${parseFloat(amount).toFixed(2)}`;
}

/**
 * Utility to clear and repopulate a container element.
 * @param {HTMLElement} container
 * @param {string} html
 */
export function renderHTML(container, html) {
  if (container) container.innerHTML = html;
}
