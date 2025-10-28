import { getCatalogItems } from "./stateManager.js";
import { bookCatalog } from "./domSelectors.js";
import { getLibraryIdStatus } from "./libraryId.js";
import { fetchUserCopyLoanStatuses } from "./helpers.js";

// public/assets/js/catalog/bookRenderer.js

// Creates a single card element for a book/thesis (per-book card)
async function createCatalogCard(
  item, // book-level object that includes copies: []
  userLibraryIdStatus = "none",
  userCopyLoanStatusMap = {}
) {
  const isThesis = item.type === "thesis";

  // Compute pending for the current user across this book's copies
  const hasCopies = Array.isArray(item.copies) && item.copies.length > 0;
  const isPendingForMe =
    hasCopies &&
    item.copies.some((c) => userCopyLoanStatusMap[c.copy_id] === "pending");

  // Decide which copy_id this card should act on when borrowing:
  // Prefer the user's pending copy for display; else first available; else null
  const chosenCopy =
    (hasCopies &&
      item.copies.find(
        (c) => userCopyLoanStatusMap[c.copy_id] === "pending"
      )) ||
    (hasCopies && item.copies.find((c) => c.status === "available")) ||
    null;

  // Availability values can arrive as strings; normalize to number
  const availableCount = Number(item.available ?? item.available_copies ?? 0);
  const totalCount = Number(item.total_copies ?? item.copies?.length ?? 0);
  const isAvailable = availableCount > 0;

  const card = document.createElement("div");
  card.className = "book-card";

  // Badge logic (per-book)
  let badgeText = "";
  let badgeClass = "";
  if (isThesis) {
    badgeText = "In Library";
    badgeClass = "thesis";
  } else if (isPendingForMe) {
    badgeText = "Pending";
    badgeClass = "pending";
  } else if (isAvailable) {
    badgeText = `${availableCount} Available`;
    badgeClass = "available";
  } else {
    badgeText = `0 / ${totalCount} Copies`;
    badgeClass = "unavailable";
  }

  // Tags section (books only)
  const tagsSection =
    !isThesis && item.tags && item.tags.length > 0
      ? `<div class="book-meta">
          <span class="book-tags">${item.tags.join(", ")}</span>
        </div>`
      : "";

  // Borrow button logic (books only)
  let borrowBtn = "";
  if (!isThesis) {
    let borrowBtnText = "Borrow";
    let borrowBtnClass = "unavailable";
    let borrowBtnDisabled = true;

    if (isPendingForMe) {
      borrowBtnText = "Pending Approval";
      borrowBtnClass = "pending";
      borrowBtnDisabled = true;
    } else if (isAvailable && chosenCopy) {
      if (userLibraryIdStatus === "active") {
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
        borrowBtnText = window.userData?.userId
          ? "Library ID Required"
          : "Login to Borrow";
        borrowBtnDisabled = true;
      }
    } else {
      borrowBtnText = "Unavailable";
      borrowBtnDisabled = true;
    }

    // Include chosen copy_id for downstream borrow action
    borrowBtn = `<button class="borrow-btn ${borrowBtnClass}"
                        data-book-id="${item.id}"
                        data-copy-id="${chosenCopy ? chosenCopy.copy_id : ""}"
                        ${borrowBtnDisabled ? "disabled" : ""}>
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
  let items = getCatalogItems(); // books, each with copies: []
  bookCatalog.innerHTML = "";
  if (!items || !items.length) {
    bookCatalog.innerHTML = `<p>No items match your criteria.</p>`;
    return;
  }

  // Get user ID and library ID status
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

  let userCopyLoanStatusMap = {};
  if (userId) {
    try {
      userCopyLoanStatusMap = await fetchUserCopyLoanStatuses(userId);
    } catch (err) {
      userCopyLoanStatusMap = {};
    }
  }

  // Sort: items with actual covers (not the no-cover path) come first
  items = items.slice().sort((a, b) => {
    const noCoverA = isNoCover(a.cover_image);
    const noCoverB = isNoCover(b.cover_image);
    if (noCoverA === noCoverB) return 0;
    return noCoverA ? 1 : -1;
  });

  try {
    // Create all cards concurrently and pass user status map (per-book cards)
    const cardPromises = items.map((item) =>
      createCatalogCard(item, userLibraryIdStatus, userCopyLoanStatusMap)
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

// Helper: is default cover
function isNoCover(coverUrl) {
  return (
    !coverUrl ||
    coverUrl.endsWith("/logo.png") ||
    coverUrl.endsWith("/nocover.png") ||
    coverUrl.endsWith("/no-cover.png")
  );
}
