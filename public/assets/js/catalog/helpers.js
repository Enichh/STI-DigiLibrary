import { configPromise } from "../config.js";

function buildQuery(params = {}) {
  const query = Object.entries(params)
    .filter(
      ([_, value]) => value !== undefined && value !== null && value !== ""
    )
    .map(
      ([key, value]) =>
        `${encodeURIComponent(key)}=${encodeURIComponent(value)}`
    )
    .join("&");
  return query ? `?${query}` : "";
}

async function getCatalogBaseUrl() {
  try {
    const config = await configPromise;
    const baseUrl = `${config.api.baseUrl.replace(/\/$/, "")}${
      config.api.endpoints?.catalog || "/catalog"
    }`;
    return baseUrl;
  } catch (err) {
    throw new Error("Failed to resolve catalog base URL due to config error.");
  }
}

export async function fetchCatalogStats() {
  try {
    const BASE_URL = await getCatalogBaseUrl();
    const url = `${BASE_URL}/stats`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success)
      return {
        totalResources: data.totalResources,
        availableBooks: data.availableBooks,
      };
    throw new Error(data.error || "Failed to fetch stats");
  } catch (err) {
    throw err;
  }
}

export async function fetchCatalogItems(params = {}) {
  try {
    const BASE_URL = await getCatalogBaseUrl();
    const query = buildQuery(params);
    const url = `${BASE_URL}/items${query}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
      const transformedData = data.data.map((item) =>
        transformBackendItemToFrontend(item, params.type)
      );
      return {
        data: transformedData,
        pagination: data.pagination,
        total: data.total,
      };
    }
    throw new Error(data.error || "Failed to fetch catalog items");
  } catch (err) {
    throw err;
  }
}

export async function fetchTags(type) {
  try {
    if (type === "thesis") return [];
    const BASE_URL = await getCatalogBaseUrl();
    const query = buildQuery(type ? { type } : {});
    const url = `${BASE_URL}/tags${query}`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) return data.tags;
    throw new Error(data.error || "Failed to fetch tags");
  } catch (err) {
    throw err;
  }
}

export function formatDate(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

export function transformBackendItemToFrontend(item, type = "book") {
  return {
    id: item.book_id || item.thesis_id || item.id || null,
    title: item.title || "",
    author: item.author || item.authors || "Unknown",
    year: item.year || item.publication_year || undefined,
    desc: item.desc || item.description || "",
    tags:
      type === "thesis"
        ? []
        : Array.isArray(item.tags)
        ? item.tags
        : Array.isArray(item.genres)
        ? item.genres
        : (item.tags || item.genres || "")
            .toString()
            .split(",")
            .map((tag) => tag.trim())
            .filter((tag) => tag.length > 0) || [],
    cover_image:
      item.cover || item.cover_image || "/assets/images/nocover.png",
    available: item.available || item.available_copies || 0,
    copies: item.copies || item.total_copies || 0,
    type: type,
  };
}
