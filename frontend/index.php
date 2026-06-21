<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/includes/auth.php';

$title = 'IronHouse Gym';
include __DIR__ . '/includes/header.php';
?>

<div class="hero">
  <h1>IronHouse Gym</h1>
  <p>Join the gym, pick a membership, book your sessions, and follow a
     workout plan made for you. Everything in one place.</p>
  <?php if ($user): ?>
    <?php if ($user['role'] === 'Admin'): ?>
      <a class="btn" href="<?= htmlspecialchars(url_path('/admin/dashboard.php')) ?>">Go to dashboard</a>
    <?php else: ?>
      <a class="btn" href="<?= htmlspecialchars(url_path('/member/dashboard.php')) ?>">Go to dashboard</a>
    <?php endif; ?>
  <?php else: ?>
    <a class="btn" href="<?= htmlspecialchars(url_path('/register.php')) ?>">Become a member</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
