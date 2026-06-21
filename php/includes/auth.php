<?php
// Session helpers — include after session_start()

function current_user(): array|null {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        $_SESSION['flash'] = ['error', 'Please log in to continue.'];
        header('Location: /php/login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: /php/index.php');
        exit;
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
