// modal.js - Modal management utilities

// Modal element references
const verifyModal = document.getElementById("verifyModal");
const lockedModal = document.getElementById("confirmLockModal");
const forgotModal = document.getElementById("forgotPasswordModal");
const confirmResetModal = document.getElementById("confirmResetModal");

// Show a modal and optionally focus a given element
function openModal(modal, focusElement) {
  if (!modal) return;
  modal.style.display = "flex";
  if (focusElement) setTimeout(() => focusElement.focus(), 300);
}

// Hide a modal and clear specified input elements
function closeModal(modal, inputsToClear = []) {
  if (!modal) return;
  modal.style.display = "none";
  inputsToClear.forEach((input) => (input.value = ""));
}

// Show locked account modal (and focus code input)
function openLockedModal() {
  openModal(lockedModal, document.getElementById("lockedCodeInput"));
}

// Hide locked account modal and clear state/input
function closeLockedModal() {
  closeModal(lockedModal, [document.getElementById("lockedCodeInput")]);
  delete window.pendingLock;
}

// Show forgot password modal (and focus email input)
function openForgotModal() {
  openModal(forgotModal, document.getElementById("forgotEmailInput"));
}

// Hide forgot password modal and clear email input
function closeForgotModal() {
  closeModal(forgotModal, [document.getElementById("forgotEmailInput")]);
}

// Show confirm password reset modal and set reset state/email
function openConfirmResetModal(email) {
  window.pendingReset = { email };
  openModal(confirmResetModal);
}

// Hide confirm password reset modal and clear all reset inputs/state
function closeConfirmResetModal() {
  closeModal(confirmResetModal, [
    document.getElementById("resetCodeInput"),
    document.getElementById("newPasswordInput"),
    document.getElementById("confirmNewPasswordInput"),
  ]);
  delete window.pendingReset;
}

// Manages binding/unbinding event listeners for modals
class ModalEventManager {
  constructor() {
    this.cleanupFunctions = [];
  }

  // Remove all event listeners bound through this manager
  cleanup() {
    this.cleanupFunctions.forEach((fn) => fn());
    this.cleanupFunctions = [];
  }

  // Bind event and store undo function for cleanup
  bind(element, event, handler, options) {
    if (!element) return;
    element.addEventListener(event, handler, options);
    this.cleanupFunctions.push(() =>
      element.removeEventListener(event, handler, options)
    );
  }
}

// Bind verification modal events for PIN input, submission, close, keyboard and outside click
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

  // Function to submit and check PIN
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

  // Bind close button
  eventManager.bind(closeModalBtn, "click", () => {
    closeModal(verifyModal, Array.from(pins));
    eventManager.cleanup();
  });

  // PIN autofill and Enter key submission for each input
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

  // Bind verify button click
  eventManager.bind(verifyBtn, "click", runVerification);

  // Close modal when clicking outside modal element
  eventManager.bind(window, "click", (e) => {
    if (e.target === verifyModal) {
      closeModal(verifyModal, Array.from(pins));
      eventManager.cleanup();
    }
  });

  // Escape key to close modal
  eventManager.bind(document, "keydown", (e) => {
    if (e.key === "Escape" && verifyModal.style.display === "flex") {
      closeModal(verifyModal, Array.from(pins));
      eventManager.cleanup();
    }
  });

  console.log(`[${op}] Modal events bound successfully`);
}

// Initialize a modal's verification event bindings as needed
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

// Exports: modal functions and element references
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
