<?php
require_once 'includes/functions.php';
require_role('admin');

// ---------- actions: approve / reject / delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $uid = (int)post('user_id');

    if ($action === 'approve') {
        $n = db_run("UPDATE users SET status = 'approved' WHERE id = ? AND role <> 'admin'", 'i', array($uid));
        set_flash('success', $n > 0 ? 'User approved.' : 'No change made (already approved or not allowed).');
    } elseif ($action === 'reject') {
        $n = db_run("UPDATE users SET status = 'rejected' WHERE id = ? AND role <> 'admin'", 'i', array($uid));
        set_flash('success', $n > 0 ? 'User rejected. They can no longer login.' : 'No change made (already rejected or not allowed).');
    } elseif ($action === 'delete') {
        $n = db_run("DELETE FROM users WHERE id = ? AND role <> 'admin'", 'i', array($uid));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'User and all related data deleted.' : 'That user cannot be deleted.');
    }
    redirect('admin_users.php');
}

$role   = get_text('role');
$status = get_text('status');
if (!in_array($role, array('student', 'tutor'), true)) $role = '';
if (!in_array($status, array('pending', 'approved', 'rejected'), true)) $status = '';

$sql = "SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at,
               tp.subjects, tp.qualification, tp.location
        FROM users u LEFT JOIN tutor_profiles tp ON tp.user_id = u.id
        WHERE u.role <> 'admin'";
$types = '';
$params = array();
if ($role !== '')   { $sql .= " AND u.role = ?";   $types .= 's'; $params[] = $role; }
if ($status !== '') { $sql .= " AND u.status = ?"; $types .= 's'; $params[] = $status; }
$sql .= " ORDER BY u.created_at DESC, u.id DESC";
$users = db_all($sql, $types, $params);

$page_title = 'Manage Users';
include 'includes/header.php';
?>
<h1>Manage users</h1>

<form method="get" action="admin_users.php" class="filter-bar">
  <select name="role">
    <option value="">All roles</option>
    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option>
    <option value="tutor" <?= $role === 'tutor' ? 'selected' : '' ?>>Tutors</option>
  </select>
  <select name="status">
    <option value="">All statuses</option>
    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-outline" href="admin_users.php">Reset</a>
</form>

<div class="card">
  <p class="muted"><?= count($users) ?> user(s) shown.</p>
  <?php if ($users): ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $x): ?>
      <tr>
        <td><?= e($x['name']) ?><br><small class="muted">Joined <?= e(format_date($x['created_at'])) ?></small></td>
        <td><?= e($x['email']) ?><br><small><?= e($x['phone']) ?></small></td>
        <td><?= e(ucfirst($x['role'])) ?></td>
        <td>
          <?php if ($x['role'] === 'tutor'): ?>
            <?= e($x['subjects'] !== null && $x['subjects'] !== '' ? $x['subjects'] : '(profile not filled)') ?><br>
            <small class="muted"><?= e($x['qualification']) ?><?= $x['location'] ? ' · ' . e($x['location']) : '' ?></small>
          <?php else: ?>
            <small class="muted">&mdash;</small>
          <?php endif; ?>
        </td>
        <td><?= status_badge($x['status']) ?></td>
        <td>
          <form method="post" action="admin_users.php" class="inline" onsubmit="return confirm('Are you sure?');">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
            <?php if ($x['status'] !== 'approved'): ?>
              <button class="btn btn-small btn-success" type="submit" name="action" value="approve">Approve</button>
            <?php endif; ?>
            <?php if ($x['status'] !== 'rejected'): ?>
              <button class="btn btn-small btn-outline" type="submit" name="action" value="reject">Reject</button>
            <?php endif; ?>
            <button class="btn btn-small btn-danger" type="submit" name="action" value="delete">Delete</button>
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
