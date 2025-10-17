/**
 * @file Modal management utilities
 */

const verifyModal = document.getElementById("verifyModal");
const lockedModal = document.getElementById("confirmLockModal");
const forgotModal = document.getElementById("forgotPasswordModal");
const confirmResetModal = document.getElementById("confirmResetModal");

/**
 * Shows a modal and optionally focuses a given element.
 * @param {HTMLElement} modal - The modal element to show.
 * @param {HTMLElement} [focusElement] - The element to focus.
 */
function openModal(modal, focusElement) {
  if (!modal) return;
  modal.style.display = "flex";
  if (focusElement) setTimeout(() => focusElement.focus(), 300);
}

/**
 * Hides a modal and clears specified input elements.
 * @param {HTMLElement} modal - The modal element to hide.
 * @param {HTMLElement[]} [inputsToClear=[]] - An array of input elements to clear.
 */
function closeModal(modal, inputsToClear = []) {
  if (!modal) return;
  modal.style.display = "none";
  inputsToClear.forEach((input) => (input.value = ""));
}

/**
 * Shows the locked account modal and focuses the code input.
 */
function openLockedModal() {
  openModal(lockedModal, document.getElementById("lockedCodeInput"));
}

/**
 * Hides the locked account modal and clears its state and input.
 */
function closeLockedModal() {
  closeModal(lockedModal, [document.getElementById("lockedCodeInput")]);
  delete window.pendingLock;
}

/**
 * Shows the forgot password modal and focuses the email input.
 */
function openForgotModal() {
  openModal(forgotModal, document.getElementById("forgotEmailInput"));
}

/**
 * Hides the forgot password modal and clears the email input.
 */
function closeForgotModal() {
  closeModal(forgotModal, [document.getElementById("forgotEmailInput")]);
}

/**
 * Shows the confirm password reset modal and sets the reset state and email.
 * @param {string} email - The user's email address.
 */
function openConfirmResetModal(email) {
  window.pendingReset = { email };
  openModal(confirmResetModal);
}

/**
 * Hides the confirm password reset modal and clears all reset inputs and state.
 */
function closeConfirmResetModal() {
  closeModal(confirmResetModal, [
    document.getElementById("resetCodeInput"),
    document.getElementById("newPasswordInput"),
    document.getElementById("confirmNewPasswordInput"),
  ]);
  delete window.pendingReset;
}

/**
 * Manages binding and unbinding event listeners for modals.
 */
class ModalEventManager {
  constructor() {
    this.cleanupFunctions = [];
  }

  /**
   * Removes all event listeners bound through this manager.
   */
  cleanup() {
    this.cleanupFunctions.forEach((fn) => fn());
    this.cleanupFunctions = [];
  }

  /**
   * Binds an event and stores the undo function for cleanup.
   * @param {HTMLElement} element - The element to bind the event to.
   * @param {string} event - The name of the event.
   * @param {function} handler - The event handler.
   * @param {object} [options] - Additional options for the event listener.
   */
  bind(element, event, handler, options) {
    if (!element) return;
    element.addEventListener(event, handler, options);
    this.cleanupFunctions.push(() =>
      element.removeEventListener(event, handler, options)
    );
  }
}

/**
 * Binds verification modal events for PIN input, submission, close, keyboard, and outside click.
 * @param {string} roleFromLogin - The role of the user logging in.
 * @param {function} verifyHandlerFn - The function to handle verification.
 */
function bindVerifyModalEvents(roleFromLogin, verifyHandlerFn) {
  const op = `verify-${Date.now()}`;
  console.log(`[${op}] Binding modal events with role: ${roleFromLogin}`);

  const verifyBtn = document.getElementById("verifyBtn");
  const closeModalBtn = document.getElementById("verifyCloseBtn");
  const pins = document.querySelectorAll(".pin");

  if (!verifyBtn || !closeModalBtn || pins.length === 0) {
    console.warn(`[${op}] Missing modal elements`);
    return;
  }

  const eventManager = new ModalEventManager();

  const runVerification = async () => {
    const code = Array.from(pins)
      .map((p) => p.value)
      .join("");
    if (code.length !== 6) {
      alert("Please enter the 6-digit code.");
      return;
    }

    verifyBtn.disabled = true;

    let success = false;
    try {
      if (typeof verifyHandlerFn === "function") {
        success = await verifyHandlerFn(code, roleFromLogin, op);
      }
    } catch (err) {
      console.error(`[${op}] Verification error:`, err);
      alert("Verification failed. Please try again.");
    }

    if (success) {
      closeModal(verifyModal, Array.from(pins));
      eventManager.cleanup();
    }

    verifyBtn.disabled = false;
  };

  eventManager.bind(closeModalBtn, "click", () => {
    closeModal(verifyModal, Array.from(pins));
    eventManager.cleanup();
  });

  pins.forEach((pin, idx) => {
    eventManager.bind(pin, "input", () => {
      if (pin.value && idx < pins.length - 1) {
        pins[idx + 1].focus();
      }
    });

    eventManager.bind(pin, "keydown", async (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        await runVerification();
      }
    });
  });

  eventManager.bind(verifyBtn, "click", runVerification);

  eventManager.bind(window, "click", (e) => {
    if (e.target === verifyModal) {
      closeModal(verifyModal, Array.from(pins));
      eventManager.cleanup();
    }
  });

  eventManager.bind(document, "keydown", (e) => {
    if (e.key === "Escape" && verifyModal.style.display === "flex") {
      closeModal(verifyModal, Array.from(pins));
      eventManager.cleanup();
    }
  });

  console.log(`[${op}] Modal events bound successfully`);
}

/**
 * Initializes a modal's verification event bindings as needed.
 * @param {string} roleFromLogin - The role of the user logging in.
 * @param {function} verifyHandlerFn - The function to handle verification.
 * @returns {function} A cleanup function.
 */
function initModal(roleFromLogin, verifyHandlerFn) {
  const modalInitializers = [];

  if (verifyHandlerFn) {
    modalInitializers.push({
      name: "verifyModal",
      init: () => bindVerifyModalEvents(roleFromLogin, verifyHandlerFn),
    });
  }

  modalInitializers.forEach(({ name, init }) => {
    try {
      init();
      console.log(`[Modal] ${name} initialized successfully`);
    } catch (error) {
      console.error(`[Modal] Failed to initialize ${name}:`, error);
    }
  });

  return () => {
    console.log("[Modal] Cleaning up modal event listeners");
  };
}

export {
  openModal,
  closeModal,
  openLockedModal,
  closeLockedModal,
  openForgotModal,
  closeForgotModal,
  openConfirmResetModal,
  closeConfirmResetModal,
  bindVerifyModalEvents,
  initModal,
  verifyModal,
  lockedModal,
  forgotModal,
  confirmResetModal,
};
