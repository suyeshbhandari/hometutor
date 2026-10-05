<?php
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$tutor = db_one("SELECT u.id, u.name, tp.subjects, tp.qualification, tp.experience_years, tp.location,
                        tp.price_per_hour, tp.availability, tp.bio
                 FROM users u
                 JOIN tutor_profiles tp ON tp.user_id = u.id
                 WHERE u.id = ? AND u.role = 'tutor' AND u.status = 'approved' AND tp.subjects <> ''", 'i', array($id));

if (!$tutor) {
    set_flash('error', 'That tutor was not found or is not available.');
    redirect('tutors.php');
}

$u = current_user();
$can_book = ($u !== null && $u['role'] === 'student');
$subject_list = array_values(array_filter(array_map('trim', explode(',', $tutor['subjects'])), 'strlen'));

$errors = array();
$old = array('subject' => '', 'date' => '', 'time' => '', 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$can_book) {
        set_flash('error', 'Please login as a student to send a request.');
        redirect('login.php');
    }

    $old['subject'] = post('subject');
    $old['date']    = post('date');
    $old['time']    = post('time');
    $old['message'] = post('message');

    if (!in_array($old['subject'], $subject_list, true)) {
        $errors['subject'] = 'Please choose one of the tutor\'s subjects.';
    }

    $d = DateTime::createFromFormat('Y-m-d', $old['date']);
    if (!$d || $d->format('Y-m-d') !== $old['date']) {
        $errors['date'] = 'Please choose a valid date.';
    } elseif ($old['date'] < date('Y-m-d')) {
        $errors['date'] = 'The date cannot be in the past.';
    }

    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $old['time'])) {
        $errors['time'] = 'Please choose a valid time.';
    } elseif (!isset($errors['date']) && strtotime($old['date'] . ' ' . $old['time']) < time()) {
        $errors['time'] = 'The date and time must be in the future.';
    }

    if (strlen($old['message']) > 300) {
        $errors['message'] = 'Message must be 300 characters or fewer.';
    }

    if (!$errors) {
        $dup = db_one("SELECT id FROM requests
                       WHERE student_id = ? AND tutor_id = ? AND subject = ? AND preferred_date = ?
                         AND status IN ('pending', 'accepted')",
                      'iiss', array($u['id'], $tutor['id'], $old['subject'], $old['date']));
        if ($dup) {
            $errors['general'] = 'You already have an active request with this tutor for that subject and date.';
        }
    }

    if (!$errors) {
        db_run('INSERT INTO requests (tutor_id, student_id, subject, preferred_date, preferred_time, message) VALUES (?, ?, ?, ?, ?, ?)',
               'iissss', array($tutor['id'], $u['id'], $old['subject'], $old['date'], $old['time'] . ':00', $old['message']));
        set_flash('success', 'Your request was sent to ' . $tutor['name'] . '. You can track it from your dashboard.');
        redirect('student_dashboard.php');
    }
}

$reviews = db_all("SELECT r.rating, r.comment, r.created_at, s.name AS student_name
                   FROM reviews r JOIN users s ON s.id = r.student_id
                   WHERE r.tutor_id = ? ORDER BY r.created_at DESC", 'i', array($tutor['id']));
$avg = 0;
if ($reviews) {
    $sum = 0;
    foreach ($reviews as $rv) $sum += (int)$rv['rating'];
    $avg = $sum / count($reviews);
}

$page_title = $tutor['name'];
include 'includes/header.php';
?>
<div class="two-col">
  <div>
    <div class="card">
      <h1><?= e($tutor['name']) ?></h1>
      <p class="rating"><?= $reviews ? stars($avg) . ' ' . number_format($avg, 1) . ' (' . count($reviews) . ' review(s))' : 'No reviews yet' ?></p>
      <table class="info">
        <tr><th>Subjects</th><td><?= e($tutor['subjects']) ?></td></tr>
        <tr><th>Qualification</th><td><?= e($tutor['qualification']) ?></td></tr>
        <tr><th>Experience</th><td><?= (int)$tutor['experience_years'] ?> year(s)</td></tr>
        <tr><th>Location</th><td><?= e($tutor['location']) ?></td></tr>
        <tr><th>Fee</th><td>Rs. <?= (int)$tutor['price_per_hour'] ?> / hour</td></tr>
        <tr><th>Availability</th><td><?= e($tutor['availability']) ?></td></tr>
      </table>
      <?php if ($tutor['bio'] !== null && $tutor['bio'] !== ''): ?>
        <h3>About</h3>
        <p><?= nl2br(e($tutor['bio'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Reviews</h2>
      <?php if (!$reviews): ?>
        <p class="muted">No reviews yet.</p>
      <?php else: foreach ($reviews as $rv): ?>
        <div class="review">
          <strong><?= e($rv['student_name']) ?></strong> <span class="rating"><?= stars($rv['rating']) ?></span>
          <small class="muted"><?= e(format_date($rv['created_at'])) ?></small>
          <?php if ($rv['comment'] !== null && $rv['comment'] !== ''): ?><p><?= e($rv['comment']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <h2>Send a booking request</h2>
      <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <?php if ($u === null): ?>
        <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> as a student to book this tutor.</p>
      <?php elseif (!$can_book): ?>
        <p class="muted">Only student/parent accounts can send booking requests.</p>
      <?php else: ?>
        <form method="post" action="tutor_view.php?id=<?= (int)$tutor['id'] ?>" novalidate>
          <?= csrf_field() ?>
          <div class="field">
            <label for="subject">Subject</label>
            <select id="subject" name="subject" class="<?= isset($errors['subject']) ? 'invalid' : '' ?>">
              <option value="">-- choose --</option>
              <?php foreach ($subject_list as $s): ?>
                <option value="<?= e($s) ?>" <?= $old['subject'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="field-error"><?= e(isset($errors['subject']) ? $errors['subject'] : '') ?></small>
          </div>
          <div class="field">
            <label for="date">Preferred date</label>
            <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= e($old['date']) ?>" class="<?= isset($errors['date']) ? 'invalid' : '' ?>">
            <small class="field-error"><?= e(isset($errors['date']) ? $errors['date'] : '') ?></small>
          </div>
          <div class="field">
            <label for="time">Preferred time</label>
            <input type="time" id="time" name="time" value="<?= e($old['time']) ?>" class="<?= isset($errors['time']) ? 'invalid' : '' ?>">
            <small class="field-error"><?= e(isset($errors['time']) ? $errors['time'] : '') ?></small>
          </div>
          <div class="field">
            <label for="message">Message to tutor (optional)</label>
            <textarea id="message" name="message" rows="3" maxlength="300" class="<?= isset($errors['message']) ? 'invalid' : '' ?>"><?= e($old['message']) ?></textarea>
            <small class="field-error"><?= e(isset($errors['message']) ? $errors['message'] : '') ?></small>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send request</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
