// public/assets/js/catalog/libraryId.js

// Helper function to format YYYY-MM to Month YYYY (e.g., 2023-12 -> Dec 2023)
function formatYearMonth(ymString) {
  if (!ymString) return "-";
  const [year, month] = ymString.split("-");
  const date = new Date(year, month - 1, 1);
  return date.toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
  });
}

// API base
const apiBase = window?.frontendConfig?.api?.baseUrl || "/api";

// Helper: Submit library ID application
export async function applyForLibraryId(userId, remarks = "") {
  const res = await fetch(`${apiBase}/library-ids/request`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ user_id: userId, remarks }),
  });
  return res.json();
}

// Helper: Fetch user's library ID data (returns full library ID object or null)
export async function fetchUserLibraryId(userId) {
  try {
    const res = await fetch(`${apiBase}/library-ids/user/${userId}`);
    const data = await res.json();

    if (!res.ok || !data.success) {
      return null;
    }

    return data.libraryId || null;
  } catch (error) {
    console.error("Error fetching library ID:", error);
    return null;
  }
}

// Helper: Check if user has an active library ID
export async function checkUserLibraryId(userId) {
  try {
    const res = await fetch(`${apiBase}/library-ids/user/${userId}`);
    if (!res.ok) {
      // User doesn't have a library ID
      return false;
    }
    const data = await res.json();
    return data.success && data.libraryId && data.libraryId.status === "active";
  } catch (error) {
    console.error("Error checking library ID:", error);
    return false;
  }
}

// Helper: Get library ID status and determine what action to take
export async function getLibraryIdStatus(userId) {
  try {
    const libraryId = await fetchUserLibraryId(userId);
    if (!libraryId) {
      return { status: "none", libraryId: null };
    }

    switch (libraryId.status) {
      case "active":
        return { status: "active", libraryId: libraryId };
      case "pending":
        return { status: "pending", libraryId: libraryId };
      default:
        return { status: "none", libraryId: null };
    }
  } catch (error) {
    console.error("Error getting library ID status:", error);
    return { status: "error", libraryId: null };
  }
}

// Helper: Initialize library ID button text based on user's status
export async function initLibraryIdButton() {
  const userId = window.userData.userId;
  const btnText = document.getElementById("library-id-btn-text");

  if (!userId) {
    btnText.textContent = "Login Required";
    return;
  }

  // Set loading state
  btnText.textContent = "Loading...";

  try {
    const { status, libraryId } = await getLibraryIdStatus(userId);

    switch (status) {
      case "active":
        btnText.textContent = "View My Library ID";
        break;
      case "pending":
        btnText.textContent = "Application Under Review";
        break;
      case "none":
      default:
        btnText.textContent = "Apply for Digital ID";
        break;
    }
  } catch (error) {
    console.error("Error initializing library ID button:", error);
    btnText.textContent = "Library ID";
  }
}

// Helper: Show the appropriate modal based on user's library ID status
export async function handleLibraryIdButton() {
  const userId = window.userData.userId;

  if (!userId) {
    alert("Please log in to access library ID services.");
    return;
  }

  try {
    const { status, libraryId } = await getLibraryIdStatus(userId);

    switch (status) {
      case "active":
        // User has an active library ID - show the ID card
        await showLibraryIdModal(userId);
        break;

      case "pending":
        // User has a pending application - show review modal
        await showPendingLibraryIdModal(libraryId);
        break;

      case "none":
        // User doesn't have a library ID - show apply modal
        closeModal("apply-digital-id-modal"); // Make sure it's properly reset
        document.getElementById("apply-digital-id-modal").style.display =
          "block";
        document.getElementById("modal-overlay").style.display = "block";
        break;

      case "error":
      default:
        // Error occurred - show apply modal as fallback
        console.error("Error checking library ID status");
        closeModal("apply-digital-id-modal"); // Make sure it's properly reset
        document.getElementById("apply-digital-id-modal").style.display =
          "block";
        document.getElementById("modal-overlay").style.display = "block";
        break;
    }
  } catch (error) {
    console.error("Error handling library ID button:", error);
    alert("An error occurred. Please try again.");
  }
}

// Helper: Close any library ID related modal
export function closeLibraryIdModal() {
  closeModal("library-id-modal");
  closeModal("pending-library-id-modal");
  closeModal("apply-digital-id-modal");
}

// Helper: Show the library ID card modal with fetched data
export async function showLibraryIdModal(userId) {
  const modal = document.getElementById("library-id-modal");
  const cardContainer = document.getElementById("id-card-content");

  if (!modal || !cardContainer) {
    console.error("Library ID modal elements not found in HTML");
    return;
  }

  try {
    const libraryId = await fetchUserLibraryId(userId);

    if (!libraryId) {
      cardContainer.innerHTML = "<p>No active Library ID found.</p>";
      modal.style.display = "block";
      document.getElementById("modal-overlay").style.display = "block";
      return;
    }

    // Render card as per your UI spec
    cardContainer.innerHTML = `
  <div class="library-id-card"
    style="background: #14395b; color: #fff; border-radius: 18px; width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); margin: 0 auto; padding: 22px 36px 12px 36px;">
    <div style="display: flex; align-items:center; justify-content: flex-start; margin-bottom: 12px;">
      <img src="/assets/images/logo.png" alt="STI DigiLibrary"
        style="width:48px; height:48px; border-radius:12px; margin-right:14px; background: #ffd700;">
      <span style="font-size:1.22em; font-weight:600;">STI DigiLibrary ID</span>
    </div>
    <hr style="border:none; border-top:1px solid #22466e; margin:16px 0;">
    <div style="display:flex; gap:32px; align-items:center;">
      <img src="${
        window.userData.profilePic || "/assets/images/owlie_icn_transparent.png"
      }"
        alt="Profile"
        style="width:88px; height:88px; border-radius:50%; border:3px solid #ffd700;">
      <div>
        <span style="font-weight:700; font-size:1.5em; color:#ffd700;">
          ${window.userData.userName}
        </span>
        <div style="color:#d4d4d4; font-size:1.12em; margin-top:10px;">
          <span style="font-weight:500;">Student ID:</span> <span style="font-weight:400;">${
            window.userData.studentId || "-"
          }</span><br>
          <span style="font-weight:500;">Library ID:</span> <span style="font-weight:400;">${
            libraryId.library_id_number || "-"
          }</span>
        </div>
      </div>
    </div>
    <hr style="border:none; border-top:1px solid #22466e; margin:20px 0 12px 0;">
    <div style="display:flex; justify-content:space-between; color:#bbdefb; font-size:1.07em; margin-bottom:8px;">
      <span><strong>Issued:</strong> ${
        libraryId.issue_year_month
          ? formatYearMonth(libraryId.issue_year_month)
          : "-"
      }</span>
      <span><strong>Expires:</strong> ${
        libraryId.expiry_year_month
          ? formatYearMonth(libraryId.expiry_year_month)
          : "-"
      }</span>
    </div>
  </div>
  <button id="download-id-btn" class="download-id-btn" style="margin-top: 20px; padding: 10px 20px; background: #ffd700; color: #14395b; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: block; margin-left: auto; margin-right: auto; font-size: 1em;">Download Card</button>
`;

    modal.style.display = "block";
    document.getElementById("modal-overlay").style.display = "block";

    const downloadBtn = document.getElementById("download-id-btn");
    if (downloadBtn) {
      downloadBtn.addEventListener("click", function () {
        const card = document.querySelector(".library-id-card");
        html2canvas(card, { backgroundColor: null }).then((canvas) => {
          const link = document.createElement("a");
          link.download = "library-id-card.png";
          link.href = canvas.toDataURL();
          link.click();
        });
      });
    }

    // Add close button event listener
    const closeBtn = modal.querySelector(".modal-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => closeModal("library-id-modal"));
    }
  } catch (error) {
    console.error("Error loading library ID:", error);
    cardContainer.innerHTML =
      "<p>Error loading library ID. Please try again later.</p>";
    modal.style.display = "block";
    document.getElementById("modal-overlay").style.display = "block";

    // Add close button event listener
    const closeBtn = modal.querySelector(".modal-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => closeModal("library-id-modal"));
    }
  }
}

// Helper: Show the pending/review modal for library ID applications
export async function showPendingLibraryIdModal(libraryId) {
  const modal = document.getElementById("pending-library-id-modal");
  const contentContainer = document.getElementById("pending-id-content");

  if (!modal || !contentContainer) {
    console.error("Pending library ID modal not found in HTML");
    return;
  }

  contentContainer.innerHTML = `
    <div class="pending-library-id-card">
      <div style="text-align: center; margin-bottom: 20px;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: #ffd700; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
          <span style="font-size: 2em; color: #333;">⏳</span>
        </div>
        <h3 style="color: #ffd700; margin: 0;">Application Under Review</h3>
        <p style="color: #666; margin: 10px 0;">Your library ID application is being processed</p>
      </div>

      <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span><strong>Application Date:</strong></span>
          <span>${new Date().toLocaleDateString()}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span><strong>Status:</strong></span>
          <span style="color: #ff6b35; font-weight: bold;">Pending</span>
        </div>
      </div>

      <div style="text-align: center; color: #666; font-size: 0.9em; margin-bottom: 20px;">
        <p>You will receive a notification once your application is approved.</p>
        <p>Please check back later or contact the library administration if you have questions.</p>
      </div>

    </div>
  `;

  modal.style.display = "block";
  document.getElementById("modal-overlay").style.display = "block";
}

// Helper: Close modal function
export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
  }
  const overlay = document.getElementById("modal-overlay");
  if (overlay) {
    overlay.style.display = "none";
  }
}

// Helper: Apply modal event setup
export function setupApplyLibraryIdModal() {
  const form = document.getElementById("apply-digital-id-form");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const userId = window.userData.userId;
    const statusDiv = document.getElementById("application-status");

    if (!userId) {
      statusDiv.innerHTML =
        '<span style="color:red;">User not logged in.</span>';
      return;
    }

    // Clear previous status
    statusDiv.innerHTML = '<span style="color:blue;">Submitting...</span>';

    try {
      const { success, error } = await applyForLibraryId(userId);

      if (success) {
        statusDiv.innerHTML =
          '<span style="color:green;">Application submitted successfully!</span>';
        // Close modal after a short delay
        setTimeout(() => {
          closeModal("apply-digital-id-modal");
          // Refresh button text to show new status
          initLibraryIdButton();
        }, 1500);
      } else {
        statusDiv.innerHTML = `<span style="color:red;">${
          error || "Application failed."
        }</span>`;
      }
    } catch (error) {
      console.error("Error submitting application:", error);
      statusDiv.innerHTML =
        '<span style="color:red;">An error occurred. Please try again.</span>';
    }
  });
}

// Entry: Attach modal events (call once per load)
export function initLibraryIdUi() {
  setupApplyLibraryIdModal();
  // Initialize button text based on user's library ID status
  initLibraryIdButton();
  // Add event listener for the library ID button
  const libraryIdBtn = document.getElementById("library-id-btn");
  if (libraryIdBtn) {
    libraryIdBtn.addEventListener("click", handleLibraryIdButton);
  }

  // Add close button event listeners for all modals
  const modals = [
    "library-id-modal",
    "pending-library-id-modal",
    "apply-digital-id-modal",
  ];
  modals.forEach((modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
      // Handle modal-close button (X button)
      const closeBtn = modal.querySelector(".modal-close");
      if (closeBtn) {
        closeBtn.addEventListener("click", () => closeModal(modalId));
      }

      // Handle modal-action-close buttons (Cancel/Close buttons)
      const cancelBtns = modal.querySelectorAll(".modal-action-close");
      cancelBtns.forEach((btn) => {
        btn.addEventListener("click", () => closeModal(modalId));
      });
    }
  });

  // Add overlay click to close functionality
  const overlay = document.getElementById("modal-overlay");
  if (overlay) {
    overlay.addEventListener("click", closeLibraryIdModal);
  }

  // Add escape key to close functionality
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeLibraryIdModal();
    }
  });
}
