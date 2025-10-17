import { getConfig } from "../config.js";

// List theses with pagination and optional filters { title, year, page, pageSize }
export async function fetchTheses(params = {}) {
  const config = await getConfig();
  // Filter out empty or null parameters
  const filteredParams = Object.fromEntries(
    Object.entries(params).filter(([_, v]) => v !== null && v !== '')
  );
  const query = new URLSearchParams(filteredParams).toString();
  const endpoint = config.api.endpoints.theses + (query ? `?${query}` : "");
  const fullUrl = config.api.baseUrl + endpoint;
  const res = await fetch(fullUrl, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Fetch single thesis by ID
export async function fetchThesisById(thesisId) {
  const config = await getConfig();
  const endpoint = `${
    config.api.endpoints.theses
  }?thesis_id=${encodeURIComponent(thesisId)}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Fetch single thesis by accession number
export async function fetchThesisByAccession(accessionNo) {
  const config = await getConfig();
  const endpoint = `${
    config.api.endpoints.theses
  }?accession_no=${encodeURIComponent(accessionNo)}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Create thesis
// body: { accession_no, call_no, title, pages?, pages_note?, pub_year }
export async function createThesis(thesisData) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.theses;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(thesisData),
  });
  return res.json();
}

// Update thesis (by thesis_id)
export async function updateThesis(thesisId, thesisData) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.theses;
  const payload = { ...thesisData, thesis_id: thesisId };
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });
  return res.json();
}

// Delete thesis (by thesis_id)
export async function deleteThesis(thesisId) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.theses;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ thesis_id: thesisId }),
  });
  return res.json();
}

// Search helper: prefix title and optional year
export async function searchTheses(
  { title = "", year = "" } = {},
  { page = 1, pageSize = 20 } = {}
) {
  const config = await getConfig();
  const params = new URLSearchParams();
  if (title.trim()) params.set("title", title.trim());
  if (String(year).trim()) params.set("year", String(year).trim());
  params.set("page", String(page));
  params.set("pageSize", String(pageSize));

  const endpoint = `${config.api.endpoints.theses}?${params.toString()}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}
