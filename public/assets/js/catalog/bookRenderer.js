// public/assets/js/catalog/bookRenderer.js

import { getCatalogItems } from "./stateManager.js";
import { bookCatalog } from "./domSelectors.js";
import { getLibraryIdStatus } from "./libraryId.js";

// Creates a single card element for a book/thesis
async function createCatalogCard(item, userLibraryIdStatus = "none") {
  const isAvailable = item.available > 0;
  const isThesis = item.type === "thesis";

  const card = document.createElement("div");
  card.className = "book-card";

  let badgeText = "";
  let badgeClass = "";
  if (isThesis) {
    badgeText = "In Library";
    badgeClass = "thesis";
  } else if (isAvailable) {
    badgeText = `${item.available} Available`;
    badgeClass = "available";
  } else {
    badgeText = `0 / ${item.copies} Copies`;
    badgeClass = "unavailable";
  }

  // Build tags section only for books, not theses
  const tagsSection =
    !isThesis && item.tags && item.tags.length > 0
      ? `<div class="book-meta">
        <span class="book-tags">${item.tags.join(", ")}</span>
      </div>`
      : "";

  // Library ID status check (for books only)
  let borrowBtn = "";
  if (!isThesis) {
    let canBorrow = false;
    let borrowBtnText = "Borrow";
    let borrowBtnClass = "unavailable";
    let borrowBtnDisabled = true;

    // Check userLibraryId only if book is available
    if (isAvailable) {
      if (userLibraryIdStatus === "active") {
        canBorrow = true;
        borrowBtnClass = "available";
        borrowBtnDisabled = false;
        borrowBtnText = "Borrow";
      } else if (userLibraryIdStatus === "pending") {
        borrowBtnText = "ID Pending Approval";
        borrowBtnDisabled = true;
      } else if (userLibraryIdStatus === "error") {
        borrowBtnText = "Library ID Required";
        borrowBtnDisabled = true;
      } else {
        // No library ID or not logged in
        borrowBtnText = window.userData?.userId
          ? "Library ID Required"
          : "Login to Borrow";
        borrowBtnDisabled = true;
      }
    } else {
      borrowBtnText = "Unavailable";
      borrowBtnDisabled = true;
    }

    borrowBtn = `<button class="borrow-btn ${borrowBtnClass}" data-book-id="${
      item.id
    }" ${borrowBtnDisabled ? "disabled" : ""}>
      ${borrowBtnText}
    </button>`;
  }

  card.innerHTML = `
    <div class="book-cover-wrapper">
      <img src="${item.cover_image}" alt="${item.title}" class="book-cover">
      <span class="card-badge ${badgeClass}">${badgeText}</span>
    </div>
    <div class="book-info">
      <h3 class="book-title">${item.title}</h3>
      <p class="book-author">by ${item.author}${
    item.year ? ` (${item.year})` : ""
  }</p>
      ${tagsSection}
      <p class="book-description">${item.desc}</p>
      <div class="book-card-action">
        ${isThesis ? "" : borrowBtn}
      </div>
    </div>
  `;
  return card;
}

export async function renderBooks() {
  let items = getCatalogItems();
  bookCatalog.innerHTML = "";
  if (!items || !items.length) {
    bookCatalog.innerHTML = `<p>No items match your criteria.</p>`;
    return;
  }

  // Get library ID status once for all cards (performance optimization)
  const userId = window.userData?.userId;
  let userLibraryIdStatus = "none";
  if (userId) {
    try {
      const { status } = await getLibraryIdStatus(userId);
      userLibraryIdStatus = status;
    } catch (error) {
      console.error("Error getting library ID status:", error);
      userLibraryIdStatus = "error";
    }
  }

  // Sort: items with actual covers (not the no-cover path) come first
  items = items.slice().sort((a, b) => {
    // Adjust property if needed; using 'cover' as per your code
    const noCoverA = isNoCover(a.cover_image);
    const noCoverB = isNoCover(b.cover_image);
    if (noCoverA === noCoverB) return 0;
    return noCoverA ? 1 : -1; // put real covers at the top
  });

  try {
    // Create all cards concurrently for better performance
    const cardPromises = items.map((item) =>
      createCatalogCard(item, userLibraryIdStatus)
    );
    const cards = await Promise.all(cardPromises);

    // Append all cards to the DOM
    cards.forEach((card) => {
      bookCatalog.appendChild(card);
    });
  } catch (error) {
    console.error("Error rendering books:", error);
    bookCatalog.innerHTML = `<p>Error loading books. Please try again.</p>`;
  }
}

// Helper function: check if cover is the default
function isNoCover(coverUrl) {
  return (
    !coverUrl ||
    coverUrl.endsWith("/logo.png") ||
    coverUrl.endsWith("/nocover.png") ||
    coverUrl.endsWith("/no-cover.png")
  );
}
