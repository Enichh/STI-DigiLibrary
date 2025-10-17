/**
 * Opens the user details modal.
 * @param {object} user - The user object containing details to display.
 */
function openModal(user) {
  document.getElementById("modal-name").textContent = user.userName;
  document.getElementById(
    "modal-status"
  ).innerHTML = `<strong>Status:</strong> ${user.status || "Unknown"}`;
  document.getElementById(
    "modal-login"
  ).innerHTML = `<strong>Last Login:</strong> ${user.last_login || "N/A"}`;
  document.getElementById(
    "modal-role"
  ).innerHTML = `<strong>Role:</strong> ${user.role}`;
  document.getElementById("user-modal").style.display = "flex";
}

/**
 * Closes the user details modal.
 */
function closeModal() {
  document.getElementById("user-modal").style.display = "none";
}

let currentPage = 1;
const limit = 10;

/**
 * Loads a paginated list of users from the API and renders them.
 * @param {number} [page=1] - The page number to load.
 */
async function loadUsers(page = 1) {
  try {
    currentPage = page;

    // Get config for API endpoints
    const config = await import("./config.js").then((module) =>
      module.getConfig()
    );

    const response = await fetch(
      `${config.api.baseUrl}${config.api.users.getUsers}?page=${page}&limit=${limit}`
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const { users, total } = await response.json();
    const list = document.getElementById("user-list");

    if (!list) return;

    // Clear existing users
    list.innerHTML = "";

    if (!Array.isArray(users) || users.length === 0) {
      list.innerHTML = '<li class="no-users">No users found</li>';
      return;
    }

    // Create user list items
    users.forEach((user) => {
      const li = document.createElement("li");
      li.className = "user-entry";
      li.innerHTML = `
        <span class="user-name">${user.userName}</span>
        <span class="user-email">${user.email || "No email"}</span>
        <span class="user-role">${user.role || "No role"}</span>
      `;
      li.addEventListener("click", () => openModal(user));
      list.appendChild(li);
    });

    // Update pagination controls
    const totalPages = Math.ceil(total / limit);
    const pageInfo = document.getElementById("page-info");
    const prevBtn = document.getElementById("prev-page");
    const nextBtn = document.getElementById("next-page");

    if (pageInfo) {
      pageInfo.textContent = `Page ${page} of ${totalPages} (${total} total users)`;
    }

    if (prevBtn) {
      prevBtn.disabled = page <= 1;
      prevBtn.classList.toggle("disabled", page <= 1);
    }

    if (nextBtn) {
      nextBtn.disabled = page >= totalPages;
      nextBtn.classList.toggle("disabled", page >= totalPages);
    }

    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set("page", page);
    window.history.pushState({}, "", url);
  } catch (error) {
    console.error(
      `[${new Date().toISOString()}] ❌ Failed to load users:`,
      error.message
    );
    const list = document.getElementById("user-list");
    if (list) {
      list.innerHTML =
        '<li class="error">Error loading users. Please try again.</li>';
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // Pagination event listeners
  const prevBtn = document.getElementById("prev-page");
  const nextBtn = document.getElementById("next-page");
  const generateBtn = document.querySelector(".generate-button");

  if (prevBtn) {
    prevBtn.addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage--;
        loadUsers(currentPage);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", () => {
      currentPage++;
      loadUsers(currentPage);
    });
  }

  generateBtn.addEventListener("click", async () => {
    const code = Math.floor(100000 + Math.random() * 900000).toString();
    const codeDisplay = document.getElementById("verification-code");
    if (codeDisplay) codeDisplay.textContent = code;

    try {
      // Get config for API endpoints
      const config = await import("./config.js").then((module) =>
        module.getConfig()
      );

      const res = await fetch(
        `${config.api.baseUrl}${config.api.endpoints.issueAdminCode}`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code }),
        }
      );

      const data = await res.json();
      if (!res.ok) {
        console.error(`[superadmin] Failed to issue code: ${data.error}`);
        alert(data.error || "Failed to issue code.");
        return;
      }

      console.log(`[superadmin] Code "${code}" issued successfully`);
      alert(`Verification code "${code}" has been stored.`);
    } catch (err) {
      console.error(`[superadmin] Network error: ${err.message}`);
      alert("Network error while issuing code.");
    }
  });

  const searchInput = document.getElementById("user-search");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const query = this.value.toLowerCase();
      document.querySelectorAll(".user-entry").forEach((entry) => {
        entry.style.display = entry.textContent.toLowerCase().includes(query)
          ? "block"
          : "none";
      });
    });
  }

  loadUsers();
});
