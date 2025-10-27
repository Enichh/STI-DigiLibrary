<?php
// app/models/userModel.php
require_once __DIR__ . '/../../config/database.php';

/**
 * Model for all user-related database operations.
 */
class UserModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    // ==================================================
    // BASIC FINDERS
    // ==================================================

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

    // ==================================================
    // USER CREATION
    // ==================================================

    public function createUser(
        string $userName,
        string $email,
        string $hashedPassword,
        int $roleId,
        string $firstName,
        ?string $middleName = null,
        string $lastName,
        ?string $suffix = null,
        ?string $studentNumber = null
    ): int {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO TBL_USERS 
                (userName, email, password_hash, role_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$userName, $email, $hashedPassword, $roleId]);
            $userId = (int)$this->pdo->lastInsertId();

            if ($roleId === 1) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO TBL_STUDENTDETAILS 
                    (user_id, firstName, middleName, lastName, studentNumber, library_id, program_id, suffix)
                    VALUES (?, ?, ?, ?, ?, NULL, NULL, ?)
                ");
                $stmt->execute([
                    $userId,
                    $firstName,
                    $middleName,
                    $lastName,
                    $studentNumber,
                    $suffix
                ]);
            }
            return $userId;
        } catch (Exception $e) {
            error_log("Failed to create user: " . $e->getMessage());
            throw $e;
        }
    }

    // ==================================================
    // USER MANAGEMENT
    // ==================================================

    public function updatePassword(int $userId, string $hashedPassword): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE TBL_USERS 
            SET password_hash = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$hashedPassword, $userId]);
    }

    public function updateFailedAttempts(int $userId, int $attempts, bool $lock = false): void
    {
        if ($lock) {
            $sql = "UPDATE TBL_USERS 
                    SET failedAttempts = ?, accountStatus = 'Locked', locked_at = NOW() 
                    WHERE user_id = ?";
        } else {
            $sql = "UPDATE TBL_USERS SET failedAttempts = ? WHERE user_id = ?";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$attempts, $userId]);
    }

    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE TBL_USERS SET failedAttempts = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function unlockAccount(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE TBL_USERS 
            SET accountStatus = 'Active', failedAttempts = 0 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    }

    // ==================================================
    // ROLE UTILITIES
    // ==================================================

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

    // ==================================================
    // PAGINATION + FETCH WITH ROLE JOIN
    // ==================================================

    public function getPaginatedUsers(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.user_id, 
                u.userName, 
                u.email, 
                r.role_name AS role
            FROM TBL_USERS u
            LEFT JOIN TBL_ROLES r ON u.role_id = r.role_id
            ORDER BY u.userName
            LIMIT ? OFFSET ?
        ");
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

    // ==================================================
    // SINGLE USER FETCH (FIXED)
    // ==================================================

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.user_id,
                u.userName,
                u.email,
                u.role_id,
                r.role_name AS role,
                u.created_at,
                u.updated_at
            FROM TBL_USERS u
            LEFT JOIN TBL_ROLES r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    // ==================================================
    // UPDATE + DELETE
    // ==================================================

    public function updateUser(int $userId, array $data): void
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $userId;
        $sql = "UPDATE TBL_USERS SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function deleteUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM TBL_USERS WHERE user_id = ?");
        $stmt->execute([$userId]);
        return ($stmt->rowCount() > 0);
    }


    /**
     * Fetches the student's detailed info for display/forms.
     * Returns associative array with all name components and student number.
     */
    public function getStudentProfileByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            s.studentNumber,
            s.firstName,
            s.middleName,
            s.lastName,
            s.suffix
        FROM TBL_STUDENTDETAILS s
        WHERE s.user_id = ?
        LIMIT 1
    ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        if (!$profile) return null;

        // Build the formatted name
        $middle = $profile['middleName'] ? (mb_substr($profile['middleName'], 0, 1) . '. ') : '';
        $suffix = $profile['suffix'] ? (' ' . $profile['suffix']) : '';
        $profile['fullName'] = trim($profile['firstName'] . ' ' . $middle . $profile['lastName'] . $suffix);

        return $profile;
    }
}
