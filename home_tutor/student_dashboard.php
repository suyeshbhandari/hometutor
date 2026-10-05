<?php
require_once 'includes/functions.php';
require_role('student');
$u = current_user();

// ---------- actions: cancel / delete / review ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $rid = (int)post('request_id');

    if ($action === 'cancel') {
        $n = db_run("UPDATE requests SET status = 'cancelled' WHERE id = ? AND student_id = ? AND status IN ('pending', 'accepted')",
                    'ii', array($rid, $u['id']));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Request cancelled.' : 'This request cannot be cancelled.');

    } elseif ($action === 'delete') {
        $n = db_run("DELETE FROM requests WHERE id = ? AND student_id = ? AND status IN ('cancelled', 'rejected')",
                    'ii', array($rid, $u['id']));
        set_flash($n > 0 ? 'success' : 'error', $n > 0 ? 'Request deleted.' : 'This request cannot be deleted.');

    } elseif ($action === 'review') {
        $rating = (int)post('rating');
        $comment = post('comment');
        $req = db_one("SELECT id, tutor_id FROM requests WHERE id = ? AND student_id = ? AND status = 'completed'",
                      'ii', array($rid, $u['id']));
        $already = $req ? db_one('SELECT id FROM reviews WHERE request_id = ?', 'i', array($rid)) : null;

        if (!$req) {
            set_flash('error', 'You can only review a completed session.');
        } elseif ($already) {
            set_flash('error', 'You have already reviewed this session.');
        } elseif ($rating < 1 || $rating > 5) {
            set_flash('error', 'Please choose a rating from 1 to 5.');
        } elseif (strlen($comment) > 300) {
            set_flash('error', 'Comment must be 300 characters or fewer.');
        } else {
            db_run('INSERT INTO reviews (request_id, tutor_id, student_id, rating, comment) VALUES (?, ?, ?, ?, ?)',
                   'iiiis', array($rid, $req['tutor_id'], $u['id'], $rating, $comment));
            set_flash('success', 'Thank you! Your review was saved.');
        }
    }
    redirect('student_dashboard.php');
}

$requests = db_all("SELECT r.id, r.subject, r.preferred_date, r.preferred_time, r.message, r.status,
                           t.id AS tutor_id, t.name AS tutor_name, t.phone AS tutor_phone,
                           rv.id AS review_id
                    FROM requests r
                    JOIN users t ON t.id = r.tutor_id
                    LEFT JOIN reviews rv ON rv.request_id = r.id
                    WHERE r.student_id = ?
                    ORDER BY r.created_at DESC, r.id DESC", 'i', array($u['id']));

$counts = array('pending' => 0, 'accepted' => 0, 'completed' => 0);
$upcoming = array();
$today = date('Y-m-d');
foreach ($requests as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
    if ($r['status'] === 'accepted' && $r['preferred_date'] >= $today) $upcoming[] = $r;
}

$page_title = 'My Dashboard';
include 'includes/header.php';
?>
<h1>Welcome, <?= e($u['name']) ?></h1>

<div class="grid grid-3">
  <div class="stat"><span><?= $counts['pending'] ?></span>Pending requests</div>
  <div class="stat"><span><?= $counts['accepted'] ?></span>Accepted sessions</div>
  <div class="stat"><span><?= $counts['completed'] ?></span>Completed sessions</div>
</div>

<?php if ($upcoming): ?>
  <div class="alert alert-info">
    <strong>Reminder &mdash; upcoming accepted session(s):</strong>
    <ul>
      <?php foreach ($upcoming as $r): ?>
        <li><?= e($r['subject']) ?> with <?= e($r['tutor_name']) ?> on <?= e(format_date($r['preferred_date'])) ?> at <?= e(format_time($r['preferred_time'])) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="row-between">
    <h2>My requests</h2>
    <a class="btn btn-primary btn-small" href="tutors.php">+ Find a tutor</a>
  </div>
  <?php if (!$requests): ?>
    <p class="muted">You have not sent any request yet.</p>
  <?php else: ?>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>Tutor</th><th>Subject</th><th>Date &amp; time</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td>
          <a href="tutor_view.php?id=<?= (int)$r['tutor_id'] ?>"><?= e($r['tutor_name']) ?></a>
          <?php if ($r['status'] === 'accepted' || $r['status'] === 'completed'): ?>
            <br><small class="muted">Phone: <?= e($r['tutor_phone']) ?></small>
          <?php endif; ?>
        </td>
        <td><?= e($r['subject']) ?></td>
        <td><?= e(format_date($r['preferred_date'])) ?><br><small><?= e(format_time($r['preferred_time'])) ?></small></td>
        <td><?= status_badge($r['status']) ?></td>
        <td>
          <?php if ($r['status'] === 'pending' || $r['status'] === 'accepted'): ?>
            <form method="post" action="student_dashboard.php" class="inline" onsubmit="return confirm('Cancel this request?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-small btn-danger" type="submit">Cancel</button>
            </form>
          <?php elseif ($r['status'] === 'cancelled' || $r['status'] === 'rejected'): ?>
            <form method="post" action="student_dashboard.php" class="inline" onsubmit="return confirm('Delete this request permanently?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-small btn-outline" type="submit">Delete</button>
            </form>
          <?php elseif ($r['status'] === 'completed' && !$r['review_id']): ?>
            <form method="post" action="student_dashboard.php" class="review-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="review">
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <select name="rating" required>
                <option value="">Rate</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Bad</option>
              </select>
              <input type="text" name="comment" maxlength="300" placeholder="Comment (optional)">
              <button class="btn btn-small btn-primary" type="submit">Review</button>
            </form>
          <?php elseif ($r['status'] === 'completed'): ?>
            <small class="muted">Reviewed &#10003;</small>
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
