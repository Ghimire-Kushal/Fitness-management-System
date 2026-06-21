<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

$stats = [
    'members'            => db_query("SELECT COUNT(*) AS c FROM users u JOIN roles r ON u.role_id=r.role_id WHERE r.role_name='Member'", [], true)['c'],
    'trainers'           => db_query("SELECT COUNT(*) AS c FROM trainer_profiles", [], true)['c'],
    'bookings'           => db_query("SELECT COUNT(*) AS c FROM bookings", [], true)['c'],
    'pending'            => db_query("SELECT COUNT(*) AS c FROM bookings WHERE status='pending'", [], true)['c'],
    'active_memberships' => db_query("SELECT COUNT(*) AS c FROM memberships WHERE status='active'", [], true)['c'],
];
$recent = db_query(
    "SELECT b.booking_id, b.status, b.booking_type,
            mu.full_name AS member_name, s.slot_date, s.start_time
     FROM bookings b
     JOIN users mu ON b.member_id = mu.user_id
     JOIN time_slots s ON b.slot_id = s.slot_id
     ORDER BY b.created_at DESC LIMIT 5"
);

$title = 'Admin dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1>Admin dashboard</h1>
<p class="subtitle">An overview of the gym.</p>

<div class="stats">
  <div class="stat"><div class="num"><?= $stats['members'] ?></div><div class="lbl">Members</div></div>
  <div class="stat"><div class="num"><?= $stats['trainers'] ?></div><div class="lbl">Trainers</div></div>
  <div class="stat"><div class="num"><?= $stats['bookings'] ?></div><div class="lbl">Total bookings</div></div>
  <div class="stat"><div class="num"><?= $stats['pending'] ?></div><div class="lbl">Pending bookings</div></div>
  <div class="stat"><div class="num"><?= $stats['active_memberships'] ?></div><div class="lbl">Active memberships</div></div>
</div>

<div class="card" style="margin-top:20px">
  <h2>Recent bookings</h2>
  <?php if ($recent): ?>
    <table>
      <tr><th>Member</th><th>Type</th><th>Date</th><th>Status</th></tr>
      <?php foreach ($recent as $b): ?>
      <tr>
        <td><?= htmlspecialchars($b['member_name']) ?></td>
        <td><?= $b['booking_type'] === 'appointment' ? 'Appointment' : 'Gym session' ?></td>
        <td><?= htmlspecialchars($b['slot_date']) ?> <?= htmlspecialchars($b['start_time']) ?></td>
        <td><span class="badge <?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p style="margin-top:14px"><a href="/php/admin/bookings.php">Manage all bookings</a></p>
  <?php else: ?>
    <p class="empty">No bookings yet.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
