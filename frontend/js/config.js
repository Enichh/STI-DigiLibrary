let config = null;

/**
 * Loads the frontend configuration from the server.
 * @returns {Promise<object>} The configuration object.
 * @throws {Error} If the configuration fails to load.
 */
async function loadConfig() {
  if (config) return config;

  try {
    const response = await fetch(
      "/STI-DigiLibrary/server/public/config/frontend.php"
    );
    if (!response.ok) {
      throw new Error("Failed to load configuration");
    }
    config = await response.json();
    return config;
  } catch (error) {
    console.error("Error loading config:", error);
    throw error;
  }
}

/**
 * Gets the frontend configuration.
 * @returns {Promise<object>} The configuration object.
 */
export async function getConfig() {
  return await loadConfig();
}

/**
 * A promise that resolves with the frontend configuration.
 * @type {Promise<object>}
 */
export const configPromise = loadConfig();
