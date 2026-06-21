<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id  = $_POST['member_id'] ?? '';
    $trainer_id = $_POST['trainer_id'] ?? '';

    $exists = db_query(
        'SELECT 1 FROM member_trainers WHERE member_id=? AND trainer_id=?',
        [$member_id, $trainer_id], true
    );
    if ($exists) {
        set_flash('error', 'That trainer is already assigned to that member.');
    } else {
        db_exec('INSERT INTO member_trainers (member_id, trainer_id) VALUES (?,?)',
                [$member_id, $trainer_id]);
        set_flash('success', 'Trainer assigned to member.');
    }
    header('Location: /php/admin/assign.php');
    exit;
}

$members = db_query(
    "SELECT u.user_id, u.full_name FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name='Member' ORDER BY u.full_name"
);
$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name, tp.specialization
     FROM trainer_profiles tp JOIN users tu ON tp.user_id = tu.user_id
     ORDER BY tu.full_name"
);
$assignments = db_query(
    "SELECT mt.id, mu.full_name AS member_name, tu.full_name AS trainer_name
     FROM member_trainers mt
     JOIN users mu ON mt.member_id = mu.user_id
     JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
     JOIN users tu ON tp.user_id = tu.user_id
     ORDER BY mu.full_name"
);

$title = 'Assign trainers';
include __DIR__ . '/../includes/header.php';
?>

<h1>Assign trainers</h1>
<p class="subtitle">Match a member with a trainer. Only assigned members can book appointments with that trainer.</p>

<div class="card">
  <h2>New assignment</h2>
  <?php if ($members && $trainers): ?>
    <form method="post" action="/php/admin/assign.php">
      <label for="member_id">Member</label>
      <select id="member_id" name="member_id" required>
        <?php foreach ($members as $m): ?>
          <option value="<?= htmlspecialchars($m['user_id']) ?>"><?= htmlspecialchars($m['full_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="trainer_id">Trainer</label>
      <select id="trainer_id" name="trainer_id" required>
        <?php foreach ($trainers as $t): ?>
          <option value="<?= htmlspecialchars($t['trainer_id']) ?>">
            <?= htmlspecialchars($t['full_name']) ?> — <?= htmlspecialchars($t['specialization'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn" type="submit">Assign trainer</button>
    </form>
  <?php else: ?>
    <p class="empty">You need at least one member and one trainer before you can assign.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Current assignments</h2>
  <?php if ($assignments): ?>
    <table>
      <tr><th>Member</th><th>Trainer</th></tr>
      <?php foreach ($assignments as $a): ?>
      <tr>
        <td><?= htmlspecialchars($a['member_name']) ?></td>
        <td><?= htmlspecialchars($a['trainer_name']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No assignments yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
