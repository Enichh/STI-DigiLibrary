<?php
// server/models/UserModel.php

require_once __DIR__ . '/../config/database.php';

// Model for all user-related database operations
class UserModel
{
    private $pdo;

    // Initialize PDO database connection
    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function findByUserName(string $userName): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM TBL_USERS WHERE userName = ?");
        $stmt->execute([$userName]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM TBL_USERS WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function createUser(string $userName, string $email, string $hashedPassword, int $roleId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO TBL_USERS (userName, email, password_hash, role_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$userName, $email, $hashedPassword, $roleId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE TBL_USERS SET password_hash = ?, updated_at = NOW() WHERE user_id = ?"
        );
        $stmt->execute([$hashedPassword, $userId]);
    }

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

    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE TBL_USERS SET failedAttempts = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function unlockAccount(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE TBL_USERS SET accountStatus = 'Active', failedAttempts = 0 WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    public function getRoleIdByName(string $roleName): ?int
    {
        $stmt = $this->pdo->prepare("SELECT role_id FROM TBL_ROLES WHERE role_name = ?");
        $stmt->execute([$roleName]);
        $row = $stmt->fetch();
        return $row ? (int)$row['role_id'] : null;
    }

    public function getRoleNameById(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT role_name FROM TBL_ROLES WHERE role_id = ?");
        $stmt->execute([$roleId]);
        $row = $stmt->fetch();
        return $row ? $row['role_name'] : null;
    }

    public function checkAdminCode(string $code): bool
    {
        $stmt = $this->pdo->prepare("SELECT code FROM TBL_ADMIN_CODES WHERE code = ?");
        $stmt->execute([$code]);
        return (bool)$stmt->fetch();
    }

    public function consumeAdminCode(string $code): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM TBL_ADMIN_CODES WHERE code = ?");
        $stmt->execute([$code]);
    }

    public function issueAdminCode(string $code): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO TBL_ADMIN_CODES (code, created_at) VALUES (?, NOW())");
        $stmt->execute([$code]);
    }

    public function clearAdminCodes(): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM TBL_ADMIN_CODES");
        $stmt->execute();
    }

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

    public function countUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM TBL_USERS");
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }
}
