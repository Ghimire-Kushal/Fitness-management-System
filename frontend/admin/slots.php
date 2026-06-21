<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Admin');

// --- Add slot ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $slot_date  = $_POST['slot_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time   = $_POST['end_time'] ?? '';
    $capacity   = (int)($_POST['capacity'] ?? 0);

    if (!$slot_date || !$start_time || !$end_time || $capacity < 1) {
        set_flash('error', 'All fields are required and capacity must be at least 1.');
    } elseif ($start_time >= $end_time) {
        set_flash('error', 'End time must be after start time.');
    } elseif ($slot_date < date('Y-m-d')) {
        set_flash('error', 'Slot date cannot be in the past.');
    } else {
        db_exec(
            'INSERT INTO time_slots (slot_date, start_time, end_time, capacity) VALUES (?,?,?,?)',
            [$slot_date, $start_time, $end_time, $capacity]
        );
        set_flash('success', 'Time slot added.');
    }
    redirect_to('/admin/slots.php');
}

// --- Edit slot -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $slot_id    = (int)($_POST['slot_id'] ?? 0);
    $slot_date  = $_POST['slot_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time   = $_POST['end_time'] ?? '';
    $capacity   = (int)($_POST['capacity'] ?? 0);

    if (!$slot_date || !$start_time || !$end_time || $capacity < 1) {
        set_flash('error', 'All fields are required and capacity must be at least 1.');
    } elseif ($start_time >= $end_time) {
        set_flash('error', 'End time must be after start time.');
    } else {
        $booked = db_query('SELECT COUNT(*) AS c FROM bookings WHERE slot_id=?', [$slot_id], true)['c'];
        if ($capacity < $booked) {
            set_flash('error', "Cannot set capacity below current bookings ($booked).");
        } else {
            db_exec(
                'UPDATE time_slots SET slot_date=?, start_time=?, end_time=?, capacity=? WHERE slot_id=?',
                [$slot_date, $start_time, $end_time, $capacity, $slot_id]
            );
            set_flash('success', 'Slot updated.');
        }
    }
    redirect_to('/admin/slots.php');
}

// --- Delete slot ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    if ($slot_id > 0) {
        $booked = db_query('SELECT COUNT(*) AS c FROM bookings WHERE slot_id=?', [$slot_id], true)['c'];
        if ($booked > 0) {
            set_flash('error', 'Cannot delete a slot that has bookings. Cancel the bookings first.');
        } else {
            db_exec('DELETE FROM time_slots WHERE slot_id=?', [$slot_id]);
            set_flash('success', 'Slot deleted.');
        }
    }
    redirect_to('/admin/slots.php');
}

$upcoming = db_query(
    "SELECT s.*, (s.capacity - COUNT(b.booking_id)) AS remaining
     FROM time_slots s
     LEFT JOIN bookings b ON b.slot_id = s.slot_id
     WHERE s.slot_date >= CURDATE()
     GROUP BY s.slot_id ORDER BY s.slot_date, s.start_time"
);
$past = db_query(
    "SELECT s.*, COUNT(b.booking_id) AS bookings_count
     FROM time_slots s
     LEFT JOIN bookings b ON b.slot_id = s.slot_id
     WHERE s.slot_date < CURDATE()
     GROUP BY s.slot_id ORDER BY s.slot_date DESC, s.start_time
     LIMIT 10"
);

$editing = (int)($_GET['edit'] ?? 0);

$title = 'Time slots';
include __DIR__ . '/../includes/header.php';
?>

<h1>Time slots</h1>
<p class="subtitle">Create bookable time slots for gym sessions and trainer appointments.</p>

<div class="card">
  <h2>Add slot</h2>
  <form method="post" action="<?= htmlspecialchars(url_path('/admin/slots.php')) ?>">
    <input type="hidden" name="action" value="add">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:0 16px">
      <div>
        <label for="slot_date">Date</label>
        <input type="date" id="slot_date" name="slot_date"
               min="<?= date('Y-m-d') ?>" required>
      </div>
      <div>
        <label for="start_time">Start time</label>
        <input type="time" id="start_time" name="start_time" required>
      </div>
      <div>
        <label for="end_time">End time</label>
        <input type="time" id="end_time" name="end_time" required>
      </div>
      <div>
        <label for="capacity">Capacity</label>
        <input type="number" id="capacity" name="capacity" min="1" value="10" required>
      </div>
    </div>
    <button class="btn" type="submit">Add slot</button>
  </form>
</div>

<div class="card">
  <h2>Upcoming slots</h2>
  <?php if ($upcoming): ?>
    <table>
      <tr><th>Date</th><th>Start</th><th>End</th><th>Capacity</th><th>Remaining</th><th>Action</th></tr>
      <?php foreach ($upcoming as $s): ?>
      <?php $is_editing = $editing === (int)$s['slot_id']; ?>
      <?php if ($is_editing): ?>
      <tr style="background:#f7fafa">
        <td colspan="6">
          <form method="post" action="<?= htmlspecialchars(url_path('/admin/slots.php')) ?>"
                style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto auto;gap:0 12px;align-items:end">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="slot_id" value="<?= $s['slot_id'] ?>">
            <div>
              <label style="margin-top:0">Date</label>
              <input type="date" name="slot_date" value="<?= htmlspecialchars($s['slot_date']) ?>" required>
            </div>
            <div>
              <label style="margin-top:0">Start</label>
              <input type="time" name="start_time" value="<?= htmlspecialchars(substr($s['start_time'],0,5)) ?>" required>
            </div>
            <div>
              <label style="margin-top:0">End</label>
              <input type="time" name="end_time" value="<?= htmlspecialchars(substr($s['end_time'],0,5)) ?>" required>
            </div>
            <div>
              <label style="margin-top:0">Capacity</label>
              <input type="number" name="capacity" min="1" value="<?= (int)$s['capacity'] ?>" required>
            </div>
            <button class="btn btn-small" type="submit" style="margin:0">Save</button>
            <a class="btn btn-small btn-plain" href="<?= htmlspecialchars(url_path('/admin/slots.php')) ?>"
               style="margin:0;text-align:center">Cancel</a>
          </form>
        </td>
      </tr>
      <?php else: ?>
      <tr>
        <td><?= htmlspecialchars($s['slot_date']) ?></td>
        <td><?= htmlspecialchars($s['start_time']) ?></td>
        <td><?= htmlspecialchars($s['end_time']) ?></td>
        <td><?= (int)$s['capacity'] ?></td>
        <td>
          <span class="badge <?= $s['remaining'] <= 0 ? 'expired' : 'approved' ?>">
            <?= (int)$s['remaining'] ?> left
          </span>
        </td>
        <td>
          <div class="row-actions">
            <a class="btn btn-small btn-plain"
               href="<?= htmlspecialchars(url_path('/admin/slots.php?edit=' . $s['slot_id'])) ?>">Edit</a>
            <?php if ((int)$s['remaining'] === (int)$s['capacity']): ?>
              <form method="post" action="<?= htmlspecialchars(url_path('/admin/slots.php')) ?>"
                    onsubmit="return confirm('Delete this slot?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="slot_id" value="<?= $s['slot_id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <span class="muted">Has bookings</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">No upcoming slots. Add one above.</p>
  <?php endif; ?>
</div>

<?php if ($past): ?>
<div class="card">
  <h2>Past slots (last 10)</h2>
  <table>
    <tr><th>Date</th><th>Start</th><th>End</th><th>Capacity</th><th>Bookings</th></tr>
    <?php foreach ($past as $s): ?>
    <tr>
      <td class="muted"><?= htmlspecialchars($s['slot_date']) ?></td>
      <td class="muted"><?= htmlspecialchars($s['start_time']) ?></td>
      <td class="muted"><?= htmlspecialchars($s['end_time']) ?></td>
      <td class="muted"><?= (int)$s['capacity'] ?></td>
      <td class="muted"><?= (int)$s['bookings_count'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
