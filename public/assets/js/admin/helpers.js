// helpers.js

/**
 * Generic API request wrapper with error handling and CORS support
 * @param {string} url - The endpoint URL.
 * @param {object} options - Fetch options (method, headers, body, etc.).
 * @returns {Promise<any>} - Parsed JSON response.
 */
export async function apiRequest(url, options = {}) {
  try {
    // Ensure URL is absolute if it's not already
    const fullUrl = url.startsWith('http') ? url : `${window.location.origin}${url}`;
    
    const defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    // Ensure we're using credentials for all requests
    const fetchOptions = {
      ...options,
      credentials: 'include',
      mode: 'cors',
      headers: {
        ...defaultHeaders,
        ...(options.headers || {})
      }
    };

    // For non-GET requests, ensure we stringify the body if it's an object
    if (fetchOptions.body && typeof fetchOptions.body === 'object') {
      fetchOptions.body = JSON.stringify(fetchOptions.body);
    }

    const res = await fetch(fullUrl, fetchOptions);

    if (!res.ok) {
      const errorText = await res.text();
      throw new Error(`API error ${res.status}: ${errorText}`);
    }

    return await res.json();
  } catch (err) {
    handleApiError(err);
    throw err;
  }
}

/**
 * Centralized error handler.
 * @param {Error} error - The error object.
 */
export function handleApiError(error) {
  console.error("API Error:", error);
  alert("Something went wrong. Please try again.");
}

/**
 * Serialize a form into a plain object.
 * @param {HTMLFormElement} formElement
 * @returns {object}
 */
export function serializeForm(formElement) {
  const formData = new FormData(formElement);
  return Object.fromEntries(formData.entries());
}

/**
 * Open a modal by ID.
 * @param {string} id
 */
export function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = "block";
}

/**
 * Close a modal by ID.
 * @param {string} id
 */
export function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = "none";
}

/**
 * Format a date string into a human‑readable format.
 * @param {string|Date} dateStr
 * @returns {string}
 */
export function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString();
}

/**
 * Format a number into Philippine Peso currency.
 * @param {number} amount
 * @returns {string}
 */
export function formatCurrency(amount) {
  return `₱${parseFloat(amount).toFixed(2)}`;
}

/**
 * Utility to clear and repopulate a container element.
 * @param {HTMLElement} container
 * @param {string} html
 */
export function renderHTML(container, html) {
  if (container) container.innerHTML = html;
}

/**
 * Show a customizable, non-blocking alert modal.
 * @param {string} title - The title of the modal.
 * @param {string} message - The message content.
 * @param {'info'|'success'|'error'} [type='info'] - Alert type for styling.
 */
export function showCustomAlert(title, message, type = "info") {
  let modal = document.getElementById("global-alert-modal");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "global-alert-modal";
    modal.className = "modal";
    modal.innerHTML = `
      <div class="modal-content" style="max-width: 400px; text-align: center;">
        <span class="close-btn" style="position:absolute; top: 10px; right: 20px; cursor:pointer;">&times;</span>
        <h2 class="modal-title"></h2>
        <p class="modal-message"></p>
        <button class="btn btn-primary ok-btn">OK</button>
      </div>
    `;
    document.body.appendChild(modal);

    const closeBtn = modal.querySelector(".close-btn");
    const okBtn = modal.querySelector(".ok-btn");
    const closeModal = () => (modal.style.display = "none");
    closeBtn.addEventListener("click", closeModal);
    okBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  }

  const titleEl = modal.querySelector(".modal-title");
  const msgEl = modal.querySelector(".modal-message");
  msgEl.textContent = message;

  if (type === "success") {
    titleEl.textContent = title || "Success";
    titleEl.style.color = "var(--success)";
  } else if (type === "error") {
    titleEl.textContent = title || "Error";
    titleEl.style.color = "var(--danger)";
  } else {
    titleEl.textContent = title || "Notification";
    titleEl.style.color = "var(--darkblue)";
  }

  modal.style.display = "block";
}

export function showCustomConfirm(
  title,
  message,
  confirmText = "Yes",
  cancelText = "Cancel",
  confirmClass = "btn-danger"
) {
  return new Promise((resolve) => {
    let modal = document.getElementById("global-confirm-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "global-confirm-modal";
      modal.className = "modal";
      modal.innerHTML = `
        <div class="modal-content" style="max-width: 450px; text-align:center;">
          <span class="close-btn" style="position:absolute; top: 10px; right: 20px; cursor:pointer;">&times;</span>
          <h2 class="modal-title"></h2>
          <p class="modal-message"></p>
          <div class="actions" style="margin-top:20px; display:flex; justify-content:center; gap:15px;">
            <button class="btn confirm-yes-btn">Confirm</button>
            <button class="btn btn-secondary cancel-no-btn">Cancel</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);

      const closeBtn = modal.querySelector(".close-btn");
      const cancelBtn = modal.querySelector(".cancel-no-btn");
      const closeModal = () => (modal.style.display = "none");
      closeBtn.addEventListener("click", () => {
        closeModal();
        resolve(false);
      });
      cancelBtn.addEventListener("click", () => {
        closeModal();
        resolve(false);
      });
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          closeModal();
          resolve(false);
        }
      });
    }

    const titleEl = modal.querySelector(".modal-title");
    const msgEl = modal.querySelector(".modal-message");
    const yesBtn = modal.querySelector(".confirm-yes-btn");
    const noBtn = modal.querySelector(".cancel-no-btn");

    titleEl.textContent = title;
    msgEl.textContent = message;
    yesBtn.textContent = confirmText;
    noBtn.textContent = cancelText;
    yesBtn.className = `btn confirm-yes-btn ${confirmClass}`;

    const newYesBtn = yesBtn.cloneNode(true);
    yesBtn.parentNode.replaceChild(newYesBtn, yesBtn);
    newYesBtn.addEventListener(
      "click",
      () => {
        modal.style.display = "none";
        resolve(true);
      },
      { once: true }
    );

    modal.style.display = "block";
  });
}

/**
 * Generates HTML <option> tags for a <select> dropdown.
 * @param {string[]|number[]} options - Array of option values.
 * @param {string|number} selectedValue - The value to be pre-selected.
 * @param {string} [defaultOptionText=null] - Text for a default/placeholder option.
 * @returns {string} HTML string of <option> tags.
 */
export function generateOptions(
  options,
  selectedValue,
  defaultOptionText = null
) {
  const selectedStr =
    selectedValue !== null && selectedValue !== undefined
      ? String(selectedValue)
      : "";
  let html = "";

  if (defaultOptionText) {
    if (defaultOptionText.toLowerCase().includes("all ")) {
      // For filters, "All" option has 'all' value
      html += `<option value="all" ${
        selectedStr === "all" ? "selected" : ""
      }>${defaultOptionText}</option>`;
    } else {
      // For required fields, "Select..." option is disabled
      html += `<option value="" ${
        selectedStr === "" ? "selected" : ""
      } disabled>${defaultOptionText}</option>`;
    }
  }

  const sortedOptions = [...options].sort();
  html += sortedOptions
    .map((option) => {
      const optionStr = String(option);
      const isSelected = optionStr === selectedStr;
      return `<option value="${optionStr}" ${
        isSelected ? "selected" : ""
      }>${optionStr}</option>`;
    })
    .join("");

  return html;
}
