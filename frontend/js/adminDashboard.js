// Session Timeout Logic
import { getConfig } from "./config.js";
let sessionTimer;
function resetSessionTimer() {
  clearTimeout(sessionTimer);
  sessionTimer = setTimeout(() => {
    console.log(
      `[session-timer] Expired at ${new Date().toLocaleTimeString()}`
    );
    sessionStorage.clear();
    window.location.href = "./login.html";
  }, 600000); // 10 minutes
}
["click", "mousemove", "keydown", "scroll"].forEach((evt) =>
  document.addEventListener(evt, resetSessionTimer, { passive: true })
);
console.log("[session-timer] Timer initialized on page load");
resetSessionTimer();
const togglePasswords = document.querySelectorAll(".togglePassword");

togglePasswords.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    const passwordInput = toggle.previousElementSibling;

    const isPassword = passwordInput.type === "password";
    passwordInput.type = isPassword ? "text" : "password";

    toggle.classList.toggle("fa-eye-slash");
    toggle.classList.toggle("fa-eye");
  });
});
// Sidebar Controls
function openProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.add("open");
}
function closeProfileSidebar() {
  const sidebar = document.getElementById("profileSidebar");
  if (sidebar) sidebar.classList.remove("open");
}

// Change Password Modal
function closeChangeModal() {
  document.getElementById("changePasswordModal").style.display = "none";
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

  if (!oldPassword || !newPassword || !confirmPassword) {
    alert("All fields are required.");
    return;
  }
  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  try {
    // Load config asynchronously
    const config = await getConfig();

    const res = await fetch(
      `${config.api.baseUrl}${config.api.endpoints.changePassword}`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, oldPassword, newPassword }),
        credentials: "include",
      }
    );

    const data = await res.json();
    if (!res.ok) {
      alert(data.error || "Password change failed.");
      return;
    }

    alert("Password changed successfully.");
    closeChangeModal();
  } catch (err) {
    alert("Something went wrong. Please try again.");
  }
}

// Logout Modal Controls
function openLogoutModal() {
  document.getElementById("logoutModal").style.display = "flex";
  document.body.style.overflow = "hidden";
}
function closeLogoutModal() {
  document.getElementById("logoutModal").style.display = "none";
  document.body.style.overflow = "";
}

// DOM Initialization
window.addEventListener("DOMContentLoaded", () => {
  const name = sessionStorage.getItem("userName");
  const email = sessionStorage.getItem("email");

  if (name) document.getElementById("adminName").textContent = name;
  if (email) document.getElementById("adminEmail").textContent = email;

  document
    .getElementById("changePasswordLink")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      document.getElementById("changePasswordModal").style.display = "flex";
    });

  document
    .getElementById("cancelLogout")
    ?.addEventListener("click", closeLogoutModal);
  document.getElementById("confirmLogout")?.addEventListener("click", () => {
    sessionStorage.clear();
    window.location.href = "./login.html";
  });
});
