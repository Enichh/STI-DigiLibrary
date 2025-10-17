// Render a list of theses without images
export function renderTheses(theses, containerId = "thesisList") {
  const container = document.getElementById(containerId);
  container.innerHTML = "";
  if (!Array.isArray(theses) || theses.length === 0) {
    container.innerHTML = "<p>No theses found.</p>";
    return;
  }

  theses.forEach((t) => {
    const title = t.title || "";
    const authorParts = [t.first_name, t.middle_name, t.last_name]
      .filter(Boolean)
      .join(" ")
      .replace(/\s+/g, " ")
      .trim();
    const author = authorParts || "Unknown Author";
    const year = t.pub_year ?? "";
    const callNo = t.call_no || "";
    const accession = t.accession_no || "";
    const pages =
      typeof t.pages === "number"
        ? `${t.pages} pages`
        : t.pages_note
        ? t.pages_note
        : "";

    const article = document.createElement("article");
    article.className = "card thesis"; // Use 'card' for similar card styles as books

    // Card structure without image or borrow button
    article.innerHTML = `
      <h3 class="thesis-title">${escapeHtml(title)}</h3>
      <p class="thesis-meta"><span class="thesis-author">${escapeHtml(
        author
      )}</span>${year ? " • " + escapeHtml(String(year)) : ""}</p>
      <p class="thesis-call"><strong>Call No:</strong> ${escapeHtml(callNo)}</p>
      <p class="thesis-accession"><strong>Accession:</strong> ${escapeHtml(
        accession
      )}</p>
      ${
        pages
          ? `<p class="thesis-pages"><strong>Pages:</strong> ${escapeHtml(
              pages
            )}</p>`
          : ""
      }
    `;
    container.appendChild(article);
  });
}

// Simple pager renderer compatible with your catalog styles
// Store the pager container element reference
let pagerContainer = null;

export function renderPagination(container, pagination) {
  // Only set up the container reference once
  if (!pagerContainer) {
    pagerContainer =
      typeof container === "string"
        ? document.querySelector(container)
        : container;
  }

  if (!pagerContainer || !pagination) return;

  const { page = 1, totalPages = 1 } = pagination;

  // Create a document fragment to hold our new content
  const fragment = document.createDocumentFragment();

  // Create and append previous button
  const prevBtn = document.createElement("button");
  prevBtn.dataset.act = "prev";
  prevBtn.textContent = "Previous";
  prevBtn.disabled = page <= 1;
  fragment.appendChild(prevBtn);

  // Create and append page info
  const pageSpan = document.createElement("span");
  pageSpan.textContent = `Page ${page} of ${Math.max(1, totalPages)}`;
  fragment.appendChild(pageSpan);

  // Create and append next button
  const nextBtn = document.createElement("button");
  nextBtn.dataset.act = "next";
  nextBtn.textContent = "Next";
  nextBtn.disabled = page >= totalPages;
  fragment.appendChild(nextBtn);

  // Clear and update the container
  while (pagerContainer.firstChild) {
    pagerContainer.removeChild(pagerContainer.firstChild);
  }
  pagerContainer.appendChild(fragment);
}

// Basic output encoding to avoid accidental HTML injection in titles/fields
function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
