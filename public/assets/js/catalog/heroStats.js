// public/assets/js/catalog/heroStats.js

import { getTotalResources, getAvailableBooks } from "./stateManager.js";
import { totalResourcesEl, availableBooksEl } from "./domSelectors.js";

// Updates the hero statistics display in the DOM
export function updateHeroStats() {
  const totalResources = getTotalResources();
  const availableBooks = getAvailableBooks();

  if (totalResourcesEl)
    totalResourcesEl.textContent = totalResources.toLocaleString();

  if (availableBooksEl)
    availableBooksEl.textContent = availableBooks.toLocaleString();
}
