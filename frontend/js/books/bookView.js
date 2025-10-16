export function renderBooks(books, containerId = "book-list") {
  const container = document.getElementById(containerId);
  container.innerHTML = "";
  if (!books || books.length === 0) {
    container.innerHTML = "<p>No books found.</p>";
    return;
  }

  books.forEach((book) => {
    const coverSrc = book.has_cover
      ? `/STI-DigiLibrary/covers/${book.isbn}.webp`
      : "/STI-DigiLibrary/frontend/assets/owlie_icn_transparent.png";

    const article = document.createElement("article");
    article.className = "book";
    article.innerHTML = `
      <div class="book-image">
        <img src="${coverSrc}" alt="Book Cover"
          onerror="this.onerror=null;this.src='/STI-DigiLibrary/frontend/assets/owlie_icn_transparent.png';" />
      </div>
      <h3>${book.title || ""}</h3>
      <p>Author: ${book.author || "Unknown"}</p>
      <p>ISBN: ${book.isbn || ""}</p>
      <p>Status: ${book.status || "Available"}</p>
      <button>Borrow</button>
    `;
    container.appendChild(article);
  });
}
