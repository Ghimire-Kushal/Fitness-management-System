<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Member');

$uid = $_SESSION['user']['user_id'];

$workouts = db_query(
    "SELECT w.*, tu.full_name AS trainer_name
     FROM workout_plans w
     LEFT JOIN trainer_profiles tp ON w.trainer_id = tp.trainer_id
     LEFT JOIN users tu ON tp.user_id = tu.user_id
     WHERE w.member_id=? ORDER BY w.created_at DESC",
    [$uid]
);

$title = 'Workout plans';
include __DIR__ . '/../includes/header.php';
?>

<h1>Your workout plans</h1>
<p class="subtitle">Plans put together for you. Follow them at your own pace.</p>

<?php if ($workouts): ?>
  <?php foreach ($workouts as $w): ?>
    <div class="card">
      <h2><?= htmlspecialchars($w['title']) ?></h2>
      <p class="muted">
        <?php if ($w['trainer_name']): ?>By <?= htmlspecialchars($w['trainer_name']) ?> &middot; <?php endif; ?>
        Added <?= $w['created_at'] ? date('d M Y', strtotime($w['created_at'])) : '' ?>
      </p>
      <p style="white-space: pre-line"><?= htmlspecialchars($w['description']) ?></p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p class="empty">You do not have a workout plan yet. Once the admin assigns one, it will show up here.</p>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
