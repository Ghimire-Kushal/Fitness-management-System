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
  <link rel="stylesheet" href="/php/assets/style.css">
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a class="brand" href="/php/index.php">Iron<span>House</span></a>
    <div class="nav-links">
      <?php if ($user && $user['role'] === 'Member'): ?>
        <a href="/php/member/dashboard.php">Dashboard</a>
        <a href="/php/member/membership.php">Membership</a>
        <a href="/php/member/booking.php">Bookings</a>
        <a href="/php/member/workout.php">Workouts</a>
        <a href="/php/member/profile.php">Profile</a>
        <a href="/php/logout.php">Log out</a>
      <?php elseif ($user && $user['role'] === 'Admin'): ?>
        <a href="/php/admin/dashboard.php">Dashboard</a>
        <a href="/php/admin/users.php">Users</a>
        <a href="/php/admin/bookings.php">Bookings</a>
        <a href="/php/admin/trainers.php">Trainers</a>
        <a href="/php/admin/assign.php">Assign</a>
        <a href="/php/admin/workouts.php">Workouts</a>
        <a href="/php/logout.php">Log out</a>
      <?php else: ?>
        <a href="/php/login.php">Log in</a>
        <a href="/php/register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container <?= $narrow ? 'narrow' : '' ?>">
  <?php if ($flash): ?>
    <div class="flash <?= htmlspecialchars($flash[0]) ?>"><?= htmlspecialchars($flash[1]) ?></div>
  <?php endif; ?>
