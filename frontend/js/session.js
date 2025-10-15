let sessionTimer;

export function resetSessionTimer() {
  clearTimeout(sessionTimer);
  console.log(`[session-timer] Reset at ${new Date().toLocaleTimeString()}`);

  sessionTimer = setTimeout(() => {
    console.log(
      `[session-timer] Expired at ${new Date().toLocaleTimeString()}`
    );
    sessionStorage.clear();
    window.location.href = "./login.html";
  }, 600000); // 10 minutes
}

export function initSessionTimer() {
  // Basic activity detection
  ["click", "mousemove", "keydown", "scroll"].forEach((evt) => {
    document.addEventListener(evt, resetSessionTimer, { passive: true });
  });

  console.log("[session-timer] Timer initialized on page load");
  resetSessionTimer();
}