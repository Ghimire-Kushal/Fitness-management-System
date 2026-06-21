<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Member');

$uid = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$full_name) {
        set_flash('error', 'Name cannot be empty.');
        redirect_to('/member/profile.php');
    }

    db_exec('UPDATE users SET full_name=?, phone=? WHERE user_id=?', [$full_name, $phone, $uid]);
    if ($password) {
        db_exec('UPDATE users SET password=? WHERE user_id=?',
                [password_hash($password, PASSWORD_BCRYPT), $uid]);
    }
    $_SESSION['user']['full_name'] = $full_name;
    set_flash('success', 'Profile updated.');
    redirect_to('/member/profile.php');
}

$profile = db_query('SELECT * FROM users WHERE user_id=?', [$uid], true);

$title  = 'Profile';
$narrow = true;
include __DIR__ . '/../includes/header.php';
?>

<h1>Your profile</h1>
<p class="subtitle">Update your details. Leave the password blank to keep your current one.</p>

<div class="card">
  <form method="post" action="<?= htmlspecialchars(url_path('/member/profile.php')) ?>">
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name"
           value="<?= htmlspecialchars($profile['full_name']) ?>" required>

    <label>Email</label>
    <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled>

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">

    <label for="password">New password</label>
    <input type="password" id="password" name="password"
           placeholder="Leave blank to keep current password">

    <button class="btn" type="submit">Save changes</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
