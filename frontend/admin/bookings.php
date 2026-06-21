<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, ['pending', 'approved', 'completed'])) {
        db_exec('UPDATE bookings SET status=? WHERE booking_id=?', [$new_status, $booking_id]);
        set_flash('success', 'Booking status updated.');
    }
    redirect_to('/admin/bookings.php');
}

$bookings = db_query(
    "SELECT b.*, mu.full_name AS member_name, tu.full_name AS trainer_name,
            s.slot_date, s.start_time, s.end_time
     FROM bookings b
     JOIN users mu ON b.member_id = mu.user_id
     JOIN time_slots s ON b.slot_id = s.slot_id
     LEFT JOIN trainer_profiles tp ON b.trainer_id = tp.trainer_id
     LEFT JOIN users tu ON tp.user_id = tu.user_id
     ORDER BY s.slot_date, s.start_time"
);

$title = 'Manage bookings';
include __DIR__ . '/../includes/header.php';
?>

<h1>Bookings</h1>
<p class="subtitle">Approve pending bookings and mark finished ones as completed.</p>

<div class="card">
  <?php if ($bookings): ?>
    <table>
      <tr><th>Member</th><th>Type</th><th>Trainer</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
      <?php foreach ($bookings as $b): ?>
      <tr>
        <td><?= htmlspecialchars($b['member_name']) ?></td>
        <td><?= $b['booking_type'] === 'appointment' ? 'Appointment' : 'Gym session' ?></td>
        <td><?= htmlspecialchars($b['trainer_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($b['slot_date']) ?></td>
        <td><?= htmlspecialchars($b['start_time']) ?> – <?= htmlspecialchars($b['end_time']) ?></td>
        <td><span class="badge <?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
        <td class="row-actions">
          <?php if ($b['status'] === 'pending'): ?>
            <form class="inline-form" method="post" action="<?= htmlspecialchars(url_path('/admin/bookings.php')) ?>">
              <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['booking_id']) ?>">
              <input type="hidden" name="status" value="approved">
              <button class="btn btn-small" type="submit">Approve</button>
            </form>
          <?php elseif ($b['status'] === 'approved'): ?>
            <form class="inline-form" method="post" action="<?= htmlspecialchars(url_path('/admin/bookings.php')) ?>">
              <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['booking_id']) ?>">
              <input type="hidden" name="status" value="completed">
              <button class="btn btn-small btn-plain" type="submit">Mark completed</button>
            </form>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No bookings to manage.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
