<?php
// server/models/booksModel.php

require_once __DIR__ . '/../config/database.php';

class BooksModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // Fetch all books
    public function fetchAllBooks($filters = []): array
    {
        $sql = "
            SELECT b.*,
              GROUP_CONCAT(
                TRIM(
                  CONCAT(
                    IF(a.first_name IS NULL OR a.first_name = 'No First Name', '', CONCAT(a.first_name, ' ')),
                    IF(a.middle_name IS NULL OR a.middle_name = 'No Middle Name', '', CONCAT(a.middle_name, ' ')),
                    IF(a.last_name IS NULL OR a.last_name = 'No Last Name', '', a.last_name)
                  )
                )
                SEPARATOR ', '
              ) AS author
            FROM tbl_books b
            LEFT JOIN tbl_book_authors ba ON ba.book_id = b.book_id
            LEFT JOIN tbl_authors a ON a.author_id = ba.author_id
            GROUP BY b.book_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    // Fetch a book by ID
    public function fetchBookById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM tbl_books WHERE book_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Insert a new book
    public function insertBook(array $data): int
    {
        $sql = "INSERT INTO tbl_books 
            (isbn, isbn13, title, subtitle, edition, volume, publication_year, publisher_id, pages, language, description, cover_image, created_at, updated_at)
            VALUES 
            (:isbn, :isbn13, :title, :subtitle, :edition, :volume, :publication_year, :publisher_id, :pages, :language, :description, :cover_image, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':isbn' => $data['isbn'] ?? null,
            ':isbn13' => $data['isbn13'] ?? null,
            ':title' => $data['title'] ?? null,
            ':subtitle' => $data['subtitle'] ?? null,
            ':edition' => $data['edition'] ?? null,
            ':volume' => $data['volume'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':publisher_id' => $data['publisher_id'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':language' => $data['language'] ?? null,
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // Update a book
    public function updateBook(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_books SET
            isbn = :isbn,
            isbn13 = :isbn13,
            title = :title,
            subtitle = :subtitle,
            edition = :edition,
            volume = :volume,
            publication_year = :publication_year,
            publisher_id = :publisher_id,
            pages = :pages,
            language = :language,
            description = :description,
            cover_image = :cover_image,
            updated_at = NOW()
            WHERE book_id = :book_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':isbn' => $data['isbn'] ?? null,
            ':isbn13' => $data['isbn13'] ?? null,
            ':title' => $data['title'] ?? null,
            ':subtitle' => $data['subtitle'] ?? null,
            ':edition' => $data['edition'] ?? null,
            ':volume' => $data['volume'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':publisher_id' => $data['publisher_id'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':language' => $data['language'] ?? null,
            ':description' => $data['description'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':book_id' => $id,
        ]);
    }

    // Delete a book
    public function deleteBook(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM tbl_books WHERE book_id = ?");
        return $stmt->execute([$id]);
    }
}
