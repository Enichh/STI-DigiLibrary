// Clear all admin signup fields and return to login panel
function resetAdminForm() {
  document.getElementById("new_admin_username").value = "";
  document.getElementById("new_admin_email").value = "";
  document.getElementById("new_admin_password").value = "";
  document.getElementById("new_admin_confirm").value = "";
  document.getElementById("admin_back").click();
}

// Clear student signup fields, store username/email in session, go to catalog
function resetStudentForm(userName, email) {
  document.getElementById("new_user_username").value = "";
  document.getElementById("new_user_email").value = "";
  document.getElementById("new_user_password").value = "";
  document.getElementById("new_user_confirm").value = "";
  sessionStorage.setItem("userName", userName);
  sessionStorage.setItem("email", email);
  window.location.href = "./catalog.html";
}

// Toggle password visibility for password fields
function initPasswordToggles() {
  const togglePasswords = document.querySelectorAll(".togglePassword");
  togglePasswords.forEach((toggle) => {
    toggle.addEventListener("click", () => {
      const passwordInput = toggle.previousElementSibling;
      if (!passwordInput) return;
      const isHidden = passwordInput.type === "password";
      passwordInput.type = isHidden ? "text" : "password";
      toggle.classList.remove("fa-eye", "fa-eye-slash");
      toggle.classList.add(isHidden ? "fa-eye" : "fa-eye-slash");
    });
  });
}

// Handle transitions between login and signup forms for users/admins
function initFormToggles() {
  const mainBtn = document.getElementById("login_signup_submitter");

  const userLogin = document.querySelector("#user_login .form_fields");
  const userSignup = document.querySelector("#user_login .signup_fields");

  document.getElementById("user_signup").addEventListener("click", () => {
    userLogin.style.display = "none";
    userSignup.style.display = "block";
    mainBtn.textContent = "Sign Up";
    window.currentMode = "student-signup";
  });

  document.getElementById("user_back").addEventListener("click", () => {
    userSignup.style.display = "none";
    userLogin.style.display = "block";
    mainBtn.textContent = "Login";
    window.currentMode = "student-login";
  });

  const adminLogin = document.querySelector("#admin_login .form_fields");
  const adminSignup = document.querySelector("#admin_login .signup_fields");

  document.getElementById("admin_signup").addEventListener("click", () => {
    adminLogin.style.display = "none";
    adminSignup.style.display = "block";
    mainBtn.textContent = "Sign Up";
    window.currentMode = "admin-signup";
  });

  document.getElementById("admin_back").addEventListener("click", () => {
    adminSignup.style.display = "none";
    adminLogin.style.display = "block";
    mainBtn.textContent = "Login";
    window.currentMode = "admin-login";
  });
}

// Switch between Student and Admin login tab, set current mode
function initLoginTabs() {
  const btnUserLoginTab = document.getElementById("stuLoginTitle");
  const btnAdminLoginTab = document.getElementById("adminLoginTitle");
  const formUserLogin = document.getElementById("user_login");
  const formAdminLogin = document.getElementById("admin_login");

  window.currentMode = "student-login";

  btnUserLoginTab.addEventListener("click", () => {
    window.currentMode = "student-login";
    formUserLogin.style.display = "block";
    formAdminLogin.style.display = "none";
    btnUserLoginTab.classList.add("active_tab");
    btnAdminLoginTab.classList.remove("active_tab");
    document.getElementById("admin_username").value = "";
    document.getElementById("admin_password").value = "";
  });

  btnAdminLoginTab.addEventListener("click", () => {
    window.currentMode = "admin-login";
    formUserLogin.style.display = "none";
    formAdminLogin.style.display = "block";
    btnAdminLoginTab.classList.add("active_tab");
    btnUserLoginTab.classList.remove("active_tab");
    document.getElementById("user_username").value = "";
    document.getElementById("user_password").value = "";
  });
}

// Prevent copy, cut, paste, and context menu on password fields for security
function disablePasswordClipboardActions() {
  const passwordFields = document.querySelectorAll('input[type="password"]');
  passwordFields.forEach((field) => {
    field.addEventListener("copy", (e) => e.preventDefault());
    field.addEventListener("cut", (e) => e.preventDefault());
    field.addEventListener("paste", (e) => e.preventDefault());
    field.addEventListener("contextmenu", (e) => e.preventDefault());
  });
}

// Show username suggestions from localStorage during input
function attachUsernameSuggestions(inputId) {
  const input = document.getElementById(inputId);
  if (!input) {
    console.warn(`[Suggestions] Input with id="${inputId}" not found.`);
    return;
  }
  if (input.nextElementSibling?.classList.contains("suggestions")) {
    console.info(`[Suggestions] Suggestions already attached to #${inputId}`);
    return;
  }

  const suggestionBox = document.createElement("div");
  suggestionBox.classList.add("suggestions");
  input.insertAdjacentElement("afterend", suggestionBox);

  input.addEventListener("input", () => {
    const query = input.value.trim().toLowerCase();
    suggestionBox.innerHTML = "";
    if (query.length > 1) {
      const saved = JSON.parse(localStorage.getItem("savedUsernames")) || [];
      const matches = saved.filter((name) =>
        name.toLowerCase().includes(query)
      );
      matches.forEach((match) => {
        const option = document.createElement("div");
        option.classList.add("suggestion");
        option.textContent = match;
        option.addEventListener("click", () => {
          input.value = match;
          suggestionBox.innerHTML = "";
          input.focus();
        });
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

// Save username to localStorage, keep last 5 for suggestions
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

// Check if email address is valid format
function isValidEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
}

// Check if password is valid (length, upper/lowercase, number, special char)
function isValidPassword(password) {
  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
  return regex.test(password);
}

// Clear all signup hint messages (errors/tips)
function clearHints(hints) {
  Object.values(hints).forEach((hint) => (hint.textContent = ""));
}

// Validate all signup fields and show hint messages if invalid
function validateSignupFields({
  userName,
  email,
  password,
  confirmPassword,
  hints,
}) {
  if (!userName || !email || !password || !confirmPassword) {
    alert("Please fill out all fields before signing up.");
    return false;
  }
  if (!isValidEmail(email)) {
    hints.email.textContent = "Please enter a valid email address.";
    return false;
  }
  if (!isValidPassword(password)) {
    hints.password.textContent =
      "Password must be 8+ characters, with upper/lowercase letters and a special character.";
    return false;
  }
  if (password !== confirmPassword) {
    hints.confirm.textContent = "Passwords do not match.";
    return false;
  }
  return true;
}

// Provide live password strength feedback in signup form
function initPasswordStrengthMeter() {
  function getActiveElements() {
    const isAdmin =
      document.getElementById("new_admin_password")?.offsetParent !== null;

    const prefix = isAdmin ? "admin" : "user";

    return {
      passwordInput: document.getElementById(`new_${prefix}_password`),
      confirmInput: document.getElementById(`new_${prefix}_confirm`),
      msg: document.getElementById(`${prefix}_password_hint`),
      confirmHint: document.getElementById(`${prefix}_confirm_hint`),
      activeStrengthBar: document.getElementById(
        isAdmin ? "admin_strength-bar" : "strength-bar"
      ),
    };
  }

  function strengthChecker(passwordInput, msg, activeStrengthBar) {
    const password = passwordInput.value;
    const checks = {
      letters: /[A-Za-z]/.test(password),
      numbers: /\d/.test(password),
      special: /[!#"$%&/()=?@~`\\.;:+=^*_-]/.test(password),
      count: password.length >= 8,
    };
    const score = Object.values(checks).filter(Boolean).length;

    if (!activeStrengthBar) return;

    activeStrengthBar.innerHTML = "";
    msg.textContent = "";

    if (score === 0) {
      return;
    }

    for (let i = 0; i < score; i++) {
      const span = document.createElement("span");
      span.className = "strength";
      activeStrengthBar.appendChild(span);
    }

    const spanRef = activeStrengthBar.getElementsByClassName("strength");
    for (let i = 0; i < spanRef.length; i++) {
      switch (score) {
        case 1:
          spanRef[i].style.background = "#ff3e36";
          msg.textContent = "Your password is very weak";
          msg.style.color = "#ff3e36";
          break;
        case 2:
          spanRef[i].style.background = "#ff691f";
          msg.textContent = "Your password is weak";
          msg.style.color = "#ff691f";
          break;
        case 3:
          spanRef[i].style.background = "#f4ce27ff";
          msg.textContent = "Your password is good";
          msg.style.color = "#e6cd5eff";
          break;
        case 4:
          spanRef[i].style.background = "#0be881";
          msg.textContent = "Your password is strong";
          msg.style.color = "#0be881";
          break;
      }
    }
  }

  document.addEventListener("input", (event) => {
    const { passwordInput, confirmInput, msg, confirmHint, activeStrengthBar } =
      getActiveElements();

    if (event.target === passwordInput) {
      strengthChecker(passwordInput, msg, activeStrengthBar);
    }
    if (confirmInput.value && !passwordInput.value) {
      confirmHint.textContent =
        "Create password field is required before confirmation";
      confirmHint.style.color = "red";
      return;
    }

    if (!confirmInput.value || !passwordInput.value) {
      confirmHint.textContent = "";
      confirmHint.style.color = "";
      return;
    }

    if (event.target === confirmInput && confirmHint) {
      const match = confirmInput.value === passwordInput.value;
      confirmHint.textContent = match
        ? "Passwords match"
        : "Passwords do not match";
      confirmHint.style.color = match ? "green" : "red";
    }
  });
}

// Initialize all UI functionality for login/signup view
function initUI() {
  initFormToggles();
  initLoginTabs();
  initPasswordToggles();
  initPasswordStrengthMeter();
  disablePasswordClipboardActions();
  attachUsernameSuggestions("user_username");
  attachUsernameSuggestions("admin_username");
}

export {
  resetAdminForm,
  resetStudentForm,
  initPasswordToggles,
  initFormToggles,
  initLoginTabs,
  attachUsernameSuggestions,
  saveUsername,
  isValidEmail,
  isValidPassword,
  clearHints,
  validateSignupFields,
  initPasswordStrengthMeter,
  initUI,
  disablePasswordClipboardActions,
};
