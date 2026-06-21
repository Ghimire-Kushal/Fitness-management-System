<?php
// Database connection — edit only this file if credentials change
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_db');

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Run a SELECT query; returns array of assoc rows (or single row if $one=true)
function db_query(string $sql, array $params = [], bool $one = false): array|null {
    $db   = get_db();
    $stmt = $db->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($one) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Run INSERT / UPDATE / DELETE; returns lastInsertId for INSERTs
function db_exec(string $sql, array $params = []): int {
    $db   = get_db();
    $stmt = $db->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}
