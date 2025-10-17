import { getConfig, configPromise } from "./config.js";
import {
  openModal,
  verifyModal,
  bindVerifyModalEvents,
  openLockedModal,
  openForgotModal,
} from "./modal.js";
import { createLoginVerificationHandler } from "./authHandler.js";

let config = null;

/**
 * Handles the response from an API request.
 * @param {Response} response - The response object from the fetch call.
 * @returns {Promise<object>} The JSON data from the response.
 * @throws {Error} If the response is not OK.
 */
export async function handleResponse(response) {
  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const error = new Error(data.error || "API request failed");
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}

/**
 * Makes an API request with a JSON body.
 * @param {string} endpoint - The API endpoint to call.
 * @param {object} body - The request body.
 * @param {object} options - Additional options for the fetch call.
 * @returns {Promise<object>} The response data.
 */
async function apiRequest(endpoint, body, options = {}) {
  if (!config) {
    try {
      config = await getConfig();
    } catch (error) {
      console.error("Failed to load config for API request:", error);
      throw new Error("Configuration not available");
    }
  }

  const response = await fetch(`${config.api.baseUrl}${endpoint}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(body),
    ...options,
  });
  return handleResponse(response);
}

/**
 * Verifies an admin code during signup or login.
 * @param {string} code - The admin verification code.
 * @param {boolean} isSignup - Whether the verification is for a signup.
 * @param {object} signupData - The signup data.
 * @returns {Promise<boolean>} True if the verification is successful.
 * @throws {Error} If the verification fails.
 */
export async function handleAdminVerification(code, isSignup, signupData) {
  try {
    if (isSignup) {
      const isValid = await verifyAdminCode(code);
      if (!isValid) {
        throw new Error("Invalid or expired admin verification code");
      }
      return true;
    }

    if (!config) {
      config = await getConfig();
    }

    const requestBody = { pin: code };
    await verifyCode(config.api.endpoints.verifyCode, requestBody);
    return true;
  } catch (error) {
    console.error("Admin verification failed:", error);
    throw error;
  }
}

/**
 * Verifies a student code during signup or login.
 * @param {string} code - The student verification code.
 * @param {boolean} isSignup - Whether the verification is for a signup.
 * @param {object} signupData - The signup data.
 * @returns {Promise<boolean>} True if the verification is successful.
 * @throws {Error} If the verification fails.
 */
export async function handleStudentVerification(code, isSignup, signupData) {
  try {
    if (!config) {
      config = await getConfig();
    }

    const requestBody = isSignup
      ? { email: signupData.email, pin: code }
      : { pin: code };

    await verifyCode(config.api.endpoints.verifyCode, requestBody);
    return true;
  } catch (error) {
    console.error("Student verification failed:", error);
    throw error;
  }
}

/**
 * Calls the backend endpoint to validate a verification code.
 * @param {string} endpoint - The API endpoint to call.
 * @param {object} requestBody - The request body.
 * @returns {Promise<boolean>} True if the verification is successful.
 * @throws {Error} If the verification fails.
 */
async function verifyCode(endpoint, requestBody) {
  try {
    const data = await apiRequest(endpoint, requestBody);
    const isSuccess = !!(
      data.message &&
      data.message.toLowerCase().includes("verification successful")
    );

    if (!isSuccess) {
      throw new Error("Invalid or expired verification code");
    }

    return true;
  } catch (error) {
    console.error("Verification failed:", error);
    throw error;
  }
}

/**
 * Converts a backend error message into user-friendly text.
 * @param {string} error - The error message from the backend.
 * @returns {string} The sanitized error message.
 */
function sanitizeErrorMessage(error) {
  if (typeof error !== "string")
    return "An unexpected error occurred. Please try again.";
  const errorMap = {
    "Invalid username or password":
      "Incorrect username or password. Please check and try again.",
    "Account not found":
      "Account not found. Please sign up or contact support.",
    "Too many failed attempts":
      "Too many login attempts. Please wait and try again.",
  };
  return (
    errorMap[error] ||
    "Login failed. Please contact support if the issue persists."
  );
}

/**
 * Handles a login request, managing verification, locked accounts, or success.
 * @param {string} userName - The user's username.
 * @param {string} password - The user's password.
 * @param {string} captchaToken - The reCAPTCHA token.
 * @param {string} expectedRole - The expected role of the user.
 * @returns {Promise<object>} An object containing the login result.
 * @throws {Error} If the login fails.
 */
export async function loginUser(
  userName,
  password,
  captchaToken,
  expectedRole
) {
  try {
    if (!config) {
      config = await getConfig();
    }

    const data = await apiRequest(config.api.endpoints.login, {
      userName,
      password,
      captchaToken,
      expectedRole,
    });
    console.log("[loginUser] Full response data:", data);

    if (data.error === "Account is locked" && data.requiresCode) {
      console.log("Locked account detected, opening modal");
      await requestLockedCode(data.email);
      openLockedModal();
      throw new Error("Account is locked");
    }

    if (data.error) {
      console.error("[loginUser] Backend error:", data.error);
      throw new Error(sanitizeErrorMessage(data.error));
    }

    if (data.requiresVerification) {
      console.log("[loginUser] Verification required, showing modal");
      if (data.userName) sessionStorage.setItem("userName", data.userName);
      if (data.email) sessionStorage.setItem("email", data.email);
      openModal(verifyModal, verifyModal.querySelector(".pin"));
      bindVerifyModalEvents(expectedRole, createLoginVerificationHandler());
      throw new Error("Verification required");
    }

    if (!data.role) {
      throw new Error("Invalid login response from server");
    }

    if (data.userName) sessionStorage.setItem("userName", data.userName);
    if (data.email) sessionStorage.setItem("email", data.email);

    console.log("[loginUser] Login successful for role:", data.role);
    return {
      success: true,
      role: data.role,
      userName: data.userName,
      email: data.email,
    };
  } catch (err) {
    console.error("[loginUser] Login failed:", err);
    throw err;
  }
}

/**
 * Handles a signup request for a new student or admin.
 * @param {string} role - The role of the new user.
 * @param {object} signupData - The signup data.
 * @returns {Promise<object>} The response data.
 */
export async function handleSignupFlow(role, signupData) {
  const { userName, email, password, confirmPassword } = signupData;

  if (!config) {
    config = await getConfig();
  }

  return apiRequest(config.api.endpoints.signup, {
    userName,
    email,
    password,
    confirmPassword: confirmPassword || password,
    role,
  });
}

/**
 * Redirects the user to a page based on their role.
 * @param {string} roleFromLogin - The user's role.
 * @throws {Error} If the role is unknown.
 */
export function handleLoginFlow(roleFromLogin) {
  const redirects = {
    student: "./catalog.html",
    admin: "./adminDashboard.html",
    superadmin: "./superadmin.html",
  };

  const redirectUrl = redirects[roleFromLogin];
  if (!redirectUrl) {
    throw new Error(`Unknown role: ${roleFromLogin}. Cannot redirect.`);
  }

  window.location.href = redirectUrl;
}

/**
 * Verifies a code used to unlock a locked account.
 * @param {string} code - The unlock code.
 * @returns {Promise<object>} The response data.
 */
export async function requestUnlock(code) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.verifyLockedCode, { code });
}

/**
 * Requests the backend to send an unlock code for a locked account.
 * @param {string} email - The user's email address.
 * @returns {Promise<object>} The response data.
 */
export async function requestLockedCode(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.sendLockedCode, { email });
}

/**
 * Requests the backend to send a verification email.
 * @param {string} email - The user's email address.
 * @returns {Promise<object>} The response data.
 */
export async function requestVerificationEmail(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.verify, { email });
}

/**
 * Requests the backend to start the password reset process.
 * @param {string} email - The user's email address.
 * @returns {Promise<object>} The response data.
 */
export async function requestPasswordReset(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.resetPassword, { email });
}

/**
 * Confirms a password reset with a code and sets a new password.
 * @param {string} email - The user's email address.
 * @param {string} code - The reset code.
 * @param {string} newPassword - The new password.
 * @returns {Promise<object>} The response data.
 */
export async function confirmPasswordReset(email, code, newPassword) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.confirmResetPassword, {
    email,
    code,
    newPassword,
  });
}

/**
 * Changes a user's password using their current and new passwords.
 * @param {string} email - The user's email address.
 * @param {string} currentPassword - The user's current password.
 * @param {string} newPassword - The user's new password.
 * @returns {Promise<object>} The response data.
 */
export async function changePassword(email, currentPassword, newPassword) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.changePassword, {
    email,
    oldPassword: currentPassword,
    newPassword,
  });
}

/**
 * Verifies an admin-specific code for signup or secure actions.
 * @param {string} code - The admin code.
 * @returns {Promise<boolean>} True if the code is valid.
 * @throws {Error} If the code is invalid.
 */
export async function verifyAdminCode(code) {
  if (!config) {
    config = await getConfig();
  }
  const data = await apiRequest(config.api.endpoints.verifyAdminCode, { code });
  if (!data.success) throw new Error("Invalid or expired admin code");
  return true;
}

//BOOKS RELATED

/**
 * Fetches books from the API.
 * @param {object} params - The query parameters for the request.
 * @returns {Promise<object>} The response data.
 */
export async function fetchBooks(params = {}) {
  if (!config) config = await getConfig();
  const query = new URLSearchParams(params).toString();
  const endpoint = config.api.endpoints.books + (query ? `?${query}` : "");
  const response = await fetch(`${config.api.baseUrl}${endpoint}`, {
    method: "GET",
    credentials: "include",
  });
  return handleResponse(response);
}

/**
 * Creates a new book.
 * @param {object} bookData - The data for the new book.
 * @returns {Promise<object>} The response data.
 */
export async function createBook(bookData) {
  if (!config) config = await getConfig();
  return apiRequest(config.api.endpoints.books, bookData);
}

/**
 * Updates an existing book.
 * @param {number} bookId - The ID of the book to update.
 * @param {object} bookData - The new data for the book.
 * @returns {Promise<object>} The response data.
 */
export async function updateBook(bookId, bookData) {
  if (!config) config = await getConfig();
  const endpoint = `${config.api.endpoints.books}?id=${bookId}`;
  return apiRequest(endpoint, bookData, { method: "PUT" });
}

/**
 * Deletes a book.
 * @param {number} bookId - The ID of the book to delete.
 * @returns {Promise<object>} The response data.
 */
export async function deleteBook(bookId) {
  if (!config) config = await getConfig();
  const endpoint = `${config.api.endpoints.books}?id=${bookId}`;
  return apiRequest(endpoint, {}, { method: "DELETE" });
}
