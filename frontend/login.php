<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && current_user()) {
    redirect_to(current_user()['role'] === 'Member'
        ? '/member/dashboard.php'
        : '/admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $row = db_query(
        'SELECT u.*, r.role_name AS role FROM users u
         JOIN roles r ON u.role_id = r.role_id WHERE u.email=?',
        [$email], true
    );

    if ($row && password_verify($password, $row['password'])) {
        if ($row['role'] === 'Trainer') {
            set_flash('error', 'Trainer accounts do not have a login portal.');
            redirect_to('/login.php');
        }
        $_SESSION['user'] = [
            'user_id'   => $row['user_id'],
            'full_name' => $row['full_name'],
            'email'     => $row['email'],
            'role'      => $row['role'],
        ];
        redirect_to($row['role'] === 'Member'
            ? '/member/dashboard.php'
            : '/admin/dashboard.php');
    }

    set_flash('error', 'Email or password is incorrect.');
    redirect_to('/login.php');
}

$title  = 'Log in';
$narrow = true;
include __DIR__ . '/includes/header.php';
?>

<h1>Log in</h1>
<p class="subtitle">Welcome back. Enter your details to continue.</p>

<div class="card">
  <form method="post" action="<?= htmlspecialchars(url_path('/login.php')) ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button class="btn" type="submit">Log in</button>
  </form>
</div>

<p class="muted">New here? <a href="<?= htmlspecialchars(url_path('/register.php')) ?>">Create a member account</a>.</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
