/**
 * @file Main entry point for the application's JavaScript.
 *
 * This file initializes the UI, attaches authentication handlers, and sets up modals
 * once the DOM is fully loaded.
 */
import { initUI } from "./ui.js";
import { attachAuthHandlers, handleVerification } from "./authHandler.js";
import { initModal } from "./modal.js";

document.addEventListener("DOMContentLoaded", () => {
  initUI();
  attachAuthHandlers();
  initModal();
});
