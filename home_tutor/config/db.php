<?php
// Database connection (default XAMPP settings: user "root", empty password)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'home_tutor_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('<h3>Database connection failed.</h3><p>Start MySQL in the XAMPP Control Panel and import <b>database/home_tutor_db.sql</b> in phpMyAdmin.</p>');
}

// ---- small helper functions so the pages stay simple ----

// Run a prepared statement and return the statement object.
function db_query($sql, $types = '', $params = array()) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// SELECT many rows -> array of associative arrays.
function db_all($sql, $types = '', $params = array()) {
    $stmt = db_query($sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// SELECT one row -> associative array or null.
function db_one($sql, $types = '', $params = array()) {
    $rows = db_all($sql, $types, $params);
    return $rows ? $rows[0] : null;
}

// INSERT / UPDATE / DELETE -> number of affected rows.
function db_run($sql, $types = '', $params = array()) {
    $stmt = db_query($sql, $types, $params);
    $n = $stmt->affected_rows;
    $stmt->close();
    return $n;
}
