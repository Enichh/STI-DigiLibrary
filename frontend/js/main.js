// main.js
import { initUI } from "./ui.js";
import { attachAuthHandlers, handleVerification } from "./authHandler.js";
import { initModal } from "./modal.js";

document.addEventListener("DOMContentLoaded", () => {
  initUI();
  attachAuthHandlers();
  initModal();
});
