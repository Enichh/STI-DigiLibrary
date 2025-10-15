import { getConfig } from "../config.js";

// Fetch all books, optionally with filters
export async function fetchBooks(params = {}) {
  const config = await getConfig();
  const query = new URLSearchParams(params).toString();
  const endpoint = config.api.endpoints.books + (query ? `?${query}` : "");
  console.log("books endpoint:", config.api.endpoints.books);
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
