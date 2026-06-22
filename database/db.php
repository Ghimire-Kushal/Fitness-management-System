<?php

define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_db');
define('DB_SOCK', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

// ─── Connection Singleton ───────────────────────────────────────
// Returns one shared mysqli connection, reused across the request.
function get_db(): mysqli {
    static $conn = null;                 // persists between calls in the same request
    if ($conn === null) {
        // Throw exceptions on errors instead of silent failures
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // host='localhost' + socket tells MySQLi to use the Unix socket
        $conn = new mysqli('localhost', DB_USER, DB_PASS, DB_NAME, 0, DB_SOCK);
        $conn->set_charset('utf8mb4');   // full Unicode support (emoji, etc.)
    }
    return $conn;
}

// ─── SELECT Helper ──────────────────────────────────────────────
// Runs a prepared SELECT. $one=true returns a single row (or null),
// otherwise returns an array of all rows.
function db_query(string $sql, array $params = [], bool $one = false): array|null {
    $db   = get_db();
    $stmt = $db->prepare($sql);          // prepared statement = SQL injection safe
    if ($params) {
        // Bind all params as strings; MySQL coerces types as needed
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($one) {                          // single-row mode
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;             // null when no match
    }

    // multi-row mode: fetch everything as an associative array
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

//INSERT / UPDATE / DELETE Helper 
// Runs a prepared write query and returns the last insert ID.
function db_exec(string $sql, array $params = []): int {
    $db   = get_db();
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $id = $stmt->insert_id;              // auto-increment ID of new row (0 if none)
    $stmt->close();
    return $id;
}