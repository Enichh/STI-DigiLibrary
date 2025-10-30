// admin/navigation.js

/**
 * Sets up sidebar navigation and handles dynamic page switching.
 * @param {Function} renderContent - Function to render the page content for a given page ID.
 */
export function setupNavigation(renderContent) {
  const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");
  const pageContents = document.querySelectorAll(".page-content");
  const pageTitle = document.getElementById("page-title");

  // Track current page to avoid redundant re-renders
  let currentPage = null;

  /**
   * Handles switching to a page based on hash and re-renders content.
   * @param {string} hash - Page hash (e.g., '#overview')
   */
  function switchPage(hash) {
    const pageId = (hash || "#overview").substring(1);

    // Skip if already on this page
    if (pageId === currentPage) return;
    currentPage = pageId;

    // Validate pageId against existing sections
    const validIds = Array.from(pageContents).map((p) => p.id);
    if (!validIds.includes(pageId)) {
      return switchPage("#overview");
    }

    // Highlight the active nav link
    navLinks.forEach((link) => {
      link.classList.toggle(
        "active",
        link.getAttribute("href") === `#${pageId}`
      );
    });

    // Show only the correct .page-content and hide others
    pageContents.forEach((page) => {
      page.classList.toggle("active", page.id === pageId);
    });

    // Update the page title text
    const activeLink = document.querySelector(`.nav-link[href="#${pageId}"]`);
    pageTitle.textContent = activeLink
      ? activeLink.textContent.trim()
      : "Dashboard";

    // Accessibility: move focus to the active section
    const activeSection = document.getElementById(pageId);
    if (activeSection) {
      activeSection.setAttribute("tabindex", "-1");
      activeSection.focus();
    }

    // Trigger app-specific rendering
    renderContent(pageId);
  }

  // Set up click listeners for sidebar navigation
  navLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const hash = e.currentTarget.getAttribute("href");
      window.location.hash = hash;
      switchPage(hash);
    });
  });

  // On initial load, switch to the hash in the URL or default to #overview
  const initialHash = window.location.hash || "#overview";
  switchPage(initialHash);

  // Handle browser back/forward navigation
  window.addEventListener("hashchange", () => {
    switchPage(window.location.hash);
  });
}
