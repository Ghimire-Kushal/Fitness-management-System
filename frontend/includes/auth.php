<?php
// Session helpers

// Base URL prefix — matches the folder name inside XAMPP htdocs
define('APP_BASE', '/Fitness');

function current_user(): array|null {
    return $_SESSION['user'] ?? null;
}

function url_path(string $path): string {
    return APP_BASE . '/' . ltrim($path, '/');
}

function redirect_to(string $path): void {
    header('Location: ' . url_path($path));
    exit;
}

function require_login(): void {
    if (!current_user()) {
        $_SESSION['flash'] = ['error', 'Please log in to continue.'];
        redirect_to('/login.php');
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        redirect_to('/index.php');
    }
}

function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = [$type, $msg];
}

function get_flash(): array|null {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
