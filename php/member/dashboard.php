<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Member');

$uid = $_SESSION['user']['user_id'];

$membership = db_query(
    "SELECT m.*, p.plan_name, p.duration_type, p.price
     FROM memberships m JOIN membership_plans p ON m.plan_id = p.plan_id
     WHERE m.user_id=? AND m.status='active'
     ORDER BY m.start_date DESC LIMIT 1",
    [$uid], true
);
$bookings = db_query(
    "SELECT b.*, s.slot_date, s.start_time, s.end_time, tu.full_name AS trainer_name
     FROM bookings b
     JOIN time_slots s ON b.slot_id = s.slot_id
     LEFT JOIN trainer_profiles tp ON b.trainer_id = tp.trainer_id
     LEFT JOIN users tu ON tp.user_id = tu.user_id
     WHERE b.member_id=? ORDER BY s.slot_date, s.start_time",
    [$uid]
);
$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name, tp.specialization
     FROM member_trainers mt
     JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
     JOIN users tu ON tp.user_id = tu.user_id
     WHERE mt.member_id=?",
    [$uid]
);
$workouts = db_query(
    "SELECT plan_id, title FROM workout_plans WHERE member_id=?",
    [$uid]
);

$title = 'My dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1>Hello, <?= htmlspecialchars($user['full_name']) ?></h1>
<p class="subtitle">Here is everything on your account at a glance.</p>

<div class="card">
  <h2>Membership</h2>
  <?php if ($membership): ?>
    <p><strong><?= htmlspecialchars($membership['plan_name']) ?></strong>
       (<?= htmlspecialchars($membership['duration_type']) ?>) &middot; Rs. <?= htmlspecialchars($membership['price']) ?></p>
    <p class="muted">Valid <?= htmlspecialchars($membership['start_date']) ?> to <?= htmlspecialchars($membership['end_date']) ?></p>
  <?php else: ?>
    <p class="empty">You have not chosen a plan yet.</p>
    <a class="btn btn-small" href="/php/member/membership.php">Choose a plan</a>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Your bookings</h2>
  <?php if ($bookings): ?>
    <table>
      <tr><th>Date</th><th>Time</th><th>Type</th><th>Trainer</th><th>Status</th></tr>
      <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?= htmlspecialchars($b['slot_date']) ?></td>
        <td><?= htmlspecialchars($b['start_time']) ?> – <?= htmlspecialchars($b['end_time']) ?></td>
        <td><?= $b['booking_type'] === 'appointment' ? 'Trainer appointment' : 'Gym session' ?></td>
        <td><?= htmlspecialchars($b['trainer_name'] ?? '—') ?></td>
        <td><span class="badge <?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No bookings yet.</p>
    <a class="btn btn-small" href="/php/member/booking.php">Book a session</a>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Your trainer</h2>
  <?php if ($trainers): ?>
    <?php foreach ($trainers as $t): ?>
      <p><strong><?= htmlspecialchars($t['full_name']) ?></strong> — <?= htmlspecialchars($t['specialization']) ?></p>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="empty">No trainer assigned yet. The admin assigns trainers.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Workout plans</h2>
  <?php if ($workouts): ?>
    <p>You have <?= count($workouts) ?> plan(s).
       <a href="/php/member/workout.php">View them</a>.</p>
  <?php else: ?>
    <p class="empty">No workout plan assigned yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
