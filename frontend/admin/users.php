<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

$current_uid = $_SESSION['user']['user_id'];

// --- Add user ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role_name = $_POST['role'] ?? 'Member';

    if (!$full_name || !$email || !$password) {
        set_flash('error', 'Name, email and password are required.');
    } elseif (!in_array($role_name, ['Admin', 'Member', 'Trainer'])) {
        set_flash('error', 'Invalid role.');
    } elseif (db_query('SELECT user_id FROM users WHERE email=?', [$email], true)) {
        set_flash('error', 'That email is already registered.');
    } else {
        $role = db_query('SELECT role_id FROM roles WHERE role_name=?', [$role_name], true);
        $new_id = db_exec(
            'INSERT INTO users (full_name, email, password, phone, role_id) VALUES (?,?,?,?,?)',
            [$full_name, $email, password_hash($password, PASSWORD_BCRYPT), $phone, $role['role_id']]
        );
        if ($role_name === 'Trainer') {
            db_exec('INSERT INTO trainer_profiles (user_id) VALUES (?)', [$new_id]);
        }
        set_flash('success', "User \"$full_name\" added.");
    }
    redirect_to('/admin/users.php');
}

// --- Remove user ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === (int)$current_uid) {
        set_flash('error', 'You cannot delete your own account.');
    } elseif ($uid > 0) {
        $target = db_query('SELECT full_name FROM users WHERE user_id=?', [$uid], true);
        // Null out workout_plan trainer references before cascade-deleting trainer_profile
        db_exec(
            'UPDATE workout_plans SET trainer_id=NULL
             WHERE trainer_id IN (SELECT trainer_id FROM trainer_profiles WHERE user_id=?)',
            [$uid]
        );
        db_exec('DELETE FROM users WHERE user_id=?', [$uid]);
        set_flash('success', "User \"{$target['full_name']}\" removed.");
    }
    redirect_to('/admin/users.php');
}

$users = db_query(
    "SELECT u.user_id, u.full_name, u.email, u.phone, r.role_name, u.created_at
     FROM users u JOIN roles r ON u.role_id = r.role_id
     ORDER BY r.role_name, u.full_name"
);
$title = 'Users';
include __DIR__ . '/../includes/header.php';
?>

<h1>Users</h1>
<p class="subtitle">Everyone registered in the system.</p>

<div class="card">
  <h2>Add user</h2>
  <form method="post" action="/Fitness/admin/users.php">
    <input type="hidden" name="action" value="add">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px">
      <div>
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" required>
      </div>
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div>
        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone">
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div>
        <label for="role">Role</label>
        <select id="role" name="role">
          <option value="Member">Member</option>
          <option value="Trainer">Trainer</option>
          <option value="Admin">Admin</option>
        </select>
      </div>
    </div>
    <button class="btn" type="submit" style="margin-top:12px">Add user</button>
  </form>
</div>

<div class="card">
  <h2>All users</h2>
  <table>
    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Action</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['full_name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
      <td><?= htmlspecialchars($u['role_name']) ?></td>
      <td><?= $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : '' ?></td>
      <td>
        <?php if ((int)$u['user_id'] !== (int)$current_uid): ?>
          <form method="post" action="/Fitness/admin/users.php"
                onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
            <button class="btn btn-small btn-danger" type="submit">Remove</button>
          </form>
        <?php else: ?>
          <span class="muted">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
