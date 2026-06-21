<?php
// Called at the top of every page — pass $title and optional $narrow=true
$user  = current_user();
$flash = get_flash();
$narrow = $narrow ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Gym Management') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(url_path('/static/css/style.css')) ?>">
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a class="brand" href="<?= htmlspecialchars(url_path('/index.php')) ?>">Iron<span>House</span></a>
    <div class="nav-links">
      <?php if ($user && $user['role'] === 'Member'): ?>
        <a href="<?= htmlspecialchars(url_path('/member/dashboard.php')) ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url_path('/member/membership.php')) ?>">Membership</a>
        <a href="<?= htmlspecialchars(url_path('/member/booking.php')) ?>">Bookings</a>
        <a href="<?= htmlspecialchars(url_path('/member/workout.php')) ?>">Workouts</a>
        <a href="<?= htmlspecialchars(url_path('/member/profile.php')) ?>">Profile</a>
        <a href="<?= htmlspecialchars(url_path('/logout.php')) ?>">Log out</a>
      <?php elseif ($user && $user['role'] === 'Admin'): ?>
        <a href="<?= htmlspecialchars(url_path('/admin/dashboard.php')) ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(url_path('/admin/users.php')) ?>">Users</a>
        <a href="<?= htmlspecialchars(url_path('/admin/bookings.php')) ?>">Bookings</a>
        <a href="<?= htmlspecialchars(url_path('/admin/trainers.php')) ?>">Trainers</a>
        <a href="<?= htmlspecialchars(url_path('/admin/assign.php')) ?>">Assign</a>
        <a href="<?= htmlspecialchars(url_path('/admin/workouts.php')) ?>">Workouts</a>
        <a href="<?= htmlspecialchars(url_path('/admin/slots.php')) ?>">Slots</a>
        <a href="<?= htmlspecialchars(url_path('/admin/plans.php')) ?>">Plans</a>
        <a href="<?= htmlspecialchars(url_path('/logout.php')) ?>">Log out</a>
      <?php else: ?>
        <a href="<?= htmlspecialchars(url_path('/login.php')) ?>">Log in</a>
        <a href="<?= htmlspecialchars(url_path('/register.php')) ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container <?= $narrow ? 'narrow' : '' ?>">
  <?php if ($flash): ?>
    <div class="flash <?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
  <?php endif; ?>
