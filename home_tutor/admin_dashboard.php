<?php
require_once 'includes/functions.php';
require_role('admin');

function count_of($sql, $types = '', $params = array()) {
    $r = db_one($sql, $types, $params);
    return (int)$r['c'];
}

$total_students = count_of("SELECT COUNT(*) AS c FROM users WHERE role = 'student'");
$total_tutors   = count_of("SELECT COUNT(*) AS c FROM users WHERE role = 'tutor'");
$pending_tutors = count_of("SELECT COUNT(*) AS c FROM users WHERE role = 'tutor' AND status = 'pending'");
$total_requests = count_of("SELECT COUNT(*) AS c FROM requests");
$by_status = array();
foreach (array('pending', 'accepted', 'rejected', 'cancelled', 'completed') as $s) {
    $by_status[$s] = count_of("SELECT COUNT(*) AS c FROM requests WHERE status = ?", 's', array($s));
}
$total_reviews = count_of("SELECT COUNT(*) AS c FROM reviews");

$waiting = db_all("SELECT u.id, u.name, u.email, u.phone, u.created_at, tp.subjects, tp.qualification, tp.location
                   FROM users u LEFT JOIN tutor_profiles tp ON tp.user_id = u.id
                   WHERE u.role = 'tutor' AND u.status = 'pending' ORDER BY u.created_at ASC");

$recent = db_all("SELECT r.id, r.subject, r.preferred_date, r.status, s.name AS student_name, t.name AS tutor_name
                  FROM requests r JOIN users s ON s.id = r.student_id JOIN users t ON t.id = r.tutor_id
                  ORDER BY r.created_at DESC, r.id DESC LIMIT 5");

$page_title = 'Admin Dashboard';
include 'includes/header.php';
?>
<div class="row-between">
  <h1>Admin dashboard</h1>
  <button class="btn btn-outline no-print" type="button" onclick="window.print()">Print report</button>
</div>

<div class="grid grid-4">
  <div class="stat"><span><?= $total_students ?></span>Students</div>
  <div class="stat"><span><?= $total_tutors ?></span>Tutors (<?= $pending_tutors ?> waiting)</div>
  <div class="stat"><span><?= $total_requests ?></span>Booking requests</div>
  <div class="stat"><span><?= $total_reviews ?></span>Reviews</div>
</div>

<div class="card">
  <h2>Tutors waiting for approval</h2>
  <?php if (!$waiting): ?>
    <p class="muted">No tutors are waiting for approval.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Name</th><th>Contact</th><th>Subjects</th><th>Qualification</th><th>Location</th><th class="no-print">Action</th></tr></thead>
    <tbody>
    <?php foreach ($waiting as $w): ?>
      <tr>
        <td><?= e($w['name']) ?></td>
        <td><?= e($w['email']) ?><br><small><?= e($w['phone']) ?></small></td>
        <td><?= e($w['subjects'] !== null && $w['subjects'] !== '' ? $w['subjects'] : '(profile not filled)') ?></td>
        <td><?= e($w['qualification']) ?></td>
        <td><?= e($w['location']) ?></td>
        <td class="no-print">
          <form method="post" action="admin_users.php" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$w['id'] ?>">
            <button class="btn btn-small btn-success" type="submit" name="action" value="approve">Approve</button>
            <button class="btn btn-small btn-danger" type="submit" name="action" value="reject">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Requests by status</h2>
    <table class="table">
      <?php foreach ($by_status as $s => $c): ?>
        <tr><td><?= status_badge($s) ?></td><td><strong><?= $c ?></strong></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="card">
    <h2>Latest requests</h2>
    <?php if (!$recent): ?>
      <p class="muted">No requests yet.</p>
    <?php else: ?>
      <table class="table">
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e($r['student_name']) ?> &rarr; <?= e($r['tutor_name']) ?><br><small class="muted"><?= e($r['subject']) ?>, <?= e(format_date($r['preferred_date'])) ?></small></td>
            <td><?= status_badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
