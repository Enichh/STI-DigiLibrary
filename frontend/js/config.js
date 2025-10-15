let config = null;

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

export async function getConfig() {
  return await loadConfig();
}

export const configPromise = loadConfig();
