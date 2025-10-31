// main.js — entry point for the Admin Dashboard

import { setupNavigation } from "./navigation.js";
import { renderOverview } from "./renderOverview.js";
import { renderBookManagement } from "./renderBookManagement.js";
// import { renderBorrowManagement } from "./renderBorrowManagement.js";
// later: import { renderUserManagement } from "./renderUserManagement.js";
// etc.

/**
 * Map page IDs (from <section id="..."> in your PHP template)
 * to their corresponding render functions.
 */
const pageRenderers = {
  overview: renderOverview,
  "book-management": renderBookManagement,
  // "borrow-management": renderBorrowManagement,
  // "user-management": renderUserManagement,
  // add more as you build them
};

/**
 * Called by navigation.js whenever a page is switched.
 * @param {string} pageId
 */
function renderContent(pageId) {
  const renderer = pageRenderers[pageId];
  const container = document.getElementById(pageId);
  if (renderer && container) {
    renderer(container);
  } else {
    console.warn(
      `No renderer defined or container missing for page: ${pageId}`
    );
  }
}

// Initialize sidebar navigation and page rendering
setupNavigation(renderContent);
