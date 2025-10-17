<?php
// server/models/UserModel.php

require_once __DIR__ . '/../config/database.php';

/**
 * Model for all user-related database operations.
 *
 * This class provides methods for creating, finding, and managing user accounts,
 * as well as handling roles and administrative codes.
 */
class UserModel
{
    private $pdo;

    /**
     * Initializes the PDO database connection.
     */
    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Finds a user by their username.
     *
     * @param string $userName The username to search for.
     * @return array|null The user record, or null if not found.
     */
    public function findByUserName(string $userName): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM TBL_USERS WHERE userName = ?");
        $stmt->execute([$userName]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address to search for.
     * @return array|null The user record, or null if not found.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM TBL_USERS WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Creates a new user record.
     *
     * @param string $userName The username.
     * @param string $email The email address.
     * @param string $hashedPassword The hashed password.
     * @param int $roleId The ID of the user's role.
     * @return int The ID of the newly created user.
     */
    public function createUser(string $userName, string $email, string $hashedPassword, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO TBL_USERS (userName, email, password_hash, role_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$userName, $email, $hashedPassword, $roleId]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Updates a user's password.
     *
     * @param int $userId The ID of the user.
     * @param string $hashedPassword The new hashed password.
     * @return void
     */
    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE TBL_USERS SET password_hash = ?, updated_at = NOW() WHERE user_id = ?"
        );
        $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Updates the number of failed login attempts for a user and optionally locks the account.
     *
     * @param int $userId The ID of the user.
     * @param int $attempts The new number of failed attempts.
     * @param bool $lock Whether to lock the account.
     * @return void
     */
    public function updateFailedAttempts(int $userId, int $attempts, bool $lock = false): void
    {
        if ($lock) {
            $stmt = $this->pdo->prepare(
                "UPDATE TBL_USERS 
                 SET failedAttempts = ?, accountStatus = 'Locked', locked_at = NOW() 
                 WHERE user_id = ?"
            );
        } else {
            $stmt = $this->pdo->prepare("UPDATE TBL_USERS SET failedAttempts = ? WHERE user_id = ?");
        }
        $stmt->execute([$attempts, $userId]);
    }

    /**
     * Resets the failed login attempts for a user.
     *
     * @param int $userId The ID of the user.
     * @return void
     */
    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE TBL_USERS SET failedAttempts = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Unlocks a user's account.
     *
     * @param int $userId The ID of the user.
     * @return void
     */
    public function unlockAccount(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE TBL_USERS SET accountStatus = 'Active', failedAttempts = 0 WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    /**
     * Gets the ID of a role by its name.
     *
     * @param string $roleName The name of the role.
     * @return int|null The role ID, or null if not found.
     */
    public function getRoleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare("SELECT role_id FROM TBL_ROLES WHERE role_name = ?");
        $stmt->execute([$roleName]);
        $row = $stmt->fetch();
        return $row ? (int)$row['role_id'] : null;
    }

    /**
     * Gets the name of a role by its ID.
     *
     * @param int $roleId The ID of the role.
     * @return string|null The role name, or null if not found.
     */
    public function getRoleNameById(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT role_name FROM TBL_ROLES WHERE role_id = ?");
        $stmt->execute([$roleId]);
        $row = $stmt->fetch();
        return $row ? $row['role_name'] : null;
    }

    /**
     * Checks if an admin code is valid.
     *
     * @param string $code The admin code to check.
     * @return bool True if the code is valid, false otherwise.
     */
    public function checkAdminCode(string $code): bool
    {
        $stmt = $this->pdo->prepare("SELECT code FROM TBL_ADMIN_CODES WHERE code = ?");
        $stmt->execute([$code]);
        return (bool)$stmt->fetch();
    }

    /**
     * Consumes an admin code after it has been used.
     *
     * @param string $code The admin code to consume.
     * @return void
     */
    public function consumeAdminCode(string $code): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM TBL_ADMIN_CODES WHERE code = ?");
        $stmt->execute([$code]);
    }

    /**
     * Issues a new admin code.
     *
     * @param string $code The admin code to issue.
     * @return void
     */
    public function issueAdminCode(string $code): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO TBL_ADMIN_CODES (code, created_at) VALUES (?, NOW())");
        $stmt->execute([$code]);
    }

    /**
     * Clears all admin codes from the database.
     *
     * @return void
     */
    public function clearAdminCodes(): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM TBL_ADMIN_CODES");
        $stmt->execute();
    }

    /**
     * Gets a paginated list of users.
     *
     * @param int $limit The maximum number of users to return.
     * @param int $offset The starting offset for pagination.
     * @return array An array of user records.
     */
    public function getPaginatedUsers(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, userName, email, role
         FROM TBL_USERS
         ORDER BY userName
         LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Counts the total number of users.
     *
     * @return int The total number of users.
     */
    public function countUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM TBL_USERS");
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }
}
