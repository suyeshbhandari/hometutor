<?php
require_once 'includes/functions.php';
require_role('tutor');
$u = current_user();

$profile = db_one('SELECT subjects, location FROM tutor_profiles WHERE user_id = ?', 'i', array($u['id']));
$profile_done = ($profile && $profile['subjects'] !== '' && $profile['location'] !== '');

// ---------- actions: accept / reject / complete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $rid = (int)post('request_id');

    if ($u['status'] !== 'approved') {
        set_flash('error', 'Your account must be approved by the admin first.');
    } elseif ($action === 'accept') {
        $n = db_run("UPDATE requests SET status = 'accepted' WHERE id = ? AND tutor_id = ? AND status = 'pending'", 'ii', array($rid, $u['id']));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Request accepted.' : 'This request can no longer be accepted.');
    } elseif ($action === 'reject') {
        $n = db_run("UPDATE requests SET status = 'rejected' WHERE id = ? AND tutor_id = ? AND status = 'pending'", 'ii', array($rid, $u['id']));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Request rejected.' : 'This request can no longer be rejected.');
    } elseif ($action === 'complete') {
        $n = db_run("UPDATE requests SET status = 'completed' WHERE id = ? AND tutor_id = ? AND status = 'accepted'", 'ii', array($rid, $u['id']));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Session marked as completed.' : 'Only accepted sessions can be completed.');
    }
    redirect('tutor_dashboard.php');
}

$requests = db_all("SELECT r.id, r.subject, r.preferred_date, r.preferred_time, r.message, r.status,
                           s.name AS student_name, s.phone AS student_phone
                    FROM requests r
                    JOIN users s ON s.id = r.student_id
                    WHERE r.tutor_id = ?
                    ORDER BY (r.status = 'pending') DESC, r.preferred_date ASC, r.id DESC", 'i', array($u['id']));

$counts = array('pending' => 0, 'accepted' => 0, 'completed' => 0);
foreach ($requests as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}
$rating = db_one('SELECT COALESCE(AVG(rating), 0) AS a, COUNT(*) AS c FROM reviews WHERE tutor_id = ?', 'i', array($u['id']));

$page_title = 'Tutor Dashboard';
include 'includes/header.php';
?>
<h1>Welcome, <?= e($u['name']) ?></h1>

<?php if ($u['status'] === 'pending'): ?>
  <div class="alert alert-info">Your account is <strong>waiting for admin approval</strong>. Complete your profile so the admin can approve you. Students will see you after approval.</div>
<?php elseif ($u['status'] === 'rejected'): ?>
  <div class="alert alert-error">Your account was rejected by the admin.</div>
<?php endif; ?>
<?php if (!$profile_done): ?>
  <div class="alert alert-info">Your tutor profile is incomplete. <a href="profile.php">Complete it now</a> (subjects and location are required to appear in the tutor list).</div>
<?php endif; ?>

<div class="grid grid-4">
  <div class="stat"><span><?= $counts['pending'] ?></span>New requests</div>
  <div class="stat"><span><?= $counts['accepted'] ?></span>Accepted</div>
  <div class="stat"><span><?= $counts['completed'] ?></span>Completed</div>
  <div class="stat"><span><?= $rating['c'] > 0 ? number_format($rating['a'], 1) : '-' ?></span>Average rating</div>
</div>

<div class="card">
  <h2>Booking requests</h2>
  <?php if (!$requests): ?>
    <p class="muted">No requests yet.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Student</th><th>Subject</th><th>Date &amp; time</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td>
          <?= e($r['student_name']) ?>
          <?php if ($r['status'] === 'accepted' || $r['status'] === 'completed'): ?>
            <br><small class="muted">Phone: <?= e($r['student_phone']) ?></small>
          <?php endif; ?>
        </td>
        <td><?= e($r['subject']) ?></td>
        <td><?= e(format_date($r['preferred_date'])) ?><br><small><?= e(format_time($r['preferred_time'])) ?></small></td>
        <td><?= e($r['message']) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td>
          <?php if ($r['status'] === 'pending'): ?>
            <form method="post" action="tutor_dashboard.php" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-small btn-success" type="submit" name="action" value="accept">Accept</button>
              <button class="btn btn-small btn-danger" type="submit" name="action" value="reject">Reject</button>
            </form>
          <?php elseif ($r['status'] === 'accepted'): ?>
            <form method="post" action="tutor_dashboard.php" class="inline" onsubmit="return confirm('Mark this session as completed?');">
              <?= csrf_field() ?>
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-small btn-primary" type="submit" name="action" value="complete">Mark completed</button>
            </form>
          <?php else: ?>
            <small class="muted">&mdash;</small>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
