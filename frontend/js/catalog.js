// Toggle password visibility
import { initPasswordToggles, disablePasswordClipboardActions } from "./ui.js";
import { changePassword } from "./api.js";
import { isValidPassword } from "./ui.js";
import { openModal, closeModal } from "./modal.js";
import { initSessionTimer } from "./session.js";

function openProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.add("open");
}

function closeProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.remove("open");
}

window.addEventListener("DOMContentLoaded", () => {
  const name = sessionStorage.getItem("userName");
  const email = sessionStorage.getItem("email");

  if (name && email) {
    document.querySelector("#profileSidebar p:nth-of-type(1)").textContent =
      name;
    document.querySelector("#profileSidebar p:nth-of-type(2)").textContent =
      email;
  }

  const openSidebarBtn = document.getElementById("openSidebarBtn");
  if (openSidebarBtn) {
    openSidebarBtn.addEventListener("click", openProfileSidebar);
  }

  const closeSidebarBtn = document.getElementById("closeSidebarBtn");
  if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener("click", closeProfileSidebar);
  }

  const closeChangeModalBtn = document.getElementById("closeChangeModalBtn");
  if (closeChangeModalBtn) {
    closeChangeModalBtn.addEventListener("click", closeChangeModal);
  }

  const submitPasswordChangeBtn = document.getElementById(
    "submitPasswordChangeBtn"
  );
  if (submitPasswordChangeBtn) {
    submitPasswordChangeBtn.addEventListener("click", submitPasswordChange);
  }

  const changePasswordLink = document.getElementById("changePasswordLink");
  if (changePasswordLink) {
    changePasswordLink.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(
        document.getElementById("changePasswordModal"),
        document.getElementById("oldPasswordInput")
      );
    });
  }

  const logoutLink = document.getElementById("logoutLink");
  if (logoutLink) {
    logoutLink.addEventListener("click", (e) => {
      e.preventDefault();
      openLogoutModal();
    });
  }

  const cancelLogout = document.getElementById("cancelLogout");
  const confirmLogout = document.getElementById("confirmLogout");

  if (cancelLogout) {
    cancelLogout.addEventListener("click", closeLogoutModal);
  }

  if (confirmLogout) {
    confirmLogout.addEventListener("click", () => {
      sessionStorage.clear();
      window.location.href = "./login.html";
    });
  }

  initPasswordToggles();
  disablePasswordClipboardActions();
  initSessionTimer();
});

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

  // Check if user is logged in (has email in session)
  if (!email) {
    return alert("Please log in to change your password.");
  }

  // Client-side validation
  if (!oldPassword || !newPassword || !confirmPassword) {
    return alert("All fields are required.");
  }
  if (oldPassword === newPassword) {
    return alert("New password must be different from the old password.");
  }
  if (newPassword !== confirmPassword) {
    return alert("Passwords do not match.");
  }
  if (!isValidPassword(newPassword)) {
    return alert(
      "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character."
    );
  }

  try {
    // Call changePassword with email, oldPassword, and newPassword
    const data = await changePassword(email, oldPassword, newPassword);

    // FIX: Handle backend errors
    if (data.error) {
      alert(data.error); // Show the error from backend
      return; // Do NOT proceed or close modal
    }

    alert(data.message || "Password changed successfully.");
    closeChangeModal();
  } catch (err) {
    alert(err.message || "Password change failed. Please try again.");
  }
}

//  Logout Modal Controls
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
