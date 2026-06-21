<?php
// ── Database connection ────────────────────────────────────────
// Connects to XAMPP MySQL via its Unix socket.
// Same server phpMyAdmin (http://localhost/phpmyadmin) uses.
// To change credentials, edit only this file.
// ──────────────────────────────────────────────────────────────
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_db');
define('DB_SOCK', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // host='localhost' + socket tells MySQLi to use the Unix socket
        $conn = new mysqli('localhost', DB_USER, DB_PASS, DB_NAME, 0, DB_SOCK);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function db_query(string $sql, array $params = [], bool $one = false): array|null {
    $db   = get_db();
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
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

function db_exec(string $sql, array $params = []): int {
    $db   = get_db();
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}
