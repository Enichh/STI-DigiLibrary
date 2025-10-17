let sessionTimer;

export function resetSessionTimer() {
  clearTimeout(sessionTimer);

  sessionTimer = setTimeout(() => {
    sessionStorage.clear();
    window.location.href = "./login.html";
  }, 600000); // 10 minutes
}

export function initSessionTimer() {
  ["click", "mousemove", "keydown", "scroll"].forEach((evt) => {
    document.addEventListener(evt, resetSessionTimer, { passive: true });
  });

  resetSessionTimer();
}
