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

// Handle response: parse JSON, throw error if response is not OK
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

// Make an API request with config and JSON body
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

// Verify admin code during signup or login
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

// Verify student code during signup or login
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

// Call backend endpoint to validate verification code
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

// Convert backend error message into user-friendly text
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

// Handle login request, manage verification, locked accounts, or success
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

// Handle signup request for a new student/admin
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

// Redirect user to page based on their role
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

// Verify code used to unlock locked account
export async function requestUnlock(code) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.verifyLockedCode, { code });
}

// Request backend to send unlock code for locked accounts
export async function requestLockedCode(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.sendLockedCode, { email });
}

// Request backend to send verification email
export async function requestVerificationEmail(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.verify, { email });
}

// Request backend to start password reset process
export async function requestPasswordReset(email) {
  if (!config) {
    config = await getConfig();
  }
  return apiRequest(config.api.endpoints.resetPassword, { email });
}

// Confirm password reset with code and set new password
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

// Change user password with current and new password
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

// Verify admin-specific code for signup or secure actions
export async function verifyAdminCode(code) {
  if (!config) {
    config = await getConfig();
  }
  const data = await apiRequest(config.api.endpoints.verifyAdminCode, { code });
  if (!data.success) throw new Error("Invalid or expired admin code");
  return true;
}
