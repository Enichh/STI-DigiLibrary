// public/assets/js/catalog/borrowFlow.js

import {
  bookCatalog,
  confirmDetails,
  finalConfirmBtn,
  receiptDetails,
  modalOverlay,
} from "./domSelectors.js";
import { fetchBookById, createLoan } from "./helpers.js";
import { addPendingLoan } from "./stateManager.js";

// Modal management utilities
function showModal(modalId) {
  modalOverlay.style.display = "block";
  document.getElementById(modalId).style.display = "block";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
  const anyOpen = Array.from(document.querySelectorAll(".modal")).some(
    (m) => m.style.display === "block"
  );
  if (!anyOpen) modalOverlay.style.display = "none";
}

export function setupBorrowFlow() {
  // Step 1: Show confirmation modal when "Borrow" clicked
  bookCatalog.addEventListener("click", async (e) => {
    const btn = e.target.closest(".borrow-btn");
    if (btn && !btn.disabled) {
      const bookId = parseInt(btn.dataset.bookId);
      try {
        const book = await fetchBookById(bookId);
        const user = window.userData;

        confirmDetails.innerHTML = `
          <p>You are about to send a borrow request for the following item:</p>
          <p><strong>Title:</strong> ${book.title}</p>
          <p><strong>Borrower:</strong> ${user.userName}</p>
          <hr>
          <p><small><strong>Rules:</strong> Please pick up the book from the librarian's desk within this day during library hours (9:00AM-4:00PM). Failure to do so will result in cancellation.</small></p>
        `;
        finalConfirmBtn.dataset.bookId = bookId;
        showModal("confirm-borrow-modal");
      } catch (error) {
        alert("Unable to open confirmation modal: " + error.message);
      }
    }
  });

  // Step 2: Handle confirm, create loan, show receipt
  finalConfirmBtn.addEventListener("click", async (e) => {
    const bookId = parseInt(e.target.dataset.bookId);
    const user = window.userData;
    try {
      // Get latest book so we can grab copyId
      const book = await fetchBookById(bookId);

      // Critical: Ensure you have the correct copyId
      const copyId =
        book.copy_id ||
        (book.copies && book.copies.length > 0 ? book.copies[0].id : null);

      if (!copyId) throw new Error("No available copy for this book.");

      // Build the borrow date and due date (e.g., 7-day loan)
      const borrowedDate = new Date()
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");
      const due = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
      const dueDate = due.toISOString().slice(0, 10);
      const remarks = null; // optionally supply a user/admin comment

      const result = await createLoan(
        user.userId,
        copyId,
        borrowedDate,
        dueDate,
        remarks
      );

      if (result.loan) addPendingLoan(result.loan);

      closeModal("confirm-borrow-modal");
      receiptDetails.innerHTML = `
        <p>You may present this borrow receipt to the librarian.</p>
        <p><strong>Borrower:</strong> ${user.userName}</p>
        <p><strong>Library ID:</strong> ${user.studentId}</p>
        <p><strong>Book Title:</strong> ${book.title}</p>
        <hr>
        <p><strong>Important:</strong> Your request has been sent for approval. You will be notified once it is ready for pickup. You can cancel this request under "Pending Borrow Requests".</p>
      `;
      showModal("receipt-modal");
    } catch (error) {
      closeModal("confirm-borrow-modal");
      alert("Failed to process loan: " + error.message);
    }
  });

  // Step 3: Universal modal close logic
  Array.from(
    document.querySelectorAll(".modal-close, .modal-action-close")
  ).forEach((btn) => {
    btn.addEventListener("click", function () {
      const modalId = btn.dataset.modalId || btn.closest(".modal").id;
      closeModal(modalId);
    });
  });
}
