<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
if ($u['role'] === 'admin') {
    redirect('admin_dashboard.php');
}
$is_tutor = ($u['role'] === 'tutor');

$errors = array();
$tp = $is_tutor ? db_one('SELECT * FROM tutor_profiles WHERE user_id = ?', 'i', array($u['id'])) : null;
if ($is_tutor && !$tp) {
    // safety: create the missing row
    db_run('INSERT INTO tutor_profiles (user_id) VALUES (?)', 'i', array($u['id']));
    $tp = db_one('SELECT * FROM tutor_profiles WHERE user_id = ?', 'i', array($u['id']));
}

$old = array(
    'name' => $u['name'], 'phone' => $u['phone'],
    'subjects' => $tp ? $tp['subjects'] : '', 'qualification' => $tp ? $tp['qualification'] : '',
    'experience_years' => $tp ? (string)$tp['experience_years'] : '0', 'location' => $tp ? $tp['location'] : '',
    'price_per_hour' => $tp ? (string)$tp['price_per_hour'] : '0', 'availability' => $tp ? $tp['availability'] : '',
    'bio' => $tp ? (string)$tp['bio'] : ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (array_keys($old) as $k) {
        if (!$is_tutor && !in_array($k, array('name', 'phone'), true)) continue;
        $old[$k] = post($k);
    }
    $current_pw = post_raw('current_password');
    $new_pw     = post_raw('new_password');
    $confirm_pw = post_raw('confirm_password');

    if (($m = validate_name($old['name'])) !== '')   $errors['name'] = $m;
    if (($m = validate_phone($old['phone'])) !== '') $errors['phone'] = $m;

    if ($is_tutor) {
        if ($old['subjects'] === '') {
            $errors['subjects'] = 'Enter at least one subject (separate with commas).';
        } elseif (strlen($old['subjects']) > 200) {
            $errors['subjects'] = 'Subjects must be 200 characters or fewer.';
        }
        if ($old['qualification'] === '' || strlen($old['qualification']) > 100) {
            $errors['qualification'] = 'Qualification is required (max 100 characters).';
        }
        if (!preg_match('/^[0-9]{1,2}$/', $old['experience_years'])) {
            $errors['experience_years'] = 'Enter experience in whole years (0 to 50).';
        } elseif ((int)$old['experience_years'] > 50) {
            $errors['experience_years'] = 'Experience cannot be more than 50 years.';
        }
        if ($old['location'] === '' || strlen($old['location']) > 100) {
            $errors['location'] = 'Location is required (max 100 characters).';
        }
        if (!preg_match('/^[0-9]{1,5}$/', $old['price_per_hour']) || (int)$old['price_per_hour'] < 1) {
            $errors['price_per_hour'] = 'Enter the hourly fee in rupees (1 to 99999).';
        }
        if ($old['availability'] === '' || strlen($old['availability']) > 150) {
            $errors['availability'] = 'Availability is required (max 150 characters).';
        }
        if (strlen($old['bio']) > 1000) {
            $errors['bio'] = 'About must be 1000 characters or fewer.';
        }
    }

    // optional password change
    $change_pw = ($current_pw !== '' || $new_pw !== '' || $confirm_pw !== '');
    if ($change_pw) {
        $row = db_one('SELECT password FROM users WHERE id = ?', 'i', array($u['id']));
        if (!password_verify($current_pw, $row['password'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
        if (($m = validate_password($new_pw)) !== '') {
            $errors['new_password'] = $m;
        } elseif ($new_pw !== $confirm_pw) {
            $errors['confirm_password'] = 'New passwords do not match.';
        }
    }

    if (!$errors) {
        db_run('UPDATE users SET name = ?, phone = ? WHERE id = ?', 'ssi', array($old['name'], $old['phone'], $u['id']));
        if ($is_tutor) {
            db_run('UPDATE tutor_profiles SET subjects = ?, qualification = ?, experience_years = ?, location = ?, price_per_hour = ?, availability = ?, bio = ? WHERE user_id = ?',
                   'ssisissi', array($old['subjects'], $old['qualification'], (int)$old['experience_years'], $old['location'],
                                     (int)$old['price_per_hour'], $old['availability'], $old['bio'], $u['id']));
        }
        if ($change_pw) {
            db_run('UPDATE users SET password = ? WHERE id = ?', 'si', array(password_hash($new_pw, PASSWORD_DEFAULT), $u['id']));
        }
        set_flash('success', 'Profile updated successfully.');
        redirect('profile.php');
    }
}

function field_err($errors, $k) { return e(isset($errors[$k]) ? $errors[$k] : ''); }
function inv($errors, $k) { return isset($errors[$k]) ? 'invalid' : ''; }

$page_title = 'My Profile';
include 'includes/header.php';
?>
<div class="form-card wide">
  <h1>My profile</h1>
  <p class="muted">Email: <strong><?= e($u['email']) ?></strong> (cannot be changed) &middot; Role: <strong><?= e(ucfirst($u['role'])) ?></strong> &middot; Status: <?= status_badge($u['status']) ?></p>

  <form method="post" action="profile.php" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" maxlength="50" value="<?= e($old['name']) ?>" class="<?= inv($errors, 'name') ?>">
      <small class="field-error"><?= field_err($errors, 'name') ?></small>
    </div>
    <div class="field">
      <label for="phone">Phone number</label>
      <input type="text" id="phone" name="phone" maxlength="10" inputmode="numeric" value="<?= e($old['phone']) ?>" class="<?= inv($errors, 'phone') ?>">
      <small class="field-error"><?= field_err($errors, 'phone') ?></small>
    </div>

    <?php if ($is_tutor): ?>
      <h2>Tutor details</h2>
      <div class="field">
        <label for="subjects">Subjects (comma separated)</label>
        <input type="text" id="subjects" name="subjects" maxlength="200" placeholder="Mathematics, Science" value="<?= e($old['subjects']) ?>" class="<?= inv($errors, 'subjects') ?>">
        <small class="field-error"><?= field_err($errors, 'subjects') ?></small>
      </div>
      <div class="field">
        <label for="qualification">Qualification</label>
        <input type="text" id="qualification" name="qualification" maxlength="100" value="<?= e($old['qualification']) ?>" class="<?= inv($errors, 'qualification') ?>">
        <small class="field-error"><?= field_err($errors, 'qualification') ?></small>
      </div>
      <div class="field">
        <label for="experience_years">Experience (years)</label>
        <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?= e($old['experience_years']) ?>" class="<?= inv($errors, 'experience_years') ?>">
        <small class="field-error"><?= field_err($errors, 'experience_years') ?></small>
      </div>
      <div class="field">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" maxlength="100" placeholder="Lalitpur" value="<?= e($old['location']) ?>" class="<?= inv($errors, 'location') ?>">
        <small class="field-error"><?= field_err($errors, 'location') ?></small>
      </div>
      <div class="field">
        <label for="price_per_hour">Fee per hour (Rs.)</label>
        <input type="number" id="price_per_hour" name="price_per_hour" min="1" max="99999" value="<?= e($old['price_per_hour']) ?>" class="<?= inv($errors, 'price_per_hour') ?>">
        <small class="field-error"><?= field_err($errors, 'price_per_hour') ?></small>
      </div>
      <div class="field">
        <label for="availability">Availability</label>
        <input type="text" id="availability" name="availability" maxlength="150" placeholder="Weekdays 4 PM - 7 PM" value="<?= e($old['availability']) ?>" class="<?= inv($errors, 'availability') ?>">
        <small class="field-error"><?= field_err($errors, 'availability') ?></small>
      </div>
      <div class="field">
        <label for="bio">About you (optional)</label>
        <textarea id="bio" name="bio" rows="4" maxlength="1000" class="<?= inv($errors, 'bio') ?>"><?= e($old['bio']) ?></textarea>
        <small class="field-error"><?= field_err($errors, 'bio') ?></small>
      </div>
    <?php endif; ?>

    <h2>Change password <small class="muted">(leave blank to keep the current one)</small></h2>
    <div class="field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" maxlength="64" class="<?= inv($errors, 'current_password') ?>">
      <small class="field-error"><?= field_err($errors, 'current_password') ?></small>
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input type="password" id="new_password" name="new_password" maxlength="64" class="<?= inv($errors, 'new_password') ?>">
      <small class="field-error"><?= field_err($errors, 'new_password') ?></small>
    </div>
    <div class="field">
      <label for="confirm_password">Confirm new password</label>
      <input type="password" id="confirm_password" name="confirm_password" maxlength="64" class="<?= inv($errors, 'confirm_password') ?>">
      <small class="field-error"><?= field_err($errors, 'confirm_password') ?></small>
    </div>

    <button type="submit" class="btn btn-primary">Save changes</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
