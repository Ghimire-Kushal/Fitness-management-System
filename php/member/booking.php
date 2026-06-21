<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Member');

$uid = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_id      = $_POST['slot_id'] ?? '';
    $booking_type = $_POST['booking_type'] ?? '';
    $trainer_id   = ($_POST['trainer_id'] ?? '') ?: null;

    $slot = db_query('SELECT * FROM time_slots WHERE slot_id=?', [$slot_id], true);
    if (!$slot || !in_array($booking_type, ['gym_session', 'appointment'])) {
        set_flash('error', 'Please choose a valid slot and booking type.');
        header('Location: /php/member/booking.php');
        exit;
    }

    $dup = db_query(
        'SELECT booking_id FROM bookings WHERE member_id=? AND slot_id=? AND booking_type=?',
        [$uid, $slot_id, $booking_type], true
    );
    if ($dup) {
        set_flash('error', 'You have already booked that slot.');
        header('Location: /php/member/booking.php');
        exit;
    }

    $taken = db_query('SELECT COUNT(*) AS c FROM bookings WHERE slot_id=?', [$slot_id], true)['c'];
    if ($taken >= $slot['capacity']) {
        set_flash('error', 'That slot is full. Please pick another time.');
        header('Location: /php/member/booking.php');
        exit;
    }

    if ($booking_type === 'appointment') {
        $assigned = db_query(
            'SELECT 1 FROM member_trainers WHERE member_id=? AND trainer_id=?',
            [$uid, $trainer_id], true
        );
        if (!$assigned) {
            set_flash('error', 'You can only book a trainer assigned to you by the admin.');
            header('Location: /php/member/booking.php');
            exit;
        }
        $busy = db_query(
            "SELECT COUNT(*) AS c FROM bookings WHERE slot_id=? AND trainer_id=? AND booking_type='appointment'",
            [$slot_id, $trainer_id], true
        )['c'];
        if ($busy >= 1) {
            set_flash('error', 'That trainer is already booked for this slot.');
            header('Location: /php/member/booking.php');
            exit;
        }
    } else {
        $trainer_id = null;
    }

    db_exec(
        'INSERT INTO bookings (member_id, slot_id, trainer_id, booking_type) VALUES (?,?,?,?)',
        [$uid, $slot_id, $trainer_id, $booking_type]
    );
    set_flash('success', 'Booking requested. It is now pending approval.');
    header('Location: /php/member/booking.php');
    exit;
}

$slots = db_query(
    "SELECT s.*, (s.capacity - COUNT(b.booking_id)) AS remaining
     FROM time_slots s
     LEFT JOIN bookings b ON b.slot_id = s.slot_id
     WHERE s.slot_date >= CURDATE()
     GROUP BY s.slot_id
     ORDER BY s.slot_date, s.start_time"
);
$trainers = db_query(
    "SELECT tp.trainer_id, tu.full_name, tp.specialization
     FROM member_trainers mt
     JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
     JOIN users tu ON tp.user_id = tu.user_id
     WHERE mt.member_id=?",
    [$uid]
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

$title = 'Bookings';
include __DIR__ . '/../includes/header.php';
?>

<h1>Book a session</h1>
<p class="subtitle">Reserve a gym session, or an appointment with your assigned trainer.</p>

<div class="card">
  <h2>New booking</h2>
  <form method="post" action="/php/member/booking.php">
    <label for="booking_type">What are you booking?</label>
    <select id="booking_type" name="booking_type" onchange="toggleTrainer()">
      <option value="gym_session">Gym session</option>
      <option value="appointment">Trainer appointment</option>
    </select>

    <div id="trainer_field" style="display:none">
      <label for="trainer_id">Trainer</label>
      <?php if ($trainers): ?>
        <select id="trainer_id" name="trainer_id">
          <?php foreach ($trainers as $t): ?>
            <option value="<?= htmlspecialchars($t['trainer_id']) ?>">
              <?= htmlspecialchars($t['full_name']) ?> — <?= htmlspecialchars($t['specialization']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <p class="muted">No trainer is assigned to you yet. The admin assigns trainers.</p>
      <?php endif; ?>
    </div>

    <label for="slot_id">Time slot</label>
    <select id="slot_id" name="slot_id" required>
      <?php foreach ($slots as $s): ?>
        <option value="<?= htmlspecialchars($s['slot_id']) ?>"
                <?= $s['remaining'] <= 0 ? 'disabled' : '' ?>>
          <?= htmlspecialchars($s['slot_date']) ?> &middot;
          <?= htmlspecialchars($s['start_time']) ?>–<?= htmlspecialchars($s['end_time']) ?>
          (<?= (int)$s['remaining'] ?> left)
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">Request booking</button>
  </form>
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
        <td><?= $b['booking_type'] === 'appointment' ? 'Appointment' : 'Gym session' ?></td>
        <td><?= htmlspecialchars($b['trainer_name'] ?? '—') ?></td>
        <td><span class="badge <?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="empty">You have not booked anything yet.</p>
  <?php endif; ?>
</div>

<script>
  function toggleTrainer() {
    var type = document.getElementById('booking_type').value;
    document.getElementById('trainer_field').style.display =
      (type === 'appointment') ? 'block' : 'none';
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
