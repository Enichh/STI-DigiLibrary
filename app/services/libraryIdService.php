<?php
// libraryIdService.php

class LibraryIdService
{
    private $libraryIdModel;

    // Dependency Injection of the model
    public function __construct(LibraryIdModel $libraryIdModel)
    {
        $this->libraryIdModel = $libraryIdModel;
    }

    // User requests a new digital/library ID (status: pending)
    public function requestLibraryId($userId, $remarks = null)
    {
        // Check if user already has an active or pending ID (business rule)
        $existing = $this->libraryIdModel->getLibraryIdsForUser($userId);
        foreach ($existing as $row) {
            if (in_array($row['status'], ['active', 'pending'])) {
                throw new Exception("User already has an active or pending library ID.");
            }
        }

        return $this->libraryIdModel->createLibraryIdRequest($userId, $remarks);
    }

    // Admin approves a pending library ID (sets status, dates, optionally initiates number assignment)
    public function approveLibraryId($libraryIdId, $adminUserId)
    {
        // Mark as active (or use triggers for number assignment)
        $this->libraryIdModel->updateLibraryIdStatus($libraryIdId, 'active');

        // Set issue date to current year-month, expiry to same month next year (e.g., 2023-12 -> 2024-12)
        $issueYearMonth = date('Y-m');
        $expiryYearMonth = date('Y-m', strtotime('+1 year'));
        return $this->libraryIdModel->setLibraryIdDates($libraryIdId, $issueYearMonth, $expiryYearMonth);
    }

    // Admin can suspend, revoke, or expire an ID
    public function updateStatus($libraryIdId, $status)
    {
        // Optionally validate status values here
        return $this->libraryIdModel->updateLibraryIdStatus($libraryIdId, $status);
    }

    // User or admin views library IDs (by user or by status)
    public function getUserLibraryId($userId)
    {
        // Return active (current) ID for user's card display
        $ids = $this->libraryIdModel->getLibraryIdsForUser($userId, true);
        return count($ids) ? $ids[0] : null;
    }

    // Get the most recent library ID regardless of status (including pending)
    public function getLatestLibraryId($userId)
    {
        // Return the most recent record regardless of status
        $ids = $this->libraryIdModel->getLibraryIdsForUser($userId, false);
        return count($ids) ? $ids[0] : null;
    }

    public function getLibraryIdById($libraryIdId)
    {
        return $this->libraryIdModel->getLibraryIdById($libraryIdId);
    }

    public function listLibraryIds($status = null)
    {
        return $this->libraryIdModel->listLibraryIds($status);
    }

    public function getLibraryIdCount()
    {
        return $this->libraryIdModel->getLibraryIdCount();
    }

    // Optionally: delete, cleanup (admin)
    public function deleteLibraryId($libraryIdId)
    {
        return $this->libraryIdModel->deleteLibraryId($libraryIdId);
    }
}
