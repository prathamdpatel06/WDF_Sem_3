<?php
// ============================================================
// students.php - database functions for student accounts.
// A student is stored in TWO linked tables: users (login) and students (profile).
// Both inserts happen in one transaction, so either both are saved or neither is.
// ============================================================

/** Creates a unique code such as STU20264821. */
function generate_student_code(mysqli $conn): string {
    do {
        $code = 'STU' . date('Y') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    } while (db_fetch_one($conn, "SELECT id FROM students WHERE student_code = ?", 's', [$code]));
    return $code;
}

/** Inserts a new student account. Returns the new user id. Throws on duplicate username/email. */
function create_student(mysqli $conn, array $d, string $password): int {
    $conn->begin_transaction();
    try {
        db_execute($conn,
            "INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, 'student')",
            'ssss', [$d['username'], $d['email'], password_hash($password, PASSWORD_DEFAULT), $d['full_name']]);
        $user_id = (int)$conn->insert_id;

        db_execute($conn,
            "INSERT INTO students (user_id, student_code, mobile, department, year_of_study, gender) VALUES (?, ?, ?, ?, ?, ?)",
            'isssis', [$user_id, generate_student_code($conn), $d['mobile'], $d['department'], (int)$d['year_of_study'], $d['gender']]);

        $conn->commit();
        return $user_id;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Updates profile data of a student. $password is null to keep the old password. */
function update_student_profile(mysqli $conn, int $user_id, array $d, ?string $password = null): void {
    $conn->begin_transaction();
    try {
        db_execute($conn, "UPDATE users SET full_name = ?, email = ? WHERE id = ?",
            'ssi', [$d['full_name'], $d['email'], $user_id]);
        db_execute($conn,
            "UPDATE students SET mobile = ?, department = ?, year_of_study = ?, gender = ? WHERE user_id = ?",
            'ssisi', [$d['mobile'], $d['department'], (int)$d['year_of_study'], $d['gender'], $user_id]);
        if ($password !== null) {
            db_execute($conn, "UPDATE users SET password_hash = ? WHERE id = ?",
                'si', [password_hash($password, PASSWORD_DEFAULT), $user_id]);
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Returns an array of field errors when the username or email is used by another account (empty = free). */
function find_duplicate_account(mysqli $conn, string $username, string $email, int $ignore_user_id = 0): array {
    $errors = [];
    $rows = db_fetch_all($conn, "SELECT id, username, email FROM users WHERE (username = ? OR email = ?) AND id <> ?",
        'ssi', [$username, $email, $ignore_user_id]);
    foreach ($rows as $row) {
        if (strcasecmp($row['username'], $username) === 0) {
            $errors['username'] = 'This username is already taken.';
        }
        if (strcasecmp($row['email'], $email) === 0) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }
    return $errors;
}
