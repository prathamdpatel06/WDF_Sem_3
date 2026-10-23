<?php
// ============================================================
// validation.php - SERVER-SIDE validation rules.
// The same rules are repeated in assets/js/validation.js for instant feedback.
// Never trust only the browser: always validate here too.
// ============================================================

const COURSES = ['Computer Engineering', 'Information Technology', 'Electronics & Communication',
    'Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Chemical Engineering'];
const GENDERS = ['Male', 'Female', 'Other'];
const EVENT_CATEGORIES = ['Hackathon', 'Workshop', 'Symposium', 'Seminar', 'Cultural', 'Sports'];
const EVENT_STATUSES = ['Open', 'Closed'];
const FEEDBACK_TYPES = ['General', 'Experience', 'Suggestion', 'Bug Report'];

function is_valid_name(string $v): bool {
    return (bool)preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $v);
}
function is_valid_username(string $v): bool {
    return (bool)preg_match('/^[A-Za-z0-9_]{4,20}$/', $v);
}
function is_valid_email(string $v): bool {
    return strlen($v) <= 100 && (bool)preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $v);
}
function is_valid_mobile(string $v): bool {
    return (bool)preg_match('/^[6-9][0-9]{9}$/', $v);
}

/** Returns an error message, or null when the password is strong enough. */
function password_problem(string $p): ?string {
    if (strlen($p) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/[0-9]/', $p)) {
        return 'Password must contain at least one letter and one number.';
    }
    return null;
}

/**
 * Validates the student fields used by registration AND by the admin "add/edit student" form.
 * $password is null when no password is being set (edit form, password left blank).
 * Returns an array: field name => error message. Empty array = valid.
 */
function validate_student_fields(array $d, ?string $password, bool $check_username = true): array {
    $errors = [];
    if (!is_valid_name($d['full_name'] ?? '')) {
        $errors['full_name'] = 'Full name: letters only, 2 to 100 characters.';
    }
    if ($check_username && !is_valid_username($d['username'] ?? '')) {
        $errors['username'] = 'Username: 4 to 20 letters, numbers or underscores.';
    }
    if (!is_valid_email($d['email'] ?? '')) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!is_valid_mobile($d['mobile'] ?? '')) {
        $errors['mobile'] = 'Enter a valid 10-digit mobile number starting with 6-9.';
    }
    if (!in_array($d['department'] ?? '', COURSES, true)) {
        $errors['department'] = 'Please select a valid course.';
    }
    $year = (int)($d['year_of_study'] ?? 0);
    if ($year < 1 || $year > 4) {
        $errors['year_of_study'] = 'Please select a year between 1 and 4.';
    }
    if (!in_array($d['gender'] ?? '', GENDERS, true)) {
        $errors['gender'] = 'Please select a gender option.';
    }
    if ($password !== null) {
        $problem = password_problem($password);
        if ($problem) {
            $errors['password'] = $problem;
        }
    }
    return $errors;
}

/** Event form validation (Practical 12). The poster file is checked separately by validate_poster_upload(). */
function validate_event_fields(array $d): array {
    $errors = [];
    if (mb_strlen(trim($d['title'] ?? '')) < 3 || mb_strlen(trim($d['title'] ?? '')) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }
    if (!in_array($d['category'] ?? '', EVENT_CATEGORIES, true)) {
        $errors['category'] = 'Please choose a valid category.';
    }
    $date = DateTime::createFromFormat('Y-m-d', $d['event_date'] ?? '');
    if (!$date || $date->format('Y-m-d') !== ($d['event_date'] ?? '')) {
        $errors['event_date'] = 'Please enter a valid date.';
    }
    if (trim($d['location'] ?? '') === '' || mb_strlen(trim($d['location'])) > 120) {
        $errors['location'] = 'Location is required (max 120 characters).';
    }
    $seats = (int)($d['seats_limit'] ?? 0);
    if ($seats < 1 || $seats > 1000) {
        $errors['seats_limit'] = 'Seats must be a number between 1 and 1000.';
    }
    if (mb_strlen($d['description'] ?? '') > 1000) {
        $errors['description'] = 'Description can be at most 1000 characters.';
    }
    if (!in_array($d['status'] ?? '', EVENT_STATUSES, true)) {
        $errors['status'] = 'Please choose Open or Closed.';
    }
    return $errors;
}

/**
 * Checks an uploaded poster image: size, real MIME type and image validity.
 * Returns an error message or null.
 */
function validate_poster_upload(array $file): ?string {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;   // poster is optional
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'The poster could not be uploaded. Please try again.';
    }
    if ($file['size'] > MAX_POSTER_SIZE) {
        return 'The poster must be 2 MB or smaller.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!array_key_exists($mime, ALLOWED_POSTER_TYPES)) {
        return 'Only JPG, PNG or WEBP images are allowed.';
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return 'The file is not a valid image.';
    }
    return null;
}
