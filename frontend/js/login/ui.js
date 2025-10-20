/**
 * Switches between user and admin login using a toggle button.
 */
function initLoginSwitcher() {
  const loginForm = document.getElementById("login");
  const adminToggleBtn = document.getElementById("adminToggle");
  let isAdmin = false;

  function switchToAdmin() {
    isAdmin = true;
    loginForm.querySelector(".form-title").textContent = "Admin Login";
    loginForm.querySelector('input[type="email"]').placeholder =
      "Admin Email Address";
    loginForm.querySelector('input[type="password"]').placeholder =
      "Admin Password";
    adminToggleBtn.textContent = "Log in as User";
    // Hide Forgot Password link in admin mode
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    if (forgotPasswordLink) {
      forgotPasswordLink.style.display = 'none';
    }
  }

  function switchToUser() {
    isAdmin = false;
    loginForm.querySelector(".form-title").textContent = "Login";
    loginForm.querySelector('input[type="email"]').placeholder =
      "Email Address";
    loginForm.querySelector('input[type="password"]').placeholder = "Password";
    adminToggleBtn.textContent = "Log in as Admin";
    // Show Forgot Password link in user mode
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    if (forgotPasswordLink) {
      forgotPasswordLink.style.display = '';
    }
  }

  adminToggleBtn.addEventListener("click", function () {
    if (isAdmin) {
      switchToUser();
    } else {
      switchToAdmin();
    }
    // Optionally clear login fields
    loginForm.querySelector('input[type="email"]').value = "";
    loginForm.querySelector('input[type="password"]').value = "";
    loginForm.querySelector(".form-message--error").textContent = "";
  });

  switchToUser();
}

/**
 * Toggles password visibility on eye icon click.
 */
function initPasswordToggles() {
  document.querySelectorAll(".togglePassword").forEach((toggle) => {
    toggle.addEventListener("click", () => {
      const input = toggle.closest('.password-container').querySelector('input');
      if (!input) return;
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      const icon = toggle.querySelector('i');
      icon.classList.remove("fa-eye", "fa-eye-slash");
      icon.classList.add(show ? "fa-eye" : "fa-eye-slash");
    });
  });
}

/**
 * Prevents copy, cut, paste, context menu on password fields.
 */
function disablePasswordClipboardActions() {
  document.querySelectorAll('input[type="password"]').forEach((field) => {
    ["copy", "cut", "paste", "contextmenu"].forEach((event) =>
      field.addEventListener(event, (e) => e.preventDefault())
    );
  });
}

/**
 * Shows username suggestions from local storage during input.
 * @param {string} inputSelector - The CSS selector for the username/email input.
 */
function attachUsernameSuggestions(inputSelector) {
  const input = document.querySelector(inputSelector);
  if (!input) return;

  let suggestionBox = document.createElement("div");
  suggestionBox.className = "suggestions";
  input.parentNode.appendChild(suggestionBox);

  input.addEventListener("input", () => {
    const query = input.value.trim().toLowerCase();
    suggestionBox.innerHTML = "";
    if (query.length > 1) {
      const saved = JSON.parse(localStorage.getItem("savedUsernames")) || [];
      saved
        .filter((name) => name.toLowerCase().includes(query))
        .forEach((match) => {
          const option = document.createElement("div");
          option.className = "suggestion";
          option.textContent = match;
          option.onclick = () => {
            input.value = match;
            suggestionBox.innerHTML = "";
            input.focus();
          };
          suggestionBox.appendChild(option);
        });
    }
  });
  document.addEventListener("click", (e) => {
    if (e.target !== input && !suggestionBox.contains(e.target)) {
      suggestionBox.innerHTML = "";
    }
  });
}

/**
 * Saves a username to local storage, keeping last 5 for suggestions.
 * @param {string} username - The username to save.
 */
function saveUsername(username) {
  if (!username) return;
  const normalized = username.trim();
  let saved = JSON.parse(localStorage.getItem("savedUsernames")) || [];
  if (!saved.includes(normalized)) {
    saved.push(normalized);
    if (saved.length > 5) saved = saved.slice(-5);
    localStorage.setItem("savedUsernames", JSON.stringify(saved));
  }
}

/**
 * Clears validation hints/error messages from hint elements.
 * @param {object} hints - An object containing hint DOM elements.
 */
function clearHints(hints) {
  if (!hints) return;

  Object.values(hints).forEach((element) => {
    if (element) {
      element.textContent = "";
      element.style.display = "none";
    }
  });
}

/**
 * Checks if an email address has a valid format.
 * @param {string} email - The email address to check.
 * @returns {boolean} True if the email is valid, false otherwise.
 */
function isValidEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
}

/**
 * Checks if a password is valid (length, upper/lowercase, number, special character).
 * @param {string} password - The password to check.
 * @returns {boolean} True if the password is valid, false otherwise.
 */
function isValidPassword(password) {
  // At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character
  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
  return regex.test(password);
}

/**
 * Validates all signup fields and shows hint messages if invalid.
 * @param {object} options - The signup fields and hint elements.
 * @param {string} options.userName - The username/full name.
 * @param {string} options.email - The email address.
 * @param {string} options.password - The password.
 * @param {string} options.confirmPassword - The confirmed password.
 * @param {object} options.hints - An object containing the hint elements.
 * @returns {boolean} True if all fields are valid, false otherwise.
 */
function validateSignupFields({
  userName,
  email,
  password,
  confirmPassword,
  hints,
}) {
  let valid = true;
  clearHints(hints);

  if (!userName) {
    if (hints.username) hints.username.textContent = "Full Name is required.";
    valid = false;
  }
  if (!email) {
    if (hints.email) hints.email.textContent = "Email is required.";
    valid = false;
  } else if (!isValidEmail(email)) {
    if (hints.email) hints.email.textContent = "Enter a valid email address.";
    valid = false;
  }
  if (!password) {
    if (hints.password) hints.password.textContent = "Password is required.";
    valid = false;
  } else if (!isValidPassword(password)) {
    if (hints.password)
      hints.password.textContent =
        "Password must be 8+ characters, with upper/lowercase, a number, and a special character.";
    valid = false;
  }
  if (!confirmPassword) {
    if (hints.confirm)
      hints.confirm.textContent = "Please confirm your password.";
    valid = false;
  } else if (password !== confirmPassword) {
    if (hints.confirm) hints.confirm.textContent = "Passwords do not match.";
    valid = false;
  }

  return valid;
}

/**
 * Provides live password strength feedback in the signup form.
 */
function initPasswordStrengthMeter() {
  // Select the signup password input and constraints list
  const passwordInput = document.getElementById("signupPassword");
  const list = document.querySelector(".password-constraints-list");
  if (!passwordInput || !list) return;

  // Get each requirement list item by ID
  const lengthCheck = document.getElementById("length-check");
  const uppercaseCheck = document.getElementById("uppercase-check");
  const specialCheck = document.getElementById("special-check");
  const whitespaceCheck = document.getElementById("whitespace-check");

  passwordInput.addEventListener("input", () => {
    const val = passwordInput.value;

    if (lengthCheck)
      lengthCheck.style.color = val.length >= 8 ? "green" : "red";
    if (uppercaseCheck)
      uppercaseCheck.style.color = /[A-Z]/.test(val) ? "green" : "red";
    if (specialCheck)
      specialCheck.style.color = /[\W_]/.test(val) ? "green" : "red";
    if (whitespaceCheck)
      whitespaceCheck.style.color = !/\s/.test(val) ? "green" : "red";
  });
}
function initPasswordConstraintsLiveList() {
  const passwordInput = document.getElementById("signupPassword");
  const lengthCheck = document.getElementById("length-check");
  const uppercaseCheck = document.getElementById("uppercase-check");
  const specialCheck = document.getElementById("special-check");
  const whitespaceCheck = document.getElementById("whitespace-check");
  const list = document.querySelector(".password-constraints-list");
  const constraintItems = [
    { node: lengthCheck, check: (val) => val.length >= 8 },
    { node: uppercaseCheck, check: (val) => /[A-Z]/.test(val) },
    { node: specialCheck, check: (val) => /[\W_]/.test(val) },
    { node: whitespaceCheck, check: (val) => !/\s/.test(val) },
  ];

  // Hide all initially
  if (list) list.style.display = "none";
  constraintItems.forEach((item) => {
    if (item.node) item.node.style.display = "none";
  });

  passwordInput.addEventListener("input", () => {
    const val = passwordInput.value;
    let anyUnmet = false;

    // Only show constraints if at least 2 characters entered
    if (val.length < 2) {
      if (list) list.style.display = "none";
      constraintItems.forEach((item) => {
        if (item.node) item.node.style.display = "none";
      });
      return;
    }

    constraintItems.forEach((item) => {
      if (!item.check(val)) {
        item.node.style.display = "";
        anyUnmet = true;
      } else {
        item.node.style.display = "none";
      }
    });

    if (list) list.style.display = anyUnmet ? "block" : "none";
  });

  // On focus, only show if there's enough input
  passwordInput.addEventListener("focus", () => {
    const val = passwordInput.value;
    if (val.length >= 2) passwordInput.dispatchEvent(new Event("input"));
  });

  // On blur, hide list if all met or not enough input
  passwordInput.addEventListener("blur", () => {
    const val = passwordInput.value;
    if (
      val.length < 2 ||
      (val.length >= 8 &&
        /[A-Z]/.test(val) &&
        /[\W_]/.test(val) &&
        !/\s/.test(val))
    ) {
      if (list) list.style.display = "none";
    }
  });
}

function initConfirmPasswordLiveFeedback() {
  const passwordInput = document.getElementById("signupPassword");
  // Adjust selector if needed for your "Confirm Password" field
  const confirmInput = document.querySelector(
    'input[placeholder="Confirm Password"]'
  );
  const feedback = document.getElementById("confirm-password-feedback");

  if (!passwordInput || !confirmInput || !feedback) return;

  function checkMatch() {
    if (confirmInput.value.length === 0) {
      feedback.textContent = "";
      return;
    }
    if (confirmInput.value === passwordInput.value) {
      feedback.textContent = "Passwords match";
      feedback.style.color = "green";
    } else {
      feedback.textContent = "Passwords do not match";
      feedback.style.color = "red";
    }
  }

  // Check on every input event in either field
  passwordInput.addEventListener("input", checkMatch);
  confirmInput.addEventListener("input", checkMatch);
}

/**
 * Initializes all UI and binds everything together for modular execution.
 */
function initUI() {
  initLoginSwitcher();
  initPasswordToggles();
  disablePasswordClipboardActions();
  attachUsernameSuggestions('input[type="email"]');
  initPasswordStrengthMeter();
  initPasswordConstraintsLiveList();
  initConfirmPasswordLiveFeedback();
}

export {
  initLoginSwitcher,
  initPasswordToggles,
  disablePasswordClipboardActions,
  attachUsernameSuggestions,
  saveUsername,
  initUI,
  clearHints,
  isValidEmail,
  isValidPassword,
  validateSignupFields,
  initPasswordStrengthMeter,
};
