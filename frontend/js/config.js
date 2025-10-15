let config = null;

async function loadConfig() {
  if (config) return config;

  const response = await fetch(
    "http://localhost/STI-DigiLibrary/server/public/config/frontend"
  );
  if (!response.ok) {
    throw new Error("Failed to load configuration");
  }
  config = await response.json();
  return config;
}

export async function getConfig() {
  return await loadConfig();
}

export const configPromise = loadConfig();
