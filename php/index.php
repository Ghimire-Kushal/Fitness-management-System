<?php
session_start();
require_once __DIR__ . '/db.php';
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
      <a class="btn" href="/php/admin/dashboard.php">Go to dashboard</a>
    <?php else: ?>
      <a class="btn" href="/php/member/dashboard.php">Go to dashboard</a>
    <?php endif; ?>
  <?php else: ?>
    <a class="btn" href="/php/register.php">Become a member</a>
    <a class="btn btn-plain" href="/php/login.php">Log in</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
