<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name'] ?? '');
    $email          = strtolower(trim($_POST['email'] ?? ''));
    $password       = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $bio            = trim($_POST['bio'] ?? '');

    if (!$full_name || !$email || !$password) {
        set_flash('error', 'Name, email and password are required.');
        redirect_to('/admin/trainers.php');
    }
    if (db_query('SELECT user_id FROM users WHERE email=?', [$email], true)) {
        set_flash('error', 'That email is already in use.');
        redirect_to('/admin/trainers.php');
    }

    $role   = db_query("SELECT role_id FROM roles WHERE role_name='Trainer'", [], true);
    $new_id = db_exec(
        'INSERT INTO users (full_name, email, password, role_id) VALUES (?,?,?,?)',
        [$full_name, $email, password_hash($password, PASSWORD_BCRYPT), $role['role_id']]
    );
    db_exec(
        'INSERT INTO trainer_profiles (user_id, specialization, bio) VALUES (?,?,?)',
        [$new_id, $specialization, $bio]
    );

    set_flash('success', 'Trainer added.');
    redirect_to('/admin/trainers.php');
}

$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name, tu.email, tp.specialization, tp.bio
     FROM trainer_profiles tp JOIN users tu ON tp.user_id = tu.user_id
     ORDER BY tu.full_name"
);

$title = 'Trainers';
include __DIR__ . '/../includes/header.php';
?>

<h1>Trainers</h1>
<p class="subtitle">Add trainers and see who is on the team.</p>

<div class="card">
  <h2>Add a trainer</h2>
  <form method="post" action="<?= htmlspecialchars(url_path('/admin/trainers.php')) ?>">
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Temporary password</label>
    <input type="password" id="password" name="password" required>

    <label for="specialization">Specialization</label>
    <input type="text" id="specialization" name="specialization"
           placeholder="e.g. Strength &amp; Conditioning">

    <label for="bio">Short bio</label>
    <textarea id="bio" name="bio"></textarea>

    <button class="btn" type="submit">Add trainer</button>
  </form>
</div>

<div class="card">
  <h2>Current trainers</h2>
  <?php if ($trainers): ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Specialization</th><th>Bio</th></tr>
      <?php foreach ($trainers as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['full_name']) ?></td>
        <td><?= htmlspecialchars($t['email']) ?></td>
        <td><?= htmlspecialchars($t['specialization'] ?? '—') ?></td>
        <td class="muted"><?= htmlspecialchars($t['bio'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No trainers added yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
