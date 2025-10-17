// Toggle password visibility and other UI hooks
import { initPasswordToggles, disablePasswordClipboardActions } from "./ui.js";
import { changePassword } from "./api.js";
import { isValidPassword } from "./ui.js";
import { openModal, closeModal } from "./modal.js";
import { initSessionTimer } from "./session.js";

const tabListeners = { books: [], theses: [] };
export function onTabActivated(tab, fn) {
  if (!tabListeners[tab]) tabListeners[tab] = [];
  tabListeners[tab].push(fn);
}
function notifyTab(tab) {
  (tabListeners[tab] || []).forEach((fn) => {
    try {
      fn();
    } catch (e) {
      /* no-op */
    }
  });
}

// Sidebar open/close
function openProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.add("open");
}
function closeProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.remove("open");
}

// Tabs behavior: Books | Theses
function initCatalogTabs() {
  const tabsBar = document.querySelector(".catalog-tabs");
  const btnBooks = document.querySelector(
    '.catalog-tabs .tab[data-tab="booksTab"]'
  );
  const btnTheses = document.querySelector(
    '.catalog-tabs .tab[data-tab="thesesTab"]'
  );
  const booksPanel = document.getElementById("book-catalog");
  const thesesPanel = document.getElementById("thesesTab");
  const booksFilters = document.getElementById("booksFilters"); // optional, hide on theses
  const searchInput = document.getElementById("search-input");

  const setActive = (tabId) => {
    const isBooks = tabId === "booksTab";

    // button active states
    if (btnBooks) btnBooks.classList.toggle("active", isBooks);
    if (btnTheses) btnTheses.classList.toggle("active", !isBooks);

    // panel visibility
    if (booksPanel) {
      booksPanel.classList.toggle("active", isBooks);
      booksPanel.style.display = isBooks ? "" : "none";
    }
    if (thesesPanel) {
      thesesPanel.classList.toggle("active", !isBooks);
      thesesPanel.style.display = !isBooks ? "" : "none";
    }

    // show/hide books-only filters
    if (booksFilters) booksFilters.style.display = isBooks ? "" : "none";

    // search placeholder
    if (searchInput) {
      searchInput.placeholder = isBooks
        ? "Search by title, author, or ISBN"
        : "Search thesis title...";
      searchInput.value = ""; // clear cross-context query
    }

    // notify listeners
    notifyTab(isBooks ? "books" : "theses");
  };

  // Attach events
  btnBooks?.addEventListener("click", () => setActive("booksTab"));
  btnTheses?.addEventListener("click", () => setActive("thesesTab"));

  // Default active tab
  setActive("booksTab");
}

window.addEventListener("DOMContentLoaded", () => {
  // Profile details
  const name = sessionStorage.getItem("userName");
  const email = sessionStorage.getItem("email");
  if (name && email) {
    const p1 = document.querySelector("#profileSidebar p:nth-of-type(1)");
    const p2 = document.querySelector("#profileSidebar p:nth-of-type(2)");
    if (p1) p1.textContent = name;
    if (p2) p2.textContent = email;
  }

  // Sidebar buttons
  document
    .getElementById("openSidebarBtn")
    ?.addEventListener("click", openProfileSidebar);
  document
    .getElementById("closeSidebarBtn")
    ?.addEventListener("click", closeProfileSidebar);

  // Change password modal
  document
    .getElementById("closeChangeModalBtn")
    ?.addEventListener("click", closeChangeModal);
  document
    .getElementById("submitPasswordChangeBtn")
    ?.addEventListener("click", submitPasswordChange);
  document
    .getElementById("changePasswordLink")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(
        document.getElementById("changePasswordModal"),
        document.getElementById("oldPasswordInput")
      );
    });

  // Logout modal
  document.getElementById("logoutLink")?.addEventListener("click", (e) => {
    e.preventDefault();
    openLogoutModal();
  });
  document
    .getElementById("cancelLogout")
    ?.addEventListener("click", closeLogoutModal);
  document.getElementById("confirmLogout")?.addEventListener("click", () => {
    sessionStorage.clear();
    window.location.href = "./login.html";
  });

  // Init UI helpers and session
  initPasswordToggles();
  disablePasswordClipboardActions();
  initSessionTimer();

  // Init Books | Theses tabs
  initCatalogTabs();
});

// Change Password Handlers
function closeChangeModal() {
  closeModal(document.getElementById("changePasswordModal"));
  document.getElementById("oldPasswordInput").value = "";
  document.getElementById("newPasswordInputChange").value = "";
  document.getElementById("confirmNewPasswordInputChange").value = "";
}

async function submitPasswordChange() {
  const email = sessionStorage.getItem("email");
  const oldPassword = document.getElementById("oldPasswordInput").value.trim();
  const newPassword = document
    .getElementById("newPasswordInputChange")
    .value.trim();
  const confirmPassword = document
    .getElementById("confirmNewPasswordInputChange")
    .value.trim();

  if (!email) return alert("Please log in to change your password.");
  if (!oldPassword || !newPassword || !confirmPassword)
    return alert("All fields are required.");
  if (oldPassword === newPassword)
    return alert("New password must be different from the old password.");
  if (newPassword !== confirmPassword) return alert("Passwords do not match.");
  if (!isValidPassword(newPassword)) {
    return alert(
      "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character."
    );
  }

  try {
    const data = await changePassword(email, oldPassword, newPassword);
    if (data.error) {
      alert(data.error);
      return;
    }
    alert(data.message || "Password changed successfully.");
    closeChangeModal();
  } catch (err) {
    alert(err.message || "Password change failed. Please try again.");
  }
}

// Logout Modal Controls
function openLogoutModal() {
  openModal(
    document.getElementById("logoutModal"),
    document.getElementById("cancelLogout")
  );
  document.body.style.overflow = "hidden";
}
function closeLogoutModal() {
  closeModal(document.getElementById("logoutModal"));
  document.body.style.overflow = "";
}
