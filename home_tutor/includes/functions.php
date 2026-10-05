<?php
// Common helpers used by every page. Include this FIRST in each page.
date_default_timezone_set('Asia/Kathmandu');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// ---------- output + navigation ----------
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function redirect($url) {
    header('Location: ' . $url);
    exit;
}
function set_flash($type, $msg) {
    $_SESSION['flash'] = array('type' => $type, 'msg' => $msg);
}
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ---------- login / roles ----------
// Returns the logged-in user (fresh from the database) or null.
function current_user() {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (isset($_SESSION['user_id'])) {
            $user = db_one('SELECT id, name, email, phone, role, status FROM users WHERE id = ?', 'i', array($_SESSION['user_id']));
        }
    }
    return $user;
}
function is_logged_in() {
    return current_user() !== null;
}
function require_login() {
    $u = current_user();
    if ($u === null || $u['status'] === 'rejected') {
        unset($_SESSION['user_id']);
        set_flash('error', 'Please login to continue.');
        redirect('login.php');
    }
}
function require_role($role) {
    require_login();
    $u = current_user();
    if ($u['role'] !== $role) {
        set_flash('error', 'You are not allowed to open that page.');
        redirect('dashboard.php');
    }
}

// ---------- CSRF protection for forms ----------
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check() {
    $t = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400);
        die('Invalid form submission. Please go back, refresh the page and try again.');
    }
}

// ---------- reading form input ----------
// Trimmed text from $_POST (empty string when missing).
function post($key) {
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? trim($_POST[$key]) : '';
}
// Untrimmed text (used for passwords).
function post_raw($key) {
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? $_POST[$key] : '';
}
function get_text($key) {
    return (isset($_GET[$key]) && is_string($_GET[$key])) ? trim($_GET[$key]) : '';
}

// ---------- validation (each returns '' when OK, or an error message) ----------
function validate_name($name) {
    if ($name === '') return 'Full name is required.';
    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $name)) {
        return 'Name must be 2-50 characters and contain only letters, spaces, . \' or -.';
    }
    return '';
}
function validate_email($email) {
    if ($email === '') return 'Email is required.';
    if (strlen($email) > 100) return 'Email must be 100 characters or fewer.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Please enter a valid email address (example: name@gmail.com).';
    // extra check: must have a dot and a real ending such as .com / .np (filter_var allows "a@b")
    if (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/', $email)) {
        return 'Email must look like name@domain.com.';
    }
    return '';
}
function validate_phone($phone) {
    if ($phone === '') return 'Phone number is required.';
    if (!preg_match('/^9[6-8][0-9]{8}$/', $phone)) {
        return 'Enter a 10-digit Nepali mobile number starting with 96, 97 or 98.';
    }
    return '';
}
function validate_password($pw) {
    if ($pw === '') return 'Password is required.';
    if (strlen($pw) < 8 || strlen($pw) > 64) return 'Password must be 8 to 64 characters long.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) {
        return 'Password must contain at least one letter and one number.';
    }
    return '';
}

// ---------- small display helpers ----------
function status_badge($status) {
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}
function stars($avg) {
    $full = (int)round($avg);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}
function format_time($t) {
    return date('g:i A', strtotime($t));
}
function format_date($d) {
    return date('d M Y', strtotime($d));
}
