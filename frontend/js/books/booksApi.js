import { getConfig } from "../config.js";

// Fetch all books with pagination/filters
export async function fetchBooks(params = {}) {
  const config = await getConfig();
  const query = new URLSearchParams(params).toString();
  const endpoint = config.api.endpoints.books + (query ? `?${query}` : "");
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Fetch single book by ID
export async function fetchBookById(bookId) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.books + `?id=${bookId}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Create book
export async function createBook(bookData) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.books;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(bookData),
  });
  return res.json();
}

// Update book
export async function updateBook(bookId, bookData) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.books + `?id=${bookId}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(bookData),
  });
  return res.json();
}

// Delete book
export async function deleteBook(bookId) {
  const config = await getConfig();
  const endpoint = config.api.endpoints.books + `?id=${bookId}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "DELETE",
    credentials: "include",
  });
  return res.json();
}

// Smart search: maps a single query string to ?isbn=... if it looks like ISBN, else ?title=...
export async function searchBooksSmart(q, options = {}) {
  const config = await getConfig();
  const page = options.page ?? 1;
  const pageSize = options.pageSize ?? 20;

  const looksLikeIsbn =
    typeof q === "string" && /^[0-9Xx-]{9,17}$/.test(q.trim());
  const params = new URLSearchParams({
    page: String(page),
    pageSize: String(pageSize),
  });

  if (q && q.trim() !== "") {
    if (looksLikeIsbn) {
      params.set("isbn", q.trim());
    } else {
      params.set("title", q.trim());
    }
  }

  const endpoint = `${config.api.endpoints.books}?${params.toString()}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}

// Precise search: pass any combination of title, author, isbn
export async function searchBooks(filters = {}, options = {}) {
  const config = await getConfig();
  const { title = "", author = "", isbn = "" } = filters;
  const page = options.page ?? 1;
  const pageSize = options.pageSize ?? 20;

  const params = new URLSearchParams();
  if (title.trim()) params.set("title", title.trim());
  if (author.trim()) params.set("author", author.trim());
  if (isbn.trim()) params.set("isbn", isbn.trim());
  params.set("page", String(page));
  params.set("pageSize", String(pageSize));

  const endpoint = `${config.api.endpoints.books}?${params.toString()}`;
  const res = await fetch(config.api.baseUrl + endpoint, {
    method: "GET",
    credentials: "include",
  });
  return res.json();
}
