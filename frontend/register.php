<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$full_name || !$email || !$password) {
        set_flash('error', 'Name, email and password are required.');
        redirect_to('/register.php');
    }

    $existing = db_query('SELECT user_id FROM users WHERE email=?', [$email], true);
    if ($existing) {
        set_flash('error', 'That email is already registered. Try logging in.');
        redirect_to('/register.php');
    }

    $role = db_query("SELECT role_id FROM roles WHERE role_name='Member'", [], true);
    db_exec(
        'INSERT INTO users (full_name, email, password, phone, role_id) VALUES (?,?,?,?,?)',
        [$full_name, $email, password_hash($password, PASSWORD_BCRYPT), $phone, $role['role_id']]
    );

    set_flash('success', 'Account created. Please log in.');
    redirect_to('/login.php');
}

$title  = 'Register';
$narrow = true;
include __DIR__ . '/includes/header.php';
?>

<h1>Create your account</h1>
<p class="subtitle">Register as a member. You can choose a membership plan right after you log in.</p>

<div class="card">
  <form method="post" action="<?= htmlspecialchars(url_path('/register.php')) ?>">
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="phone">Phone (optional)</label>
    <input type="text" id="phone" name="phone">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button class="btn" type="submit">Create account</button>
  </form>
</div>

<p class="muted">Already a member? <a href="<?= htmlspecialchars(url_path('/login.php')) ?>">Log in</a>.</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
