import {
  fetchTotalUniqueBooks,
  fetchTotalCopies,
  fetchCheckedOutCount,
  fetchAvailableCopies,
  fetchUpcomingDueDates,
  fetchAdminActionItems,
  fetchBorrowerCount,
  fetchLibraryIdCount,
  fetchTotalTheses,
} from "./apiOverview.js";
import { renderHTML, formatDate } from "./helpers.js";

function getDaysRemaining(dueDate) {
  const today = new Date();
  const due = new Date(dueDate);
  today.setHours(0, 0, 0, 0);
  due.setHours(0, 0, 0, 0);

  const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return `<span class="due-today">Due Today</span>`;
  if (diffDays < 0)
    return `<span class="due-overdue">${Math.abs(diffDays)}d Overdue</span>`;
  return `<span class="due-remaining">${diffDays}d Remaining</span>`;
}

export async function renderOverview() {
  const container = document.getElementById("overview");
  if (!container) return;

  container.innerHTML = `<div class="overview-container"><p>Loading...</p></div>`;

  try {
    const [
      totalBooks,
      totalCopies,
      checkedOut,
      available,
      upcomingDue,
      actionItems,
      borrowerCount,
      libraryIdCount,
      totalTheses,
    ] = await Promise.all([
      fetchTotalUniqueBooks(),
      fetchTotalCopies(),
      fetchCheckedOutCount(),
      fetchAvailableCopies(),
      fetchUpcomingDueDates(3),
      fetchAdminActionItems(),
      fetchBorrowerCount(),
      fetchLibraryIdCount(),
      fetchTotalTheses(),
    ]);

    const returnsHtml = upcomingDue.length
      ? upcomingDue
          .map(
            (item) => `
        <li class="due-item">
          <div class="due-item-info">
            <strong>${item.userName}</strong>
            <em>${item.bookTitle}</em>
          </div>
          <div class="due-item-status">
            <strong>${getDaysRemaining(item.due_date)}</strong>
            <small>${formatDate(item.due_date)}</small>
          </div>
        </li>`
          )
          .join("")
      : `<li class="empty-state-text">No books currently checked out.</li>`;

    const seeAllHtml =
      checkedOut > 3
        ? `<div class="see-all-container">
           <a href="#borrow-management" data-section="borrow-management" class="see-all-link">
             See all ${checkedOut} checked out items →
           </a>
         </div>`
        : "";
    let notificationsHtml = "";
    if (actionItems.pendingApplications > 0) {
      notificationsHtml += `<a href="#digital-ids" data-section="digital-ids" class="admin-action-item">
        <i class="fas fa-id-card"></i>
        <div><strong>New ID Applications:</strong> ${actionItems.pendingApplications} pending review.</div>
      </a>`;
    }
    if (actionItems.pendingRenewals > 0) {
      notificationsHtml += `<a href="#digital-ids" data-section="digital-ids" class="admin-action-item">
        <i class="fas fa-id-card"></i>
        <div><strong>ID Renewals:</strong> ${actionItems.pendingRenewals} pending review.</div>
      </a>`;
    }
    if (actionItems.pendingBorrows > 0) {
      notificationsHtml += `<a href="#borrow-management" data-section="borrow-management" class="admin-action-item">
        <i class="fa-solid fa-hands-holding"></i>
        <div><strong>Book Requests:</strong> ${actionItems.pendingBorrows} pending approval.</div>
      </a>`;
    }
    if (actionItems.overdueBooks > 0) {
      notificationsHtml += `<a href="#fines" data-section="fines" class="admin-action-item is-danger">
        <i class="fa-solid fa-peso-sign"></i>
        <div><strong>Overdue Alert:</strong> ${actionItems.overdueBooks} book${
        actionItems.overdueBooks > 1 ? "s" : ""
      } currently overdue.</div>
      </a>`;
    }
    if (!notificationsHtml) {
      notificationsHtml = `<p class="empty-state-text">No pending admin actions.</p>`;
    }

    container.innerHTML = `
      <style>
        /* Scoped styles for Overview */
        .overview-container .content-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-top: 30px; }
        .overview-container .activity-list { list-style: none; padding: 0; }
        .overview-container .empty-state-text { padding: 12px 0; color: #777; font-style: italic; }
        .overview-container .due-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--lightgray); }
        .overview-container .due-item:last-child { border-bottom: none; }
        .overview-container .due-item-info strong { display: block; color: var(--darkblue); }
        .overview-container .due-item-info em { font-size: 0.9rem; color: #555; }
        .overview-container .due-item-status { text-align: right; flex-shrink: 0; margin-left: 15px; }
        .overview-container .due-today, .overview-container .due-overdue { color: var(--danger); font-weight: bold; }
        .overview-container .due-remaining { color: var(--success); font-weight: 500; }
        .overview-container .see-all-container { text-align: right; margin-top: 15px; border-top: 1px solid var(--lightgray); padding-top: 15px; }
        .overview-container .admin-action-item { display: flex; align-items: center; margin-bottom: 10px; padding: 12px 15px; border: 1px solid var(--lightgray); border-radius: var(--border-radius); background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: var(--blue); text-decoration: none; transition: all 0.2s ease; }
        .overview-container .admin-action-item.is-danger { color: var(--danger); border-color: var(--danger); }
        @media (max-width: 992px) { .overview-container .content-grid { grid-template-columns: 1fr; } .overview-container .stats-grid { grid-template-columns: 1fr 1fr; gap: 15px; } }
        @media (max-width: 400px) { .overview-container .stats-grid { grid-template-columns: 1fr; } }
      </style>

      <div class="overview-container">
        <p class="dashboard-welcome-text">Welcome back, Admin! Here's your library snapshot.</p>
        <div class="stats-grid">
          <div class="stat-card"><div class="icon"><i class="fas fa-users"></i></div><div class="info"><h3>${borrowerCount}</h3><p>Total Users</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-book-open"></i></div><div class="info"><h3>${
            totalBooks + totalTheses
          }</h3><p>Unique Titles</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-layer-group"></i></div><div class="info"><h3>${totalCopies}</h3><p>Total Copies</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-book-reader"></i></div><div class="info"><h3>${checkedOut}</h3><p>Checked Out</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-check-circle"></i></div><div class="info"><h3>${available}</h3><p>Available</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fas fa-book"></i></div><div class="info"><h3>${totalBooks}</h3><p>Total Books</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-id-card"></i></div><div class="info"><h3>${libraryIdCount}</h3><p>Active Library IDs</p></div></div>
          <div class="stat-card"><div class="icon"><i class="fas fa-file-alt"></i></div><div class="info"><h3>${totalTheses}</h3><p>Total Theses</p></div></div>  
        </div>

        <div class="content-grid">
          <div class="card">
            <h3>Upcoming Due Dates</h3>
            <hr class="dashboard-separator">
            <ul class="activity-list" id="upcoming-returns-list">${returnsHtml}</ul>
            ${seeAllHtml}
          </div>
          <div class="card">
            <h3>Admin Action Items</h3>
            <hr class="dashboard-separator">
            <div id="admin-notifications-list">${notificationsHtml}</div>
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    console.error("Overview render failed:", err);
    container.innerHTML = `<p class="error">Failed to load dashboard data.</p>`;
  }
}
