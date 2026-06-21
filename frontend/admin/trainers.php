<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

// --- Add trainer ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'add') === 'add') {
    $full_name      = trim($_POST['full_name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $bio            = trim($_POST['bio'] ?? '');

    if (!$full_name) {
        set_flash('error', 'Full name is required.');
        redirect_to('/admin/trainers.php');
    }

    $role = db_query("SELECT role_id FROM roles WHERE role_name='Trainer'", [], true);
    // Auto-generate internal email and locked password — trainer cannot log in
    $internal_email = 'trainer_' . uniqid() . '@internal.gym';
    $locked_hash    = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

    $new_id = db_exec(
        'INSERT INTO users (full_name, email, password, role_id) VALUES (?,?,?,?)',
        [$full_name, $internal_email, $locked_hash, $role['role_id']]
    );
    db_exec(
        'INSERT INTO trainer_profiles (user_id, specialization, bio) VALUES (?,?,?)',
        [$new_id, $specialization, $bio]
    );

    set_flash('success', "Trainer \"$full_name\" added.");
    redirect_to('/admin/trainers.php');
}

// --- Remove trainer ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $trainer_id = (int)($_POST['trainer_id'] ?? 0);
    if ($trainer_id > 0) {
        $t = db_query('SELECT u.full_name, tp.user_id FROM trainer_profiles tp JOIN users u ON tp.user_id=u.user_id WHERE tp.trainer_id=?', [$trainer_id], true);
        if ($t) {
            db_exec('UPDATE workout_plans SET trainer_id=NULL WHERE trainer_id=?', [$trainer_id]);
            db_exec('DELETE FROM users WHERE user_id=?', [$t['user_id']]);
            set_flash('success', "Trainer \"{$t['full_name']}\" removed.");
        }
    }
    redirect_to('/admin/trainers.php');
}

$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name, tp.specialization, tp.bio
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
    <input type="hidden" name="action" value="add">

    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" required>

    <label for="specialization">Specialization</label>
    <input type="text" id="specialization" name="specialization">

    <label for="bio">Short bio</label>
    <textarea id="bio" name="bio"></textarea>

    <button class="btn" type="submit">Add trainer</button>
  </form>
</div>

<div class="card">
  <h2>Current trainers</h2>
  <?php if ($trainers): ?>
    <table>
      <tr><th>Name</th><th>Specialization</th><th>Bio</th><th>Action</th></tr>
      <?php foreach ($trainers as $t): ?>
      <tr>
        <td><strong><?= htmlspecialchars($t['full_name']) ?></strong></td>
        <td><?= htmlspecialchars($t['specialization'] ?? '—') ?></td>
        <td class="muted"><?= htmlspecialchars($t['bio'] ?? '—') ?></td>
        <td>
          <form method="post" action="<?= htmlspecialchars(url_path('/admin/trainers.php')) ?>"
                onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($t['full_name'])) ?>?')">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="trainer_id" value="<?= $t['trainer_id'] ?>">
            <button class="btn btn-small btn-danger" type="submit">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No trainers added yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
