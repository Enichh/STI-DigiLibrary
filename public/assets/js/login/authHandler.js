import {
  isValidPassword,
  saveUsername,
  clearHints,
  validateSignupFields,
} from "./ui.js";
import {
  openForgotModal,
  closeForgotModal,
  closeLockedModal,
  closeConfirmResetModal,
  openConfirmResetModal,
  verifyModal,
  bindVerifyModalEvents,
  openModal,
} from "./modal.js";
import {
  handleAdminVerification,
  handleStudentVerification,
  requestUnlock,
  requestVerificationEmail,
  requestPasswordReset,
  confirmPasswordReset,
  changePassword,
  loginUser,
  handleSignupFlow,
  handleLoginFlow,
} from "./api.js";
import { getConfig } from "../config.js";

/**
 * Creates a single instance of the verification handler.
 * @type {function}
 */
export const handleVerification = (() => {
  console.log("[Auth] Initializing verification handler");
  const pendingSignupRef = { current: null };
  return createVerificationHandler(pendingSignupRef);
})();

/**
 * Attaches handler for switching between student and admin login modes via toggle button.
 */
function setupAdminToggle() {
  const loginForm = document.getElementById("login");
  const adminToggleBtn = document.getElementById("adminToggle");
  const formTitle = loginForm ? loginForm.querySelector(".form-title") : null;
  const emailInput = document.getElementById("loginEmail");
  const passwordInput = document.getElementById("loginPassword");
  let isAdmin = false;

  if (!adminToggleBtn || !formTitle || !emailInput || !passwordInput) return;

  adminToggleBtn.addEventListener("click", function () {
    isAdmin = !isAdmin;
    if (isAdmin) {
      formTitle.textContent = "Admin Login";
      emailInput.placeholder = "Admin Email Address";
      passwordInput.placeholder = "Admin Password";
      adminToggleBtn.textContent = "Log in as User";
    } else {
      formTitle.textContent = "Login";
      emailInput.placeholder = "Email Address";
      passwordInput.placeholder = "Password";
      adminToggleBtn.textContent = "Log in as Admin";
    }
    emailInput.value = "";
    passwordInput.value = "";
    const errorMsg = loginForm.querySelector(".form-message--error");
    if (errorMsg) errorMsg.textContent = "";
    window.currentMode = isAdmin ? "admin-login" : "student-login";
  });

  // Initialize in student mode by default
  formTitle.textContent = "Login";
  emailInput.placeholder = "Email Address";
  passwordInput.placeholder = "Password";
  adminToggleBtn.textContent = "Log in as Admin";
  window.currentMode = "student-login";
}

/**
 * Attaches all authentication-related event handlers for login, signup,
 * unlock, forgot/reset, change password, and admin toggle.
 */
export function attachAuthHandlers() {
  console.log("[Auth] Attaching authentication handlers");
  const startTime = performance.now();

  try {
    setupLoginAndSignup();
    setupUnlock();
    setupForgotAndReset();
    setupChangePassword();
    setupAdminToggle();
    console.log(
      `[Auth] Handlers attached in ${performance.now() - startTime}ms`
    );
  } catch (error) {
    console.error("[Auth] Failed to attach handlers:", error);
    throw error;
  }
}

/**
 * Executes Google reCAPTCHA and passes the token to a callback function.
 * @param {string} action - The action to perform.
 * @param {function} callback - The callback function to execute with the token.
 */
async function executeRecaptcha(action, callback) {
  const startTime = performance.now();
  console.log(`[reCAPTCHA] Executing for action: ${action}`);

  try {
    const configStart = performance.now();
    const config = await getConfig();
    console.log(
      `[reCAPTCHA] Config loaded in ${performance.now() - configStart}ms`
    );

    grecaptcha.ready(() => {
      const executeStart = performance.now();
      grecaptcha.execute(config.recaptcha.siteKey, { action }).then((token) => {
        console.log(
          `[reCAPTCHA] Token generated in ${performance.now() - executeStart}ms`
        );
        callback(token);
      });
    });
  } catch (error) {
    console.error(
      `[reCAPTCHA] Failed after ${performance.now() - startTime}ms:`,
      error
    );
    throw error;
  }
}

/**
 * Creates a handler for signup verification (student or admin registration).
 * @param {object} pendingSignupRef - A reference to the pending signup data.
 * @returns {function} The verification handler.
 */
function createVerificationHandler(pendingSignupRef) {
  return async (code, role, op) => {
    const startTime = performance.now();
    console.log(`[${op}] Starting verification for ${role}`, {
      code: code ? "***" : "none",
      hasPendingData: !!pendingSignupRef.current,
    });

    try {
      let success = false;
      let verificationStart, verificationEnd;

      verificationStart = performance.now();
      if (role === "student") {
        console.log(`[${op}] Starting student verification`);
        success = await handleStudentVerification(
          code,
          true,
          pendingSignupRef.current
        );
      } else if (role === "admin") {
        console.log(`[${op}] Starting admin verification`);
        success = await handleAdminVerification(
          code,
          true,
          pendingSignupRef.current
        );
      }
      verificationEnd = performance.now();
      console.log(
        `[${op}] ${role} verification ${success ? "succeeded" : "failed"} in ${
          verificationEnd - verificationStart
        }ms`
      );

      if (success) {
        try {
          const signupStart = performance.now();
          console.log(`[${op}] Starting signup flow for ${role}`);

          await handleSignupFlow(role, pendingSignupRef.current);

          const signupEnd = performance.now();
          console.log(
            `[${op}] Signup flow completed in ${signupEnd - signupStart}ms`
          );
          console.log(
            `[${op}] Total verification time: ${signupEnd - startTime}ms`
          );

          alert("Signup successful! Please log in with your new credentials.");
          pendingSignupRef.current = null;

          window.location.href = "/login";
          return true;
        } catch (err) {
          console.error("Signup failed:", err);
          alert("Signup failed. Please try again.");
          return false;
        }
      }

      const totalTime = performance.now() - startTime;
      console.warn(
        `[${op}] Verification failed after ${totalTime}ms: Invalid or expired code`
      );
      alert("Invalid or expired verification code.");
      return false;
    } catch (err) {
      const totalTime = performance.now() - startTime;
      console.error(`[${op}] Verification failed after ${totalTime}ms:`, err);
      alert(err.message || "Verification failed. Please try again.");
      return false;
    }
  };
}

/**
 * Sets up the login form handler to handle Enter key submission.
 */
function setupLoginHandler() {
  const loginForm = document.getElementById("login");
  if (!loginForm) return;

  loginForm.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      const activeElement = document.activeElement;
      if (
        activeElement &&
        (activeElement.tagName === "INPUT" ||
          activeElement.tagName === "SELECT")
      ) {
        e.preventDefault();
        loginForm.requestSubmit();
      }
    }
  });
}

/**
 * Sets up the signup form handler (show/hide toggle).
 */
function setupSignupHandler() {
  console.log("[Auth] Setting up signup handler");

  const loginForm = document.getElementById("login");
  const signupForm = document.getElementById("signup");
  const linkCreateAccount = document.getElementById("linkCreateAccount");
  const linkLogin = document.getElementById("linkLogin");

  // Show signup, hide login
  if (linkCreateAccount && signupForm && loginForm) {
    linkCreateAccount.addEventListener("click", (e) => {
      e.preventDefault();
      signupForm.classList.remove("form--hidden");
      loginForm.classList.add("form--hidden");
    });
  }

  // Show login, hide signup
  if (linkLogin && signupForm && loginForm) {
    linkLogin.addEventListener("click", (e) => {
      e.preventDefault();
      signupForm.classList.add("form--hidden");
      loginForm.classList.remove("form--hidden");
    });
  }
}

/**
 * Sets up the login and signup form handlers for the current UI.
 */
function setupLoginAndSignup() {
  console.log("[Auth] Setting up login/signup handler");
  const startTime = performance.now();

  // Use the forms directly; button logic is handled by browser on submit
  const loginForm = document.getElementById("login");
  const signupForm = document.getElementById("signup");

  // Handler for login form submit
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        const isAdminMode = window.currentMode === "admin-login";
        await handleLogin(isAdminMode);
      } catch (error) {
        console.error("[Auth] Error in login handler:", error);
      }
    });
  }

  // Handler for signup form submit
  if (signupForm) {
    signupForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      try {
        await handleSignup();
      } catch (error) {
        console.error("[Auth] Error in signup handler:", error);
      }
    });
  }

  // Setup show/hide toggling as an extra, to be robust if skipped in other setup
  setupSignupHandler();

  console.log(
    `[Auth] Login/signup handler setup completed in ${
      performance.now() - startTime
    }ms`
  );
}

/**
 * Creates a handler for user verification during login (student/admin).
 * @returns {function} The login verification handler.
 */
export function createLoginVerificationHandler() {
  console.log("[Auth] Creating login verification handler");

  return async (code, role, op) => {
    const startTime = performance.now();
    console.log(`[${op}] Starting login verification for ${role}`, {
      code: code ? "***" : "none",
      timestamp: new Date().toISOString(),
    });

    try {
      let success = false;
      let verificationStart, verificationEnd;

      verificationStart = performance.now();
      if (role === "student") {
        console.log(`[${op}] Starting student login verification`);
        success = await handleStudentVerification(code, false);
      } else if (role === "admin") {
        console.log(`[${op}] Starting admin login verification`);
        success = await handleAdminVerification(code, false);
      }
      verificationEnd = performance.now();
      console.log(
        `[${op}] ${role} login verification ${
          success ? "succeeded" : "failed"
        } in ${verificationEnd - verificationStart}ms`
      );

      if (success) {
        console.log(
          `[${op}] Login verification successful, starting login flow`
        );
        const loginFlowStart = performance.now();
        await handleLoginFlow(role);
        console.log(
          `[${op}] Login flow completed in ${
            performance.now() - loginFlowStart
          }ms`
        );

        const totalTime = performance.now() - startTime;
        console.log(`[${op}] Total login verification time: ${totalTime}ms`);
        alert("Login verification successful!");
        return true;
      }

      const totalTime = performance.now() - startTime;
      console.warn(
        `[${op}] Invalid or expired verification code after ${totalTime}ms`
      );
      alert("Invalid or expired verification code.");
      return false;
    } catch (err) {
      const totalTime = performance.now() - startTime;
      console.error(
        `[${op}] Login verification failed after ${totalTime}ms:`,
        err
      );
      alert(err.message || "Verification failed. Please try again.");
      return false;
    }
  };
}

/**
 * Handles the login form logic, including reCAPTCHA and form validation.
 * Uses 'admin mode' variable and reads from the single login form.
 */
function handleLogin(isAdminMode = false) {
  const op = `login-${Date.now()}`;
  const startTime = performance.now();
  const role = isAdminMode ? "admin" : "student";

  console.log(`[Auth] Starting ${role} login`, {
    timestamp: new Date().toISOString(),
    operation: op,
  });

  const loginForm = document.getElementById("login");
  if (!loginForm) {
    alert("Login form not found.");
    return;
  }
  const emailInput = document.getElementById("loginEmail");
  const passwordInput = document.getElementById("loginPassword");
  const identifier = emailInput?.value.trim();

  const password = passwordInput?.value;
  const expectedRole = isAdminMode ? "admin" : "student";

  // Log email and password values while in scope
  console.log("Email:", emailInput?.value, "Password:", passwordInput?.value);

  const errorDiv = loginForm.querySelector(".form-message--error");
  if (errorDiv) errorDiv.innerText = "";

  if (!identifier || !password) {
    const errorMsg = "Please enter email and password.";
    console.warn(`[Auth] ${op} - ${errorMsg}`);
    if (errorDiv) errorDiv.innerText = errorMsg;
    return;
  }

  console.log(`[Auth] ${op} - Validating credentials for ${identifier}`);
  saveUsername(identifier);

  executeRecaptcha("login", (captchaToken) => {
    const recaptchaTime = performance.now();
    console.log(
      `[Auth] ${op} - reCAPTCHA completed in ${recaptchaTime - startTime}ms`
    );

    console.log(`[Auth] ${op} - Initiating login for ${identifier}`);
    loginUser(identifier, password, captchaToken, expectedRole)
      .then((result) => {
        if (result.success) {
          console.log(
            `[Auth] ${op} - Login flow completed successfully in ${
              performance.now() - startTime
            }ms`
          );
          // Handle successful login (redirect, etc)
          return;
        }
        if (result.requiresVerification) {
          // Modal is already shown by loginUser.
          return;
        }
        // Handle unexpected non-success, non-verification cases
        if (errorDiv) errorDiv.innerText = "Unexpected state. Try again.";
      })
      .catch((error) => {
        // Show error message in the login form
        if (errorDiv) {
          errorDiv.innerText =
            error.message || "Login failed. Please try again.";
          errorDiv.style.display = "block";
        }
        console.error(
          `[Auth] ${op} - Login failed after ${
            performance.now() - startTime
          }ms:`,
          error
        );
      });
  });
}

/**
 * Handles the signup form logic, validation, and verification email step.
 * Collects all form fields, validates, and sets up verification.
 * @param {boolean} isAdminMode - If true, signup is for admin.
 */
async function handleSignup(isAdminMode = false) {
  const op = `signup-${Date.now()}`;
  const role = isAdminMode ? "admin" : "student";

  const signupForm = document.getElementById("signup");
  if (!signupForm) {
    alert("Signup form not found.");
    return;
  }

  // Collect all identity-related fields for student/admin
  const firstName = document.getElementById("firstName")?.value?.trim();
  const lastName = document.getElementById("lastName")?.value?.trim();
  const middleInitial = document.getElementById("middleInitial")?.value?.trim();
  const suffix = document.getElementById("suffix")?.value?.trim();
  const studentNumber = document.getElementById("studentNumber")?.value?.trim();
  const email = document.getElementById("signupEmail")?.value?.trim();
  const password = document.getElementById("signupPassword")?.value;
  const confirmPassword = document.getElementById(
    "signupConfirmPassword"
  )?.value;
  const userName = [firstName, lastName].filter(Boolean).join(" ").trim();

  // Hint fields for validation error messaging
  const hints = {
    firstName: signupForm.querySelector(
      "#firstName + .form-input-error-message"
    ),
    lastName: signupForm.querySelector("#lastName + .form-input-error-message"),
    studentNumber: signupForm.querySelector(
      "#studentNumber + .form-input-error-message"
    ),
    email: signupForm.querySelector("#user_email_hint"),
    password: signupForm.querySelector("#user_password_hint"),
    confirm: signupForm.querySelector("#user_confirm_hint"),
    userName: signupForm.querySelector("#user_username_hint"),
  };

  clearHints(hints);

  // Validate all required signup fields
  const valid = validateSignupFields({
    firstName,
    lastName,
    userName,
    email,
    password,
    confirmPassword,
    studentNumber,
    hints,
  });
  if (!valid) return;

  // Store all user info for verification and backend submission
  const pendingSignupRef = {
    current: {
      op,
      userName,
      email,
      password,
      confirmPassword,
      role,
      firstName,
      middleName: middleInitial,
      lastName,
      suffix,
      studentNumber,
    },
  };

  // Open and bind the verification modal
  openModal(verifyModal, verifyModal.querySelector(".pin"));
  bindVerifyModalEvents(role, createVerificationHandler(pendingSignupRef));

  // Student: send verification email
  if (role === "student") {
    try {
      await requestVerificationEmail(email);
    } catch (error) {
      console.error("Verification email failed:", error);
      alert("Failed to send verification email. Please try again.");
    }
  }
}

/**
 * Sets up the unlock modal for locked accounts and handles unlock code submission.
 */
function setupUnlock() {
  const lockedCodeInput = document.getElementById("lockedCodeInput");
  const submitLockedCodeBtn = document.getElementById("submitLockedCode");
  const closeLockModalBtn = document.getElementById("closeConfirmLockModal");

  async function submitLockedCode(event) {
    if (event) event.preventDefault();
    const code = lockedCodeInput?.value.trim();
    if (!code || code.length !== 6) return alert("Enter a valid 6-digit code.");

    submitLockedCodeBtn.disabled = true;
    submitLockedCodeBtn.textContent = "Verifying...";

    try {
      const data = await requestUnlock(code);
      alert(data.message || "Account unlocked successfully.");
      closeLockedModal();
      setTimeout(() => window.location.reload(), 1000);
    } catch (err) {
      alert(err.message || "Unlock failed.");
    } finally {
      submitLockedCodeBtn.disabled = false;
      submitLockedCodeBtn.textContent = "Unlock Account";
    }
  }

  submitLockedCodeBtn?.addEventListener("click", submitLockedCode);
  closeLockModalBtn?.addEventListener("click", closeLockedModal);
}

/**
 * Sets up the forgot password and password reset process, including all UI and validation steps.
 */
function setupForgotAndReset() {
  const forgotLink = document.getElementById("forgotPasswordLink");
  const forgotEmailInput = document.getElementById("forgotEmailInput");
  const forgotSubmitBtn = document.getElementById("forgotSubmitButton");
  const submitPasswordResetBtn = document.getElementById("submitPasswordReset");
  const closeConfirmResetBtn = document.getElementById(
    "closeConfirmResetModal"
  );
  const closeForgotBtn = document.querySelector(
    "#forgotPasswordModal .close-btn"
  );

  forgotLink?.addEventListener("click", (e) => {
    e.preventDefault();
    openForgotModal();
  });

  forgotSubmitBtn?.addEventListener("click", async (event) => {
    if (event) event.preventDefault();
    const email = forgotEmailInput.value.trim();
    if (!email) return alert("Please enter your email.");

    forgotSubmitBtn.disabled = true;
    forgotSubmitBtn.textContent = "Sending...";

    try {
      const data = await requestPasswordReset(email);
      if (data.error) {
        if (data.error.includes("wait")) {
          alert(
            "Too many requests. Please wait before requesting another reset."
          );
        } else {
          alert(data.error);
        }
      } else {
        alert(data.message || "Reset code sent. Check your email.");
        closeForgotModal();
        openConfirmResetModal(email);
      }
    } catch (err) {
      alert(err.message || "Reset request failed.");
    } finally {
      forgotSubmitBtn.disabled = false;
      forgotSubmitBtn.textContent = "Send Reset Code";
    }
  });

  submitPasswordResetBtn?.addEventListener("click", async () => {
    const code = document.getElementById("resetCodeInput").value.trim();
    const newPassword = document
      .getElementById("newPasswordInput")
      .value.trim();
    const confirmPassword = document
      .getElementById("confirmNewPasswordInput")
      .value.trim();
    const email = window.pendingReset?.email;

    const validationError = validateResetInputs(
      code,
      newPassword,
      confirmPassword
    );
    if (validationError) {
      alert(validationError);
      return;
    }

    submitPasswordResetBtn.disabled = true;
    submitPasswordResetBtn.textContent = "Resetting...";

    try {
      const data = await confirmPasswordReset(email, code, newPassword);
      if (data.error) {
        alert(data.error);
      } else {
        alert(data.message || "Password reset successful!");
        closeConfirmResetModal();
      }
    } catch (err) {
      alert(err.message || "Password reset failed.");
    } finally {
      submitPasswordResetBtn.disabled = false;
      submitPasswordResetBtn.textContent = "Reset Password";
    }
  });

  closeForgotBtn?.addEventListener("click", closeForgotModal);

  // Close forgot modal when clicking outside or pressing escape
  const forgotModal = document.getElementById("forgotPasswordModal");
  if (forgotModal) {
    forgotModal.addEventListener("click", (e) => {
      if (e.target === forgotModal) {
        closeForgotModal();
      }
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && forgotModal.style.display === "flex") {
        closeForgotModal();
      }
    });
  }

  closeConfirmResetBtn?.addEventListener("click", closeConfirmResetModal);
}

/**
 * Validates the change password inputs.
 * @param {string} currentPassword - The current password.
 * @param {string} newPassword - The new password.
 * @param {string} confirmPassword - The confirmed new password.
 * @returns {string|null} An error message if validation fails, otherwise null.
 */
function validateChangePassword(currentPassword, newPassword, confirmPassword) {
  if (!currentPassword || !newPassword || !confirmPassword)
    return "Please fill out all fields.";
  if (newPassword !== confirmPassword) return "Passwords do not match.";
  if (!isValidPassword(newPassword))
    return "Password must meet complexity requirements.";
  return null;
}

/**
 * Validates the password reset inputs.
 * @param {string} code - The reset code.
 * @param {string} newPassword - The new password.
 * @param {string} confirmPassword - The confirmed new password.
 * @returns {string|null} An error message if validation fails, otherwise null.
 */
function validateResetInputs(code, newPassword, confirmPassword) {
  if (!code || code.length !== 6) return "Please enter a valid 6-digit code.";
  if (!newPassword || !confirmPassword) return "Please fill out all fields.";
  if (newPassword !== confirmPassword) return "Passwords do not match.";
  if (!isValidPassword(newPassword))
    return "Password must meet complexity requirements.";
  return null;
}

/**
 * Sets up the change password button and process, including input validation and feedback.
 */
function setupChangePassword() {
  const changeBtn = document.getElementById("changePasswordButton");
  if (!changeBtn) return;

  changeBtn.addEventListener("click", async (e) => {
    e.preventDefault();
    const email = sessionStorage.getItem("email");
    const currentPassword = document.getElementById("currentPassword").value;
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    const validationError = validateChangePassword(
      currentPassword,
      newPassword,
      confirmPassword
    );
    if (validationError) {
      alert(validationError);
      return;
    }

    try {
      const data = await changePassword(email, currentPassword, newPassword);
      alert(data.message || "Password changed successfully.");
    } catch (err) {
      alert(err.message || "Password change failed.");
    }
  });
}
