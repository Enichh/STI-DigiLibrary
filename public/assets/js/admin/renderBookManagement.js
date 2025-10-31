import { generateOptions, showCustomAlert } from "./helpers.js";
import {
  fetchCatalogItems,
  createBook,
  updateBook,
  deleteBook,
  createThesis,
  updateThesis,
  deleteThesis,
} from "./apiBookManagement.js";

const state = {
  viewMode: "grid",
  filterType: "all",
  genreFilter: "all",
  searchTerm: "",
  currentPage: 1,
  itemsPerPage: 12,
  itemToDeleteId: null,
  items: [], // Store the current items in state
};

const SHELF_LOCATIONS = ["CIR", "REF", "THESIS", "FIC", "RES", "FIL", "GEN"];
const CLASSIFICATION_CODES = ["QA", "HF", "TH", "P", "Z", "TK", "DS", "E", "F"];
const GENRE_OPTIONS = [
  "Business & IT Applications",
  "Computer Ethics & IT in Society",
  "Computer Science Fundamentals",
  "Database Systems",
  "Digital Media & Graphics",
  "Educational & Reference",
  "Game Development",
  "Hardware & Architecture",
  "Mathematics & Discrete Math",
  "Networking & Security",
  "Operating Systems",
  "Programming & Software Development",
  "Software Engineering & Project Management",
  "Systems Analysis & Design",
  "Web Development & Internet Technologies",
];

function safeGet(value, placeholder = "N/A") {
  return value !== null && value !== undefined && value !== ""
    ? value
    : placeholder;
}

function formatCallNumber(shelfOrObj, classCode, classNum, cutter, year) {
  let parts;
  if (typeof shelfOrObj === "object" && shelfOrObj !== null) {
    parts = {
      shelf: shelfOrObj.callNumber?.shelf,
      classCode: shelfOrObj.callNumber?.classificationCode,
      classNum: shelfOrObj.callNumber?.classificationNumber,
      cutter: shelfOrObj.callNumber?.cutter,
      year: shelfOrObj.year,
    };
  } else {
    parts = { shelf: shelfOrObj, classCode, classNum, cutter, year };
  }
  const displayYear = parts.year || "----";
  const displayParts = [
    parts.shelf,
    parts.classCode,
    parts.classNum,
    parts.cutter,
    displayYear,
  ].filter((part) => part !== null && part !== undefined && part !== "");
  return displayParts.length > 0 ? displayParts.join(" ") : "N/A";
}

function updateCallNumberPreview(form, prefix) {
  const previewEl = form.querySelector(`#${prefix}-call-number-preview`);
  if (!previewEl) return;
  const shelf = form.querySelector(`#${prefix}-shelf`)?.value || "";
  const classCode = form.querySelector(`#${prefix}-class-code`)?.value || "";
  const classNum = form.querySelector(`#${prefix}-class-num`)?.value || "";
  const cutter = form.querySelector(`#${prefix}-cutter`)?.value || "";
  const year = form.querySelector(`#${prefix}-year`)?.value || "";
  previewEl.textContent = formatCallNumber(
    shelf,
    classCode,
    classNum,
    cutter,
    year
  );
}

export {
  state,
  SHELF_LOCATIONS,
  CLASSIFICATION_CODES,
  GENRE_OPTIONS,
  safeGet,
  formatCallNumber,
  updateCallNumberPreview,
  generateOptions,
};

// =============================================================================
// SECTION 3: MAIN RENDERING FUNCTIONS
// Summary: Functions responsible for drawing the module UI.
// =============================================================================

export function renderBookManagement(container) {
  container.innerHTML = `
    <p class="dashboard-welcome-text">
      Manage the library's collection of books and theses. Add, edit, or remove items.
    </p>
    <div class="card book-management-controls">
      <div class="control-row add-buttons-row">
        <div class="add-buttons">
          <button id="add-book-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Add Book</button>
          <button id="add-thesis-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Add Thesis</button>
        </div>
      </div>
      <div class="control-row search-filter-row">
        <div class="search-genre-group">
          <input type="text" id="bm-search" class="form-control search-input" placeholder="Search title, author, ISBN..." value="${
            state.searchTerm
          }">
          <select id="bm-genre-filter" class="form-control">
            ${generateOptions(GENRE_OPTIONS, state.genreFilter, "All Genres")}
          </select>
        </div>
        <div class="filters-view-group">
          <div class="type-toggle">
            <button class="bm-control-btn filter-btn ${
              state.filterType === "all" ? "active" : ""
            }" data-filter="all">All</button>
            <button class="bm-control-btn filter-btn ${
              state.filterType === "book" ? "active" : ""
            }" data-filter="book">Books</button>
            <button class="bm-control-btn filter-btn ${
              state.filterType === "thesis" ? "active" : ""
            }" data-filter="thesis">Theses</button>
          </div>
          <div class="view-toggle">
            <button class="bm-control-btn view-btn ${
              state.viewMode === "grid" ? "active" : ""
            }" data-view="grid" title="Grid View"><i class="fas fa-th-large"></i></button>
            <button class="bm-control-btn view-btn ${
              state.viewMode === "list" ? "active" : ""
            }" data-view="list" title="List View"><i class="fas fa-list"></i></button>
          </div>
        </div>
      </div>
    </div>
    <div id="bm-collection-area" class="card" style="margin-top: 20px;"></div>
    <style>
        /* Control Bar Layout */
        .book-management-controls { margin:0px; padding:15px; }
        .control-row {  margin-top: 15px;display: flex; align-items: center; gap: 15px; width: 100%; }
        .add-buttons-row { padding-bottom: 15px; border-bottom: 1px solid var(--lightgray); justify-content: flex-start; }
        .search-filter-row { justify-content: space-between; flex-wrap: wrap; padding-bottom: 15px; }
        /* NEW: Group for search and genre */
        .search-genre-group {
            display: flex;
            flex-grow: 1;
            gap: 15px;
            align-items: center;
            min-width: 300px;
            margin-right: 15px;
        }
        .search-genre-group .search-input {
            flex-grow: 1;
            min-width: 200px;
        }
        .search-genre-group .form-control {
            width: auto;
            min-width: 150px;
            flex-shrink: 0;
        }
        .filters-view-group { display: flex; align-items: center; gap: 10px; flex-shrink: 0; flex-wrap: wrap; }
        /* Grid View */
       .bm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
       .bm-book-card {
          background-color: var(--white);
          border-radius: var(--border-radius);
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
          display: flex;
          flex-direction: column;
          overflow: hidden;
          transition: transform 0.2s;
       }
        .bm-book-card:hover { transform: translateY(-5px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .bm-book-card img { width: 100%; height: 180px; object-fit: contain; background-color: #f0f0f0; border-bottom: 1px solid var(--lightgray); padding: 10px; }
        .bm-book-card-info { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .bm-book-card-info h4 { margin: 0 0 5px 0; font-size: 1rem; color: var(--darkblue); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bm-book-card-info p {
            margin: 0 0 10px 0;
            font-size: 0.85rem;
            color: #666;
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .bm-book-card-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: #777; margin-bottom: 10px; padding-top: 10px; border-top: 1px solid var(--lightgray); }
        .bm-call-number {
            margin: 0 0 10px 0;
            font-size: 0.85rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bm-call-number strong {
            color: var(--darkblue);
            font-weight: 600;
            margin-right: 5px;
        }

        .bm-book-card-actions { display: flex; gap: 10px; margin-top: auto; }
        /* List View (Table) */
        .bm-list { overflow-x: auto; }
        .bm-list .data-table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1200px;
        }
        .bm-list .data-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--blue);
        }
        .bm-list .data-table th:last-child,
        .bm-list .data-table td:last-child {
            position: sticky;
            right: 0;
            z-index: 11;
        }
        .bm-list .data-table th:last-child {
            background-color: var(--blue);
        }
        .bm-list .data-table td:last-child {
            background-color: var(--white);
        }
        .bm-list .data-table tbody tr:nth-of-type(even) td:last-child {
            background-color: #f9f9f9;
        }
        .bm-list .data-table img.cover-thumb { width: 40px; height: 50px; object-fit: contain; border-radius: 4px; vertical-align: middle; margin-right: 10px; background-color: #f0f0f0; padding: 5px; }
        .bm-list .data-table .action-btns { text-align: center; vertical-align: middle; }
        .bm-list .data-table .action-btns button { margin: 2px; }
        .bm-list .data-table td.description-cell { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bm-list .data-table td.description-cell:hover { white-space: normal; overflow: visible; word-wrap: break-word; }
         .bm-list .data-table th:nth-child(1), .bm-list .data-table td:nth-child(1) { width: 70px; }
         .bm-list .data-table th:nth-child(2), .bm-list .data-table td:nth-child(2) { min-width: 200px; }
         .bm-list .data-table th:nth-child(3), .bm-list .data-table td:nth-child(3) { min-width: 150px; }
         .bm-list .data-table th:nth-child(13),  .bm-list .data-table td:nth-child(13)  { min-width: 180px; }
         .bm-list .data-table th:last-child,  .bm-list .data-table td:last-child  { width: 120px; min-width: 120px; }
         .call-number-preview {
            grid-column: 1 / -1;
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-top: 5px;
            font-family: monospace;
            font-size: 0.95rem;
            color: #333;
            border: 1px solid #ced4da;
            min-height: 40px;
            display: flex;
            align-items: center;
         }
        .call-number-preview strong { margin-right: 10px; font-weight: 600; color: var(--darkblue); }
        #add-book-modal .modal-content,
        #add-thesis-modal .modal-content {
            display: flex;
            flex-direction: column;
            max-height: 85vh;
            height: auto;
            width: 80%;
            max-width: 700px;
        }
        #add-book-modal .modal-content h2,
        #add-thesis-modal .modal-content h2 {
            flex-shrink: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--lightgray);
        }
        #add-book-form,
        #add-thesis-form {
            flex-grow: 1;
            overflow-y: auto;
            padding: 5px 20px 5px 5px;
            margin-right: -15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        #add-book-form .form-group.full-width[style*="text-align: right"],
        #add-thesis-form .form-group.full-width[style*="text-align: right"] {
            flex-shrink: 0;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--lightgray);
            background-color: #fefefe;
            grid-column: 1 / -1;
        }
        .type-toggle, .view-toggle {
            display: inline-flex;
        }
        .bm-control-btn {
            background-color: transparent;
            color: var(--blue);
            border: 1px solid var(--blue);
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
            border-right: none;
            border-radius: 0;
        }
        .bm-control-btn.active,
        .bm-control-btn:hover {
            background-color: var(--blue);
            color: var(--white);
        }
            

        
        .type-toggle .bm-control-btn:first-child,
        .view-toggle .bm-control-btn:first-child {
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }
        .type-toggle .bm-control-btn:last-child,
        .view-toggle .bm-control-btn:last-child {
            border-right: 1px solid var(--blue);
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }
        .edit-btn { background-color: var(--lightblue); color: white; }
        .edit-btn:hover { background-color: var(--blue); }
        .delete-btn { background-color: var(--danger); color: white; }
        .delete-btn:hover { background-color: #c0392b; }
        .empty-state-text {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .control-row.search-filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            .search-genre-group {
                flex-direction: column;
                width: 100%;
                margin-right: 0;
                gap: 10px;
            }
            .search-genre-group .search-input,
            .search-genre-group .form-control {
                width: 100%;
                min-width: 0;
            }
            .filters-view-group {
                width: 100%;
                justify-content: space-between;
            }
            .type-toggle, .view-toggle {
                flex-shrink: 0;
            }
            .add-buttons-row {
                flex-direction: column;
                align-items: stretch;
            }
            .add-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
                gap: 10px;
            }
            .add-buttons .btn {
                width: 100%;
            }
            .bm-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }
        @media (max-width: 480px) {
            .bm-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
  `;
  attachControlListeners(container);
  renderCollection();
}

function renderPagination(pagination) {
  if (!pagination || pagination.totalPages <= 1) return "";
  return `
    <div class="pagination-controls" style="display:flex;align-items:center;justify-content:center;margin:24px 0;gap:12px;">
      <button class="btn btn-primary" id="bm-page-prev" ${
        pagination.page <= 1 ? "disabled" : ""
      }>
        <i class="fas fa-chevron-left"></i> Previous
      </button>
      <span style="font-weight:500;color:var(--blue);background:#f3f7fa;padding:8px 18px;border-radius:8px;">
        Page ${pagination.page} of ${pagination.totalPages}
      </span>
      <button class="btn btn-primary" id="bm-page-next" ${
        pagination.page >= pagination.totalPages ? "disabled" : ""
      }>
        Next <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  `;
}

async function renderCollection() {
  const collectionArea = document.getElementById("bm-collection-area");
  if (!collectionArea) return;

  // Build filters ONLY once, cleanly!
  const filters = {};
  if (state.searchTerm) filters.search = state.searchTerm;
  if (state.filterType !== "all") filters.type = state.filterType;
  if (
    state.genreFilter &&
    state.genreFilter !== "all" &&
    state.genreFilter !== undefined
  ) {
    // Ensure consistent formatting of the genre filter
    const cleanGenre = state.genreFilter.trim();

    // Filtering by genre

    // Use the exact value from GENRE_OPTIONS to ensure consistency
    const exactGenre = GENRE_OPTIONS.find(
      (g) => g.trim().toLowerCase() === cleanGenre.toLowerCase()
    );

    if (exactGenre) {
      filters.genre = exactGenre;
      // Using exact genre match
    } else {
      filters.genre = cleanGenre;
    }
  }

  let items = [];
  let pagination = null;

  try {
    // Use ONLY the constructed filters object
    const apiResponse = await fetchCatalogItems(
      filters,
      state.currentPage,
      state.itemsPerPage
    );
    items = apiResponse.data || [];
    state.items = [...items]; // Store items in state for global access
    pagination = apiResponse.pagination;
  } catch (err) {
    // Error fetching items
    collectionArea.innerHTML = `<p class="error">Failed to load items. Please try again later.</p>`;
    return;
  }

  if (!Array.isArray(items) || items.length === 0) {
    collectionArea.innerHTML = `<p class="empty-state-text">No items found matching your criteria.</p>`;
    return;
  }

  if (state.viewMode === "grid") {
    collectionArea.innerHTML = `
      <div class="bm-grid">
        ${items
          .map((item) =>
            "book_id" in item
              ? renderBookCard(item)
              : "thesis_id" in item
              ? renderThesisCard(item)
              : ""
          )
          .join("")}
      </div>
    `;
  } else {
    collectionArea.innerHTML = renderBookThesisTable(items);
  }

  if (pagination && pagination.totalPages > 1) {
    collectionArea.insertAdjacentHTML(
      "beforeend",
      renderPagination(pagination)
    );
    document.getElementById("bm-page-next")?.addEventListener("click", () => {
      if (state.currentPage < pagination.totalPages) {
        state.currentPage++;
        renderCollection();
      }
    });
    document.getElementById("bm-page-prev")?.addEventListener("click", () => {
      if (state.currentPage > 1) {
        state.currentPage--;
        renderCollection();
      }
    });
  }

  // Attach listeners using the current global items
  attachActionListeners(collectionArea, state.items);
}

function renderBookCard(item) {
  const defaultCover = "assets/images/logo.png";
  let coverUrl = defaultCover;

  if (item.cover_image) {
    const folder = item.type === "thesis" ? "theses_covers" : "covers";
    coverUrl = item.cover_image.startsWith("http")
      ? item.cover_image
      : `assets/${folder}/${item.cover_image.replace(/^.*[\\/]/, "")}`;
  }

  // Extract call_no from first copy (copies array exists for books)
  const callNumber = (item.copies && item.copies[0]?.call_no) || "N/A";

  return `
    <div class="bm-book-card" data-id="${item.book_id || item.thesis_id}">
      <img 
        src="${coverUrl}" 
        alt="${safeGet(item.title, "Untitled")}"
        onerror="this.onerror=null; this.src='${defaultCover}'; this.style.objectFit='contain'; this.style.padding='10px';">
      
      <div class="bm-book-card-info">
        <h4>${safeGet(item.title, "Untitled")}</h4>
        <p class="bm-author">${safeGet(
          item.authors || item.author,
          "Unknown Author"
        )}</p>
        
        <div class="bm-book-card-meta">
          <span>Total: ${safeGet(
            item.total_copies || item.copies?.length,
            "N/A"
          )}</span>
        </div>

        <div class="bm-call-number">
          <small>Call #:</small> ${callNumber}
        </div>

        <div class="bm-book-card-actions">
          <button class="btn btn-sm edit-btn" data-id="${
            item.book_id || item.thesis_id
          }"><i class="fas fa-edit"></i> Edit</button>
          <button class="btn btn-sm delete-btn" data-id="${
            item.book_id || item.thesis_id
          }"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
    </div>
  `;
}

function renderThesisCard(item) {
  const defaultCover = "assets/images/logo.png";
  let coverUrl = defaultCover;
  let match = (item.cover_image || "").match(
    /assets\/theses_covers\/([^)\[]+)/
  );
  if (item.cover_image && match) {
    coverUrl = `assets/theses_covers/${match[1]}`;
  }
  return `
    <div class="bm-book-card" data-id="${item.thesis_id}">
      <img src="${coverUrl}" alt="${safeGet(item.title, "Untitled")}"
        onerror="this.onerror=null; this.src='${defaultCover}'; this.style.objectFit='contain'; this.style.padding='10px';">
      <div class="bm-book-card-info">
        <h4>${safeGet(item.title, "Untitled")}</h4>
        <p>${safeGet(item.author, "Unknown Author")}</p>
        ${
          item.year
            ? `<div class="bm-book-card-meta"><span>${item.year}</span></div>`
            : ""
        }
        <div class="bm-book-card-actions">
          <button class="btn btn-sm edit-btn" data-id="${
            item.thesis_id
          }"><i class="fas fa-edit"></i> Edit</button>
          <button class="btn btn-sm delete-btn" data-id="${
            item.thesis_id
          }"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
    </div>
  `;
}

function renderBookThesisTable(items) {
  const defaultCover = "assets/images/logo.png";
  return `
    <div class="bm-list">
      <table class="data-table">
        <thead>
          <tr>
            <th>Cover</th>
            <th>Title</th>
            <th>Author(s)</th>
            <th>Year</th>
            <th>Accession/ISBN</th>
            <th>Call No.</th>
            <th>Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${items
            .map((item) => {
              // Book detection
              if ("book_id" in item) {
                let coverUrl =
                  item.cover_image && item.cover_image.startsWith("http")
                    ? item.cover_image
                    : `assets/covers/${(item.cover_image || "").replace(
                        /^.*[\\/]/,
                        ""
                      )}`;
                return `
                <tr data-id="${item.book_id}">
                  <td><img src="${coverUrl || defaultCover}" alt="${safeGet(
                  item.title
                )}" class="cover-thumb"
                    onerror="this.onerror=null; this.src='${defaultCover}';"></td>
                  <td>${safeGet(item.title)}</td>
                  <td>${safeGet(item.authors)}</td>
                  <td>${safeGet(item.publication_year, "-")}</td>
                  <td>${safeGet(item.isbn, "-")}</td>
                  <td>${safeGet(item.call_no, "-")}</td>
                  <td>Book</td>
                  <td class="action-btns">
                    <button class="btn btn-sm edit-btn" data-id="${
                      item.book_id
                    }"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm delete-btn" data-id="${
                      item.book_id
                    }"><i class="fas fa-trash"></i> Delete</button>
                  </td>
                </tr>
              `;
              }
              // Thesis detection
              if ("thesis_id" in item) {
                let match = (item.cover_image || "").match(
                  /assets\/theses_covers\/([^)\[]+)/
                );
                let coverUrl =
                  item.cover_image && match
                    ? `assets/theses_covers/${match[1]}`
                    : defaultCover;
                return `
                <tr data-id="${item.thesis_id}">
                  <td><img src="${coverUrl}" alt="${safeGet(
                  item.title
                )}" class="cover-thumb"
                    onerror="this.onerror=null; this.src='${defaultCover}';"></td>
                  <td>${safeGet(item.title)}</td>
                  <td>${safeGet(item.author)}</td>
                  <td>${safeGet(item.year, "-")}</td>
                  <td>${safeGet(item.accession_no, "-")}</td>
                  <td>${safeGet(item.call_no, "-")}</td>
                  <td>Thesis</td>
                  <td class="action-btns">
                    <button class="btn btn-sm edit-btn" data-id="${
                      item.thesis_id
                    }"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm delete-btn" data-id="${
                      item.thesis_id
                    }"><i class="fas fa-trash"></i> Delete</button>
                  </td>
                </tr>
              `;
              }
            })
            .join("")}
        </tbody>
      </table>
    </div>
  `;
}

// =============================================================================
// SECTION 4: EVENT LISTENER ATTACHMENT
// Summary: Functions to attach all event listeners for the module.
// =============================================================================

/**
 * Attaches listeners to the main control bar (filters, search, add, view).
 * @param {HTMLElement} container - The <section> element containing the controls.
 */
function attachControlListeners(container) {
  // Add Buttons
  container
    .querySelector("#add-book-btn")
    ?.addEventListener("click", () => openAddBookModal());
  container
    .querySelector("#add-thesis-btn")
    ?.addEventListener("click", () => openAddThesisModal());

  // Type Filter Buttons (All, Books, Theses)
  container.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const btn = e.currentTarget;
      state.filterType = btn.dataset.filter;
      // Update active class
      container
        .querySelectorAll(".filter-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      renderCollection();
    });
  });

  // Genre Filter Dropdown
  const genreFilterSelect = container.querySelector("#bm-genre-filter");
  if (genreFilterSelect) {
    // Ensure the UI matches the current state, using exact value from GENRE_OPTIONS if possible
    const currentGenre = GENRE_OPTIONS.includes(state.genreFilter)
      ? state.genreFilter
      : "all";
    genreFilterSelect.value = currentGenre;
    state.genreFilter = currentGenre; // Ensure state is in sync

    genreFilterSelect.addEventListener("change", (e) => {
      const selectedValue = e.target.value;

      // Find the exact match in GENRE_OPTIONS (case-insensitive)
      const exactGenre =
        GENRE_OPTIONS.find(
          (g) => g.toLowerCase() === selectedValue.toLowerCase()
        ) || selectedValue;

      state.genreFilter = exactGenre === "all" ? "all" : exactGenre;
      state.currentPage = 1; // Reset to first page when changing filters

      // Update the UI to reflect the exact value
      if (genreFilterSelect.value !== state.genreFilter) {
        genreFilterSelect.value = state.genreFilter;
      }

      // Genre filter changed
      renderCollection();
    });
  }

  // Search Input
  const searchInput = container.querySelector("#bm-search");
  if (searchInput) {
    // Use 'input' for real-time filtering, 'change' for on blur
    searchInput.addEventListener("input", (e) => {
      // Basic debounce to prevent re-render on every single key-press
      clearTimeout(searchInput.timer);
      searchInput.timer = setTimeout(() => {
        state.searchTerm = e.target.value;
        state.currentPage = 1;
        renderCollection();
      }, 300); // 300ms delay
    });
  }

  // View Mode Toggle (Grid, List)
  container.querySelectorAll(".view-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const btn = e.currentTarget;
      state.viewMode = btn.dataset.view;
      // Update active class
      container
        .querySelectorAll(".view-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      renderCollection();
    });
  });

  // Attach listeners for modal forms (only need to do this once)
  attachFormSubmitListeners();
  // Attach listener for delete confirmation modal
  attachDeleteConfirmListener();
}

/**
 * Attaches delegated event listeners to the collection area for Edit/Delete buttons,
 * using the current list of items (books/theses) for correct lookups.
 * @param {HTMLElement} collectionArea - The div#bm-collection-area element.
 */
function attachActionListeners(collectionArea) {
  collectionArea.addEventListener("click", (e) => {
    const target = e.target.closest("button");
    if (!target) return;

    const itemId = target.dataset.id || target.closest("[data-id]")?.dataset.id;
    if (!itemId) return;

    if (target.classList.contains("edit-btn")) {
      handleEditClick(itemId);
    } else if (target.classList.contains("delete-btn")) {
      handleDeleteClick(itemId);
    }
  });
}

/**
 * Attaches submit listeners to the modal forms and close listeners to modal buttons.
 * This function runs once when the module is initialized.
 */
function attachFormSubmitListeners() {
  // Form submission: Just attach as before
  document
    .getElementById("add-book-form")
    ?.addEventListener("submit", handleBookSubmit);
  document
    .getElementById("add-thesis-form")
    ?.addEventListener("submit", handleThesisSubmit);

  // Modal close buttons (X)
  document
    .querySelectorAll(
      "#add-book-modal .close-btn, #add-thesis-modal .close-btn"
    )
    .forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const modal = e.target.closest(".modal");
        if (modal) {
          modal.style.display = "none";
        }
      });
    });

  // Close modal on outside (backdrop) click
  window.addEventListener("click", (event) => {
    if (
      event.target.id === "add-book-modal" ||
      event.target.id === "add-thesis-modal"
    ) {
      event.target.style.display = "none";
    }
  });
}

/**
 * Attaches click listeners for the delete confirmation modal (Confirm, Cancel).
 * This function runs once when the module is initialized.
 */
function attachDeleteConfirmListener() {
  const modal = document.getElementById("deleteBookModal");
  if (!modal) return; // Exit if modal isn't in the main HTML

  // Confirm Delete
  modal
    .querySelector("#confirmDeleteBookBtn")
    ?.addEventListener("click", async () => {
      const itemId = state.itemToDeleteId;
      if (!itemId) {
        modal.style.display = "none";
        return;
      }

      // Find the item in the current state
      const item = state.items.find(
        (item) =>
          String(item.book_id) === String(itemId) ||
          String(item.thesis_id) === String(itemId)
      );

      if (!item) {
        showCustomAlert(
          "Error",
          "Item not found for deletion. The item may have been removed.",
          "error"
        );
        state.itemToDeleteId = null;
        modal.style.display = "none";
        return;
      }

      try {
        if (item.book_id) {
          await deleteBook(item.book_id);
        } else if (item.thesis_id) {
          await deleteThesis(item.thesis_id);
        }

        // Remove the item from state to keep UI in sync
        state.items = state.items.filter(
          (i) =>
            String(i.book_id) !== String(itemId) &&
            String(i.thesis_id) !== String(itemId)
        );

        showCustomAlert("Success", "Item successfully removed.", "success");
        // Re-render to reflect changes
        renderCollection();
      } catch (error) {
        // Error during delete
        showCustomAlert(
          "Error",
          `Failed to delete item: ${error.message || "Unknown error"}`,
          "error"
        );
      } finally {
        state.itemToDeleteId = null;
        modal.style.display = "none";
      }
    });

  // Cancel Delete
  modal.querySelector("#cancelDeleteBookBtn")?.addEventListener("click", () => {
    state.itemToDeleteId = null; // Reset
    modal.style.display = "none"; // Close modal
  });

  // Close (X) button
  modal
    .querySelector("#closeDeleteBookModal")
    ?.addEventListener("click", () => {
      state.itemToDeleteId = null; // Reset
      modal.style.display = "none"; // Close modal
    });
}

// =============================================================================
// SECTION 5: ACTION HANDLERS (CLICKS)
// Summary: Functions that respond directly to user clicks on Edit/Delete.
// =============================================================================

function handleEditClick(itemId) {
  const item = state.items.find(
    (b) =>
      String(b.book_id) === String(itemId) ||
      String(b.thesis_id) === String(itemId)
  );

  if (!item) {
    showCustomAlert("Item not found.", "error");
    return;
  }

  if ("book_id" in item) {
    openAddBookModal(item);
  } else if ("thesis_id" in item) {
    openAddThesisModal(item);
  }
}

/**
 * Delete handler—finds the item in state and prepares confirmation.
 * @param {string|number} itemId - The book_id or thesis_id.
 */
function handleDeleteClick(itemId) {
  const item = state.items.find(
    (b) =>
      String(b.book_id) === String(itemId) ||
      String(b.thesis_id) === String(itemId)
  );

  if (!item) {
    showCustomAlert("Item not found.", "error");
    return;
  }

  state.itemToDeleteId = itemId;

  // Show confirmation modal
  const modal = document.getElementById("deleteBookModal");
  const modalText = document.getElementById("deleteBookModalText");
  if (modal && modalText) {
    modalText.textContent = `Do you really want to delete "${item.title}"? This action cannot be undone.`;
    modal.style.display = "flex";
  }
}

// =============================================================================
// SECTION 6: MODAL MANAGEMENT FUNCTIONS
// Summary: Functions for opening and populating the Add/Edit modals.
// =============================================================================
/**
 * Populates form fields from an item object.
 * Handles both flat API responses and nested structures.
 * @param {HTMLFormElement} form - The form element
 * @param {Object} item - The item data (book or thesis)
 */
function populateFormFields(form, item) {
  if (!item) {
    console.warn("populateFormFields: No item provided");
    return;
  }

  console.log("=== POPULATE FORM FIELDS START ===");
  console.log("Item data:", item);

  // Map API response keys to form field names (handles both formats)
  const fieldMap = {
    title: item.title,
    author: item.authors || item.author, // API: authors or author
    isbn: item.isbn,
    genre: item.genres || item.genre, // API: genres or genre
    year: item.publication_year || item.year, // API: publication_year or year
    copies: item.total_copies || item.copies, // API: total_copies
    pages: item.pages,
    edition: item.edition,
    publisher: item.publisher_name || item.publisher, // API: publisher_name or publisher
    accessionCode: item.accession_no || item.accessionCode, // API: accession_no or accessionCode
    cover: item.cover_image || item.cover, // API: cover_image or cover
    description: item.description,
    shelf: item.callNumber?.shelf || item.shelf,
    classificationCode:
      item.callNumber?.classificationCode || item.classificationCode,
    classificationNumber:
      item.callNumber?.classificationNumber || item.classificationNumber,
    cutter: item.callNumber?.cutter || item.cutter,
  };

  console.log("Field map (after API mapping):", fieldMap);

  Object.entries(fieldMap).forEach(([name, value]) => {
    const input = form.querySelector(`[name="${name}"]`);

    console.log(`Field: ${name}, Value: ${value}, Input found: ${!!input}`);

    if (input && value !== undefined && value !== null && value !== "") {
      console.log(`  ✓ Setting ${name} = ${value}`);
      input.value = value;
    } else {
      if (!input) {
        console.warn(`  ✗ Input not found for field: ${name}`);
      } else {
        console.log(`  ○ Skipped ${name} (undefined/null/empty)`);
      }
    }
  });

  console.log("=== POPULATE FORM FIELDS END ===");
}

/**
 * Opens and populates the "Add/Edit Book" modal.
 * If itemToEdit is provided, it populates the form for editing.
 * @param {object | null} [itemToEdit=null] - The book object to edit, or null for adding.
 */
function openAddBookModal(itemToEdit = null) {
  const modal = document.getElementById("add-book-modal");
  const form = document.getElementById("add-book-form");
  const title = document.getElementById("add-book-modal-title");
  if (!modal || !form || !title) return;

  // Set modal state
  const isEditMode = itemToEdit !== null;
  title.textContent = isEditMode ? "Edit Book" : "Add New Book";
  form.dataset.mode = isEditMode ? "edit" : "add";
  form.dataset.book_id = isEditMode ? itemToEdit.book_id : "";

  // Get current values or defaults
  const currentShelf = itemToEdit?.callNumber?.shelf || "CIR";
  const currentClassCode = itemToEdit?.callNumber?.classificationCode || "QA";
  const currentYear = itemToEdit?.year || "";
  const maxYear = new Date().getFullYear() + 1;
  const currentGenre = itemToEdit?.genre || "";

  // Populate form HTML
  form.innerHTML = `
    
        <input type="hidden" name="type" value="book">

        <div class="form-group">
            <label for="book-title">Title*</label>
            <input type="text" id="book-title" name="title" class="form-control" required value="${safeGet(
              itemToEdit?.title,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="book-author">Author(s)*</label>
            <input type="text" id="book-author" name="author" class="form-control" required value="${safeGet(
              itemToEdit?.author,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="book-isbn">ISBN</label>
            <input type="text" id="book-isbn" name="isbn" class="form-control" value="${safeGet(
              itemToEdit?.isbn,
              ""
            )}">
        </div>
         <div class="form-group">
            <label for="book-genre">Genre*</label>
            <select id="book-genre" name="genre" class="form-control" required>
                 ${generateOptions(GENRE_OPTIONS, currentGenre, "Select Genre")}
            </select>
        </div>
        <div class="form-group">
            <label for="book-year">Publication Year*</label>
             <input type="number" id="book-year" name="year" class="form-control"
                    placeholder="YYYY" min="1000" max="${maxYear}" step="1"
                    required value="${safeGet(currentYear, "")}">
        </div>
        <div class="form-group">
            <label for="book-copies">Copies*</label>
            <input type="number" id="book-copies" name="copies" class="form-control" required value="${safeGet(
              itemToEdit?.copies,
              1
            )}" min="0">
        </div>
        <div class="form-group">
            <label for="book-pages">Pages</label>
            <input type="number" id="book-pages" name="pages" class="form-control" value="${safeGet(
              itemToEdit?.pages,
              ""
            )}" min="1">
        </div>
        <div class="form-group">
            <label for="book-edition">Edition/Volume</label>
            <input type="text" id="book-edition" name="edition" class="form-control" value="${safeGet(
              itemToEdit?.edition,
              ""
            )}">
        </div>
         <div class="form-group">
            <label for="book-publisher">Publisher</label>
            <input type="text" id="book-publisher" name="publisher" class="form-control" value="${safeGet(
              itemToEdit?.publisher,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="book-accession">Accession Code</label>
            <input type="text" id="book-accession" name="accessionCode" class="form-control" value="${safeGet(
              itemToEdit?.accessionCode,
              ""
            )}">
        </div>
        <div class="form-group full-width">
            <label for="book-cover">Cover Image URL</label>
            <input type="url" id="book-cover" name="cover" class="form-control" value="${safeGet(
              itemToEdit?.cover,
              ""
            )}">
        </div>
        <div class="form-group full-width">
            <label for="book-desc">Description</label>
            <textarea id="book-desc" name="description" class="form-control" rows="3">${safeGet(
              itemToEdit?.description,
              ""
            )}</textarea>
        </div>

        <h4 class="form-subtitle full-width">Call Number Details</h4>
        <div class="form-group">
            <label for="book-shelf">Shelf Location*</label>
            <select id="book-shelf" name="shelf" class="form-control" required>
                ${generateOptions(SHELF_LOCATIONS, currentShelf)}
            </select>
        </div>
        <div class="form-group">
            <label for="book-class-code">Classification Code*</label>
             <select id="book-class-code" name="classificationCode" class="form-control" required>
                ${generateOptions(CLASSIFICATION_CODES, currentClassCode)}
            </select>
        </div>
        <div class="form-group">
            <label for="book-class-num">Classification Number*</label>
            <input type="text" id="book-class-num" name="classificationNumber" class="form-control" required value="${safeGet(
              itemToEdit?.callNumber?.classificationNumber,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="book-cutter">Cutter*</label>
            <input type="text" id="book-cutter" name="cutter" class="form-control" required value="${safeGet(
              itemToEdit?.callNumber?.cutter,
              ""
            )}">
        </div>

        <div class="call-number-preview">
            <strong>Call Number Preview:</strong> <span id="book-call-number-preview">N/A</span>
        </div>

        <div class="form-group full-width" style="text-align: right;">
            <button type="submit" class="btn btn-primary">${
              isEditMode ? "Save Changes" : "Add Book"
            }</button>
        </div>
    `;

  modal.style.display = "flex"; // Use flex to show and center
  if (itemToEdit) {
    populateFormFields(form, itemToEdit);
  }
  // Add event listeners for call number preview update
  const callNumberFields = [
    "book-shelf",
    "book-class-code",
    "book-class-num",
    "book-cutter",
    "book-year",
  ];
  callNumberFields.forEach((fieldId) => {
    const input = form.querySelector(`#${fieldId}`);
    if (input) {
      input.addEventListener("input", () =>
        updateCallNumberPreview(form, "book")
      );
    }
  });
  // Trigger initial preview
  updateCallNumberPreview(form, "book");
}

/**
 * Opens and populates the "Add/Edit Thesis" modal.
 * @param {object | null} [itemToEdit=null] - The thesis object to edit, or null for adding.
 */
function openAddThesisModal(itemToEdit = null) {
  const modal = document.getElementById("add-thesis-modal");
  const form = document.getElementById("add-thesis-form");
  const title = document.getElementById("add-thesis-modal-title");
  if (!modal || !form || !title) return;

  // Set modal state
  const isEditMode = itemToEdit !== null;
  title.textContent = isEditMode ? "Edit Thesis" : "Add New Thesis";
  form.dataset.mode = isEditMode ? "edit" : "add";
  form.dataset.thesis_id = isEditMode ? itemToEdit.thesis_id : "";

  // Get current values or defaults
  const currentShelf = itemToEdit?.callNumber?.shelf || "THESIS";
  const currentClassCode = itemToEdit?.callNumber?.classificationCode || "TH";
  const currentYear = itemToEdit?.year || "";
  const maxYear = new Date().getFullYear() + 1;

  // Populate form HTML
  form.innerHTML = `
        <input type="hidden" name="type" value="thesis">
        <input type="hidden" name="copies" value="1"> <!-- Theses always have 1 copy -->

        <div class="form-group">
            <label for="thesis-title">Title*</label>
            <input type="text" id="thesis-title" name="title" class="form-control" required value="${safeGet(
              itemToEdit?.title,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="thesis-author">Author(s)*</label>
            <input type="text" id="thesis-author" name="author" class="form-control" required value="${safeGet(
              itemToEdit?.author,
              ""
            )}">
        </div>
         <div class="form-group">
            <label for="thesis-year">Publication Year*</label>
            <input type="number" id="thesis-year" name="year" class="form-control"
                   placeholder="YYYY" min="1900" max="${maxYear}" step="1"
                   required value="${safeGet(currentYear, "")}">
        </div>
        <div class="form-group">
            <label for="thesis-pages">Pages</label>
            <input type="number" id="thesis-pages" name="pages" class="form-control" value="${safeGet(
              itemToEdit?.pages,
              ""
            )}" min="1">
        </div>
        <div class="form-group">
            <label for="thesis-accession">Accession Code</label>
            <input type="text" id="thesis-accession" name="accessionCode" class="form-control" value="${safeGet(
              itemToEdit?.accessionCode,
              ""
            )}">
        </div>
        <div class="form-group full-width">
            <label for="thesis-desc">Description/Abstract</label>
            <textarea id="thesis-desc" name="description" class="form-control" rows="4">${safeGet(
              itemToEdit?.description,
              ""
            )}</textarea>
        </div>

        <h4 class="form-subtitle full-width">Call Number Details</h4>
        <div class="form-group">
            <label for="thesis-shelf">Shelf Location*</label>
             <select id="thesis-shelf" name="shelf" class="form-control" required>
                ${generateOptions(SHELF_LOCATIONS, currentShelf)}
            </select>
        </div>
        <div class="form-group">
            <label for="thesis-class-code">Classification Code*</label>
            <select id="thesis-class-code" name="classificationCode" class="form-control" required>
                ${generateOptions(CLASSIFICATION_CODES, currentClassCode)}
            </select>
        </div>
        <div class="form-group">
            <label for="thesis-class-num">Classification Number*</label>
            <input type="text" id="thesis-class-num" name="classificationNumber" class="form-control" required value="${safeGet(
              itemToEdit?.callNumber?.classificationNumber,
              ""
            )}">
        </div>
        <div class="form-group">
            <label for="thesis-cutter">Cutter*</label>
            <input type="text" id="thesis-cutter" name="cutter" class="form-control" required value="${safeGet(
              itemToEdit?.callNumber?.cutter,
              ""
            )}">
        </div>

        <div class="call-number-preview">
            <strong>Call Number Preview:</strong> <span id="thesis-call-number-preview">N/A</span>
        </div>

        <div class="form-group full-width" style="text-align: right;">
            <button type="submit" class="btn btn-primary">${
              isEditMode ? "Save Changes" : "Add Thesis"
            }</button>
        </div>
    `;

  modal.style.display = "flex"; // Use flex to show and center
  // AUTO-FILL FORM IF EDITING
  if (itemToEdit) {
    populateFormFields(form, itemToEdit);
  }
  // Add event listeners for call number preview update
  const callNumberFields = [
    "thesis-shelf",
    "thesis-class-code",
    "thesis-class-num",
    "thesis-cutter",
    "thesis-year",
  ];
  callNumberFields.forEach((fieldId) => {
    const input = form.querySelector(`#${fieldId}`);
    if (input) {
      input.addEventListener("input", () =>
        updateCallNumberPreview(form, "thesis")
      );
    }
  });
  // Trigger initial preview
  updateCallNumberPreview(form, "thesis");
}

// =============================================================================
// SECTION 7: FORM SUBMISSION HANDLERS
// Summary: Functions that process form data on modal submission.
// =============================================================================

/**
 * Handles submission of the "Add/Edit Book" form.
 * Validates data, then creates or updates the book via API.
 * @param {Event} e - The form submit event.
 */
async function handleBookSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const mode = form.dataset.mode;
  const bookId = form.dataset.book_id
    ? parseInt(form.dataset.book_id, 10)
    : null; // FIXED: book_id not id
  const yearInput = formData.get("year");
  const genreInput = formData.get("genre");

  console.log("=== BOOK SUBMIT ===");
  console.log("Mode:", mode, "Book ID:", bookId);

  // --- Validation ---
  if (
    !formData.get("title") ||
    !formData.get("author") ||
    !yearInput ||
    !formData.get("copies") ||
    !genreInput
  ) {
    showCustomAlert(
      "Error",
      "Please fill in all required fields marked with *.",
      "error"
    );
    return;
  }

  const pubYear = parseInt(yearInput);
  const currentFullYear = new Date().getFullYear();
  if (isNaN(pubYear) || pubYear < 1000 || pubYear > currentFullYear + 1) {
    showCustomAlert(
      "Error",
      `Please enter a valid Publication Year between 1000 and ${
        currentFullYear + 1
      }.`,
      "error"
    );
    return;
  }
  // --- End Validation ---

  // Create the book data object
  const bookData = {
    title: formData.get("title"),
    author: formData.get("author"),
    isbn: formData.get("isbn") || null,
    genre: genreInput,
    year: pubYear,
    copies: parseInt(formData.get("copies")),
    pages: formData.get("pages") ? parseInt(formData.get("pages")) : null,
    edition: formData.get("edition") || null,
    publisher: formData.get("publisher") || null,
    accessionCode: formData.get("accessionCode") || null,
    cover: formData.get("cover") || null,
    description: formData.get("description") || "",
    callNumber: {
      shelf: formData.get("shelf"),
      classificationCode: formData.get("classificationCode"),
      classificationNumber: formData.get("classificationNumber"),
      cutter: formData.get("cutter"),
      year: pubYear,
    },
  };

  // Add or Update using API
  try {
    if (mode === "add") {
      await createBook(bookData);
      showCustomAlert(
        "Success",
        `"${bookData.title}" added successfully.`,
        "success"
      );
    } else if (mode === "edit") {
      if (!bookId) {
        showCustomAlert(
          "Error",
          "Missing book ID for edit operation.",
          "error"
        );
        return;
      }
      await updateBook(bookId, bookData); // FIXED: Pass bookId
      showCustomAlert(
        "Success",
        `"${bookData.title}" updated successfully.`,
        "success"
      );
    }

    await renderCollection();
    document.getElementById("add-book-modal").style.display = "none";
  } catch (error) {
    console.error("Book submit error:", error);
    showCustomAlert("Error", `Failed to save book: ${error.message}`, "error");
  }
}

/**
 * Handles submission of the "Add/Edit Thesis" form.
 * Validates data, then creates or updates the thesis via API.
 * @param {Event} e - The form submit event.
 */
async function handleThesisSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const mode = form.dataset.mode;
  const thesisId = form.dataset.thesis_id
    ? parseInt(form.dataset.thesis_id, 10)
    : null;
  const yearInput = formData.get("year");

  console.log("=== THESIS SUBMIT START ===");
  console.log("Mode:", mode);
  console.log("Thesis ID:", thesisId);
  console.log("Dataset attributes:", form.dataset);

  // --- Validation ---
  if (!formData.get("title") || !formData.get("author") || !yearInput) {
    console.warn("Validation failed: missing required fields");
    showCustomAlert(
      "Error",
      "Please fill in all required fields marked with *.",
      "error"
    );
    return;
  }

  const pubYear = parseInt(yearInput);
  const currentFullYear = new Date().getFullYear();
  if (isNaN(pubYear) || pubYear < 1900 || pubYear > currentFullYear + 1) {
    console.warn("Validation failed: invalid year", pubYear);
    showCustomAlert(
      "Error",
      `Please enter a valid Publication Year between 1900 and ${
        currentFullYear + 1
      }.`,
      "error"
    );
    return;
  }
  // --- End Validation ---

  // Create the thesis data object
  const thesisData = {
    title: formData.get("title"),
    author: formData.get("author"),
    year: pubYear,
    pages: formData.get("pages") ? parseInt(formData.get("pages")) : null,
    accessionCode: formData.get("accessionCode") || null,
    description: formData.get("description") || "",
    callNumber: {
      shelf: formData.get("shelf"),
      classificationCode: formData.get("classificationCode"),
      classificationNumber: formData.get("classificationNumber"),
      cutter: formData.get("cutter"),
      year: pubYear,
    },
  };

  console.log("Thesis Data:", thesisData);

  // Add or Update using API
  try {
    if (mode === "add") {
      console.log("Creating new thesis...");
      const result = await createThesis(thesisData);
      console.log("Create result:", result);
      showCustomAlert(
        "Success",
        `"${thesisData.title}" added successfully.`,
        "success"
      );
    } else if (mode === "edit") {
      if (!thesisId) {
        console.error("Edit mode but no thesis ID found");
        showCustomAlert(
          "Error",
          "Missing thesis ID for edit operation.",
          "error"
        );
        return;
      }
      console.log("Updating thesis ID:", thesisId);
      const result = await updateThesis(thesisId, thesisData);
      console.log("Update result:", result);
      showCustomAlert(
        "Success",
        `"${thesisData.title}" updated successfully.`,
        "success"
      );
    } else {
      console.error("Invalid mode:", mode);
      showCustomAlert("Error", "Invalid form mode.", "error");
      return;
    }

    console.log("Rendering collection...");
    await renderCollection();
    const modal = document.getElementById("add-thesis-modal");
    if (modal) {
      modal.style.display = "none";
      console.log("Modal closed");
    }
    console.log("=== THESIS SUBMIT SUCCESS ===");
  } catch (error) {
    console.error("=== THESIS SUBMIT ERROR ===");
    console.error("Error message:", error.message);
    console.error("Full error:", error);
    showCustomAlert(
      "Error",
      `Failed to save thesis: ${error.message}`,
      "error"
    );
  }
}
