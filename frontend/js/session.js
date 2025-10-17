/**
 * @file Manages session timeout due to user inactivity.
 */

let sessionTimer;

/**
 * Resets the session timer. If the timer expires, it clears the session storage
 * and redirects the user to the login page.
 */
export function resetSessionTimer() {
  clearTimeout(sessionTimer);

  sessionTimer = setTimeout(() => {
    sessionStorage.clear();
    window.location.href = "./login.html";
  }, 600000); // 10 minutes
}

/**
 * Initializes the session timer by adding event listeners for user activity.
 */
export function initSessionTimer() {
  ["click", "mousemove", "keydown", "scroll"].forEach((evt) => {
    document.addEventListener(evt, resetSessionTimer, { passive: true });
  });

  resetSessionTimer();
}
