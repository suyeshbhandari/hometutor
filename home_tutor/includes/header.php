<?php
// Needs includes/functions.php to be loaded already. Set $page_title before including.
$u = current_user();
$flash = get_flash();
$badge_count = 0;
if ($u && $u['role'] === 'tutor') {
    $r = db_one("SELECT COUNT(*) AS c FROM requests WHERE tutor_id = ? AND status = 'pending'", 'i', array($u['id']));
    $badge_count = (int)$r['c'];
} elseif ($u && $u['role'] === 'admin') {
    $r = db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'tutor' AND status = 'pending'");
    $badge_count = (int)$r['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($page_title) ? $page_title . ' | ' : '') ?>Online Home Tutor</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php">&#127891; Home Tutor</a>
    <nav>
      <a href="index.php">Home</a>
      <a href="tutors.php">Find Tutors</a>
      <?php if ($u): ?>
        <a href="dashboard.php">Dashboard<?php if ($badge_count > 0): ?> <span class="count"><?= $badge_count ?></span><?php endif; ?></a>
        <?php if ($u['role'] === 'admin'): ?>
          <a href="admin_users.php">Users</a>
          <a href="admin_requests.php">Requests</a>
        <?php else: ?>
          <a href="profile.php">My Profile</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-small btn-outline-light">Logout (<?= e(explode(' ', $u['name'])[0]) ?>)</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php" class="btn btn-small btn-light">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container main">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
