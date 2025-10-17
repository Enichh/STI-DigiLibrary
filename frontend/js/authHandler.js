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
import { getConfig } from "./config.js";

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
 * Attaches all authentication-related event handlers for login, signup, unlock, forgot/reset, and change password.
 */
export function attachAuthHandlers() {
  console.log("[Auth] Attaching authentication handlers");
  const startTime = performance.now();

  try {
    setupLoginAndSignup();
    setupUnlock();
    setupForgotAndReset();
    setupChangePassword();

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

          window.location.href = "./login.html";
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
 * Sets up the login form handler.
 */
function setupLoginHandler() {
  document.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const mainBtn = document.getElementById("login_signup_submitter");
      const isLogin = mainBtn.textContent.trim().toLowerCase() === "login";
      console.log(
        `[Auth] Enter key pressed, triggering ${isLogin ? "login" : "signup"}`
      );
      mainBtn.click();
    }
  });
}

/**
 * Sets up the signup form handler.
 */
function setupSignupHandler() {
  console.log("[Auth] Setting up signup handler");

  // Handle back to login buttons
  const backToLoginBtns = document.querySelectorAll("[id$='_back']");
  backToLoginBtns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const form = e.target.closest("form");
      if (form) {
        const loginForm = form.querySelector(".form_fields");
        const signupForm = form.querySelector(".signup_fields");
        if (loginForm && signupForm) {
          loginForm.style.display = "block";
          signupForm.style.display = "none";
        }
      }
    });
  });
}

/**
 * Sets up the main login/signup button handler.
 */
function setupLoginAndSignup() {
  console.log("[Auth] Setting up main login/signup handler");
  const startTime = performance.now();

  const mainBtn = document.getElementById("login_signup_submitter");
  if (!mainBtn) {
    console.warn("[Auth] Main login/signup button not found");
    return;
  }

  // Single click handler for the main button
  mainBtn.onclick = async (e) => {
    e.preventDefault();
    const isLogin = mainBtn.textContent.trim().toLowerCase() === "login";
    const isUserSignup = window.currentMode === "student-signup";

    console.log("[Auth] Main button clicked", {
      mode: window.currentMode,
      isLogin,
      isUserSignup,
    });

    if (!window.currentMode) {
      console.error("[Auth] Current mode not set");
      return;
    }

    try {
      if (isLogin) {
        await handleLogin(window.currentMode === "student-login");
      } else {
        await handleSignup(isUserSignup);
      }
    } catch (error) {
      console.error("[Auth] Error in main button handler:", error);
    }
  };

  console.log(
    `[Auth] Main button handler setup completed in ${
      performance.now() - startTime
    }ms`
  );

  // Set up other UI handlers if needed
  const currentMode = window.currentMode;
  if (currentMode === "student-login" || currentMode === "admin-login") {
    setupLoginHandler();
  } else if (
    currentMode === "student-signup" ||
    currentMode === "admin-signup"
  ) {
    setupSignupHandler();
  }
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
 * @param {boolean} isUserLogin - Whether the login is for a student or admin.
 */
function handleLogin(isUserLogin) {
  const op = `login-${Date.now()}`;
  const startTime = performance.now();
  const role = isUserLogin ? "student" : "admin";

  console.log(`[Auth] Starting ${role} login`, {
    timestamp: new Date().toISOString(),
    operation: op,
  });

  const username = document
    .getElementById(isUserLogin ? "user_username" : "admin_username")
    ?.value.trim();
  const password = document.getElementById(
    isUserLogin ? "user_password" : "admin_password"
  )?.value;
  const expectedRole = isUserLogin ? "student" : "admin";

  if (!username || !password) {
    const errorMsg = "Please enter username and password.";
    console.warn(`[Auth] ${op} - ${errorMsg}`);
    alert(errorMsg);
    return;
  }

  console.log(`[Auth] ${op} - Validating credentials for ${username}`);
  saveUsername(username);

  executeRecaptcha("login", (captchaToken) => {
    const recaptchaTime = performance.now();
    console.log(
      `[Auth] ${op} - reCAPTCHA completed in ${recaptchaTime - startTime}ms`
    );

    console.log(`[Auth] ${op} - Initiating login for ${username}`);
    loginUser(username, password, captchaToken, expectedRole)
      .then(() => {
        console.log(
          `[Auth] ${op} - Login flow completed successfully in ${
            performance.now() - startTime
          }ms`
        );
      })
      .catch((error) => {
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
 * @param {boolean} isUserSignup - Whether the signup is for a student or admin.
 */
async function handleSignup(isUserSignup) {
  const op = `signup-${Date.now()}`;
  const role = isUserSignup ? "student" : "admin";

  const userName = document.getElementById(
    isUserSignup ? "new_user_username" : "new_admin_username"
  ).value;
  const password = document.getElementById(
    isUserSignup ? "new_user_password" : "new_admin_password"
  ).value;
  const email = document.getElementById(
    isUserSignup ? "new_user_email" : "new_admin_email"
  ).value;
  const confirmPassword = document.getElementById(
    isUserSignup ? "new_user_confirm" : "new_admin_confirm"
  ).value;

  const hints = {
    email: document.getElementById(
      isUserSignup ? "user_email_hint" : "admin_email_hint"
    ),
    password: document.getElementById(
      isUserSignup ? "user_password_hint" : "admin_password_hint"
    ),
    confirm: document.getElementById(
      isUserSignup ? "user_confirm_hint" : "admin_confirm_hint"
    ),
    username: document.getElementById(
      isUserSignup ? "user_username_hint" : "admin_username_hint"
    ),
  };

  clearHints(hints);

  if (
    !validateSignupFields({
      userName,
      email,
      password,
      confirmPassword,
      hints,
    })
  ) {
    return;
  }

  const pendingSignupRef = {
    current: { op, userName, email, password, confirmPassword, role },
  };

  openModal(verifyModal, verifyModal.querySelector(".pin"));
  bindVerifyModalEvents(role, createVerificationHandler(pendingSignupRef));

  if (role === "student") {
    try {
      await requestVerificationEmail(email);
    } catch (error) {
      console.error("Verification email failed:", error);
      alert("Failed to send verification email. Please try again.");
      return;
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
