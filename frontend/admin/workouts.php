<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id   = $_POST['member_id'] ?? '';
    $trainer_id  = ($_POST['trainer_id'] ?? '') ?: null;
    $title_input = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$member_id || !$title_input || !$description) {
        set_flash('error', 'Member, title and description are required.');
        redirect_to('/admin/workouts.php');
    }

    db_exec(
        'INSERT INTO workout_plans (member_id, trainer_id, title, description) VALUES (?,?,?,?)',
        [$member_id, $trainer_id, $title_input, $description]
    );
    set_flash('success', 'Workout plan assigned to member.');
    redirect_to('/admin/workouts.php');
}

$members = db_query(
    "SELECT u.user_id, u.full_name FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name='Member' ORDER BY u.full_name"
);
$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name FROM trainer_profiles tp
     JOIN users tu ON tp.user_id = tu.user_id ORDER BY tu.full_name"
);
$plans = db_query(
    "SELECT w.*, mu.full_name AS member_name, tu.full_name AS trainer_name
     FROM workout_plans w
     JOIN users mu ON w.member_id = mu.user_id
     LEFT JOIN trainer_profiles tp ON w.trainer_id = tp.trainer_id
     LEFT JOIN users tu ON tp.user_id = tu.user_id
     ORDER BY w.created_at DESC"
);

$title = 'Workout plans';
include __DIR__ . '/../includes/header.php';
?>

<h1>Workout plans</h1>
<p class="subtitle">Create a personalised plan and assign it to a member.</p>

<div class="card">
  <h2>New workout plan</h2>
  <?php if ($members): ?>
    <form method="post" action="<?= htmlspecialchars(url_path('/admin/workouts.php')) ?>">
      <label for="member_id">For member</label>
      <select id="member_id" name="member_id" required>
        <?php foreach ($members as $m): ?>
          <option value="<?= htmlspecialchars($m['user_id']) ?>"><?= htmlspecialchars($m['full_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="trainer_id">From trainer (optional)</label>
      <select id="trainer_id" name="trainer_id">
        <option value="">— none —</option>
        <?php foreach ($trainers as $t): ?>
          <option value="<?= htmlspecialchars($t['trainer_id']) ?>"><?= htmlspecialchars($t['full_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="plan_title">Plan title</label>
      <input type="text" id="plan_title" name="title"
             placeholder="e.g. Beginner full-body, 3 days a week" required>

      <label for="description">Plan details</label>
      <textarea id="description" name="description"
                placeholder="List the exercises, sets and reps..." required></textarea>

      <button class="btn" type="submit">Assign plan</button>
    </form>
  <?php else: ?>
    <p class="empty">There are no members to assign a plan to yet.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Assigned plans</h2>
  <?php if ($plans): ?>
    <table>
      <tr><th>Member</th><th>Title</th><th>Trainer</th><th>Added</th></tr>
      <?php foreach ($plans as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['member_name']) ?></td>
        <td><?= htmlspecialchars($p['title']) ?></td>
        <td><?= htmlspecialchars($p['trainer_name'] ?? '—') ?></td>
        <td><?= $p['created_at'] ? date('d M Y', strtotime($p['created_at'])) : '' ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No workout plans assigned yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
