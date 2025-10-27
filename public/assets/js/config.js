//config.js
const CONFIG_URL = "/config/frontend";

let cachedConfig = null;

export async function loadConfig() {
  if (cachedConfig) return cachedConfig;

  try {
    const response = await fetch(CONFIG_URL);
    if (!response.ok) throw new Error("Failed to load config");

    const config = await response.json();
    cachedConfig = config;
    return config;
  } catch (err) {
    console.error("Error loading frontend config:", err);
    return {
      api: { baseUrl: window.location.origin + "/api" },
      recaptcha: { siteKey: "6Ldr480rAAAAAFzjsARYcwQUgmlLcJ6SR1clGOsL" },
    };
  }
}

export function getConfig() {
  return cachedConfig ? Promise.resolve(cachedConfig) : loadConfig();
}

export const configPromise = loadConfig();
