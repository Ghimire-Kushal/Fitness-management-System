<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('Member');

$uid = $_SESSION['user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = $_POST['plan_id'] ?? '';
    $plan    = db_query('SELECT * FROM membership_plans WHERE plan_id=?', [$plan_id], true);

    if (!$plan) {
        set_flash('error', 'Please choose a valid plan.');
        redirect_to('/member/membership.php');
    }

    $days  = $plan['duration_type'] === 'yearly' ? 365 : 30;
    $start = date('Y-m-d');
    $end   = date('Y-m-d', strtotime("+$days days"));

    db_exec("UPDATE memberships SET status='expired' WHERE user_id=? AND status='active'", [$uid]);
    db_exec(
        'INSERT INTO memberships (user_id, plan_id, start_date, end_date) VALUES (?,?,?,?)',
        [$uid, $plan_id, $start, $end]
    );

    set_flash('success', "You are now on the {$plan['plan_name']} plan.");
    redirect_to('/member/dashboard.php');
}

$plans   = db_query('SELECT * FROM membership_plans ORDER BY price');
$current = db_query(
    "SELECT m.*, p.plan_name FROM memberships m
     JOIN membership_plans p ON m.plan_id = p.plan_id
     WHERE m.user_id=? AND m.status='active' LIMIT 1",
    [$uid], true
);

$title = 'Membership';
include __DIR__ . '/../includes/header.php';
?>

<h1>Membership plans</h1>
<p class="subtitle">Pick the plan that suits you. You can change it later.</p>

<?php if ($current): ?>
  <div class="card">
    <h2>Current plan</h2>
    <p><strong><?= htmlspecialchars($current['plan_name']) ?></strong> &middot; valid until <?= htmlspecialchars($current['end_date']) ?></p>
  </div>
<?php endif; ?>

<div class="card">
  <h2>Choose a plan</h2>
  <form method="post" action="<?= htmlspecialchars(url_path('/member/membership.php')) ?>">
    <table>
      <tr><th></th><th>Plan</th><th>Type</th><th>Price</th><th>Includes</th></tr>
      <?php foreach ($plans as $i => $p): ?>
      <tr>
        <td><input type="radio" name="plan_id" value="<?= htmlspecialchars($p['plan_id']) ?>"
                   style="width:auto" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><strong><?= htmlspecialchars($p['plan_name']) ?></strong></td>
        <td><?= htmlspecialchars($p['duration_type']) ?></td>
        <td>Rs. <?= htmlspecialchars($p['price']) ?></td>
        <td class="muted"><?= htmlspecialchars($p['description'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <button class="btn" type="submit">Confirm plan</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
