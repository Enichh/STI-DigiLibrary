/**
 * Utility: Checks if a student number is valid: 11 digits starting with 02000.
 * @param {string} studentNumber
 * @returns {boolean}
 */
function isValidStudentNumber(studentNumber) {
  return /^02000\d{6}$/.test(studentNumber);
}

/**
 * Utility: Clears validation hints/error messages from hint elements.
 * @param {object} hints - An object containing hint DOM elements.
 */
function clearHints(hints) {
  if (!hints) return;
  Object.values(hints).forEach((hint) => {
    if (hint) {
      hint.textContent = "";
      hint.style.display = "none";
    }
  });
}

/**
 * Utility: Checks if an email address is valid.
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
  const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  return regex.test(email);
}

/**
 * Utility: Checks if a password meets standard complexity requirements.
 * 8+ chars, upper/lowercase, digit, special char, no whitespace.
 * @param {string} password
 * @returns {boolean}
 */
function isValidPassword(password) {
  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])(?!.*\s).{8,}$/;
  return regex.test(password);
}

/**
 * Validates all signup fields and shows hint messages if invalid.
 * Accepts all user fields except programId/yearLevel.
 * @param {object} fields - User registration fields and hints.
 * @returns {boolean}
 */
function validateSignupFields({
  firstName,
  middleName,
  lastName,
  suffix,
  studentNumber,
  email,
  password,
  confirmPassword,
  hints,
}) {
  let valid = true;
  clearHints(hints);

  const required = [
    { value: firstName, key: "firstName", msg: "First name is required." },
    { value: lastName, key: "lastName", msg: "Last name is required." },
    { value: email, key: "email", msg: "Email is required." },
    { value: password, key: "password", msg: "Password is required." },
    {
      value: confirmPassword,
      key: "confirm",
      msg: "Please confirm your password.",
    },
    {
      value: studentNumber,
      key: "studentNumber",
      msg: "Student number is required.",
    },
  ];

  required.forEach((field) => {
    if (!field.value) {
      if (hints[field.key]) {
        hints[field.key].textContent = field.msg;
        hints[field.key].style.display = "block";
      }
      valid = false;
    }
  });

  if (studentNumber && !isValidStudentNumber(studentNumber)) {
    if (hints.studentNumber) {
      hints.studentNumber.textContent =
        "Student number must be 11 digits starting with 02000";
      hints.studentNumber.style.display = "block";
    }
    valid = false;
  }

  if (email && !isValidEmail(email)) {
    if (hints.email) {
      hints.email.textContent = "Enter a valid email address.";
      hints.email.style.display = "block";
    }
    valid = false;
  }

  if (password && !isValidPassword(password)) {
    if (hints.password) {
      hints.password.textContent =
        "Password must be 8+ characters, with upper/lowercase, a number, and a special character, and no whitespace.";
      hints.password.style.display = "block";
    }
    valid = false;
  }

  if (password && confirmPassword && password !== confirmPassword) {
    if (hints.confirm) {
      hints.confirm.textContent = "Passwords do not match.";
      hints.confirm.style.display = "block";
    }
    valid = false;
  }

  return valid;
}

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
    document.getElementById("loginEmail").placeholder = "Admin Email Address";
    document.getElementById("loginPassword").placeholder = "Admin Password";
    adminToggleBtn.textContent = "Log in as User";
    const forgotPasswordLink = document.getElementById("forgotPasswordLink");
    const signupLink = document.querySelector(".form-text:last-of-type");
    if (forgotPasswordLink) forgotPasswordLink.style.display = "none";
    if (signupLink) signupLink.style.display = "none";
  }

  function switchToUser() {
    isAdmin = false;
    loginForm.querySelector(".form-title").textContent = "Login";
    document.getElementById("loginEmail").placeholder = "Email Address";
    document.getElementById("loginPassword").placeholder = "Password";
    adminToggleBtn.textContent = "Log in as Admin";
    const forgotPasswordLink = document.getElementById("forgotPasswordLink");
    const signupLink = document.querySelector(".form-text:last-of-type");
    if (forgotPasswordLink) forgotPasswordLink.style.display = "";
    if (signupLink) signupLink.style.display = "";
  }

  adminToggleBtn.addEventListener("click", function () {
    if (isAdmin) {
      switchToUser();
    } else {
      switchToAdmin();
    }
    document.getElementById("loginEmail").value = "";
    document.getElementById("loginPassword").value = "";
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
      const input = toggle
        .closest(".password-container")
        .querySelector("input");
      if (!input) return;
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      const icon = toggle.querySelector("i");
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
 * @param {string} inputSelector - CSS selector for input.
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
 * Saves a username to local storage, max 5 for suggestions.
 * @param {string} username
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
 * Provides dynamic, live password strength feedback.
 * Shows constraints list only when typing, hides when empty or all met.
 * Unmet constraints show in red, met constraints show in green with strikethrough.
 */
function initPasswordStrengthMeter() {
  const passwordInput = document.getElementById("signupPassword");
  const list = document.querySelector(".password-constraints-list");
  if (!passwordInput || !list) return;

  const lengthCheck = document.getElementById("length-check");
  const uppercaseCheck = document.getElementById("uppercase-check");
  const specialCheck = document.getElementById("special-check");
  const whitespaceCheck = document.getElementById("whitespace-check");

  // Hide initially
  list.style.display = "none";

  passwordInput.addEventListener("input", () => {
    const val = passwordInput.value;

    // If empty, hide list
    if (val.length === 0) {
      list.style.display = "none";
      return;
    }

    // Show list when user starts typing
    list.style.display = "block";

    // Check each constraint and update styling
    const lengthValid = val.length >= 8;
    const uppercaseValid = /[A-Z]/.test(val);
    const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(val);
    const whitespaceValid = !/\s/.test(val);

    if (lengthCheck) {
      lengthCheck.style.color = lengthValid ? "green" : "red";
      lengthCheck.style.textDecoration = lengthValid ? "line-through" : "none";
    }

    if (uppercaseCheck) {
      uppercaseCheck.style.color = uppercaseValid ? "green" : "red";
      uppercaseCheck.style.textDecoration = uppercaseValid
        ? "line-through"
        : "none";
    }

    if (specialCheck) {
      specialCheck.style.color = specialValid ? "green" : "red";
      specialCheck.style.textDecoration = specialValid
        ? "line-through"
        : "none";
    }

    if (whitespaceCheck) {
      whitespaceCheck.style.color = whitespaceValid ? "green" : "red";
      whitespaceCheck.style.textDecoration = whitespaceValid
        ? "line-through"
        : "none";
    }

    // Optional: Hide list when all constraints are met
    if (lengthValid && uppercaseValid && specialValid && whitespaceValid) {
      list.style.display = "none";
    }
  });

  // Hide list when field loses focus if empty
  passwordInput.addEventListener("blur", () => {
    if (passwordInput.value.length === 0) {
      list.style.display = "none";
    }
  });
}

/**
 * Modular UI initializer.
 */
function initUI() {
  initLoginSwitcher();
  initPasswordToggles();
  disablePasswordClipboardActions();
  attachUsernameSuggestions('input[type="email"]');
  initPasswordStrengthMeter();
}

/**
 * Exported API
 */
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
