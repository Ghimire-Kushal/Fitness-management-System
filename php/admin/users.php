<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

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
  <table>
    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= htmlspecialchars($u['full_name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
      <td><?= htmlspecialchars($u['role_name']) ?></td>
      <td><?= $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
