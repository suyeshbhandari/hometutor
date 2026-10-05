<?php
require_once 'includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'delete') {
        $n = db_run('DELETE FROM requests WHERE id = ?', 'i', array((int)post('request_id')));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Request deleted.' : 'Request not found.');
    }
    redirect('admin_requests.php');
}

$status = get_text('status');
if (!in_array($status, array('pending', 'accepted', 'rejected', 'cancelled', 'completed'), true)) $status = '';

$sql = "SELECT r.id, r.subject, r.preferred_date, r.preferred_time, r.status, r.created_at,
               s.name AS student_name, t.name AS tutor_name
        FROM requests r
        JOIN users s ON s.id = r.student_id
        JOIN users t ON t.id = r.tutor_id";
$types = '';
$params = array();
if ($status !== '') { $sql .= " WHERE r.status = ?"; $types = 's'; $params[] = $status; }
$sql .= " ORDER BY r.created_at DESC, r.id DESC";
$rows = db_all($sql, $types, $params);

$page_title = 'All Requests';
include 'includes/header.php';
?>
<h1>All booking requests</h1>

<form method="get" action="admin_requests.php" class="filter-bar">
  <select name="status">
    <option value="">All statuses</option>
    <?php foreach (array('pending', 'accepted', 'rejected', 'cancelled', 'completed') as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-outline" href="admin_requests.php">Reset</a>
</form>

<div class="card">
  <p class="muted"><?= count($rows) ?> request(s) shown.</p>
  <?php if ($rows): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>#</th><th>Student</th><th>Tutor</th><th>Subject</th><th>Date &amp; time</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['student_name']) ?></td>
        <td><?= e($r['tutor_name']) ?></td>
        <td><?= e($r['subject']) ?></td>
        <td><?= e(format_date($r['preferred_date'])) ?> <small><?= e(format_time($r['preferred_time'])) ?></small></td>
        <td><?= status_badge($r['status']) ?></td>
        <td>
          <form method="post" action="admin_requests.php" class="inline" onsubmit="return confirm('Delete this request permanently?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-small btn-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
