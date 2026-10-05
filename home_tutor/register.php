<?php
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = array();
$old = array('name' => '', 'email' => '', 'phone' => '', 'role' => 'student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['name']  = post('name');
    $old['email'] = strtolower(post('email'));
    $old['phone'] = post('phone');
    $old['role']  = post('role');
    $password = post_raw('password');
    $confirm  = post_raw('confirm_password');

    // ----- validation -----
    if (($m = validate_name($old['name'])) !== '')   $errors['name'] = $m;
    if (($m = validate_email($old['email'])) !== '') $errors['email'] = $m;
    if (($m = validate_phone($old['phone'])) !== '') $errors['phone'] = $m;
    if ($old['role'] !== 'student' && $old['role'] !== 'tutor') {
        $errors['role'] = 'Please choose Student/Parent or Tutor.';
    }
    if (($m = validate_password($password)) !== '')  $errors['password'] = $m;
    if ($confirm === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // email must not already be registered
    if (!isset($errors['email'])) {
        $exists = db_one('SELECT id FROM users WHERE email = ?', 's', array($old['email']));
        if ($exists) {
            $errors['email'] = 'This email is already registered. Please login instead.';
        }
    }

    // ----- save -----
    if (!$errors) {
        $hash   = password_hash($password, PASSWORD_DEFAULT);
        $status = ($old['role'] === 'tutor') ? 'pending' : 'approved'; // tutors wait for admin approval
        try {
            db_run('INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, ?)',
                   'ssssss', array($old['name'], $old['email'], $hash, $old['phone'], $old['role'], $status));
            $new_id = $conn->insert_id;
            if ($old['role'] === 'tutor') {
                db_run('INSERT INTO tutor_profiles (user_id) VALUES (?)', 'i', array($new_id));
            }
            if ($old['role'] === 'tutor') {
                set_flash('success', 'Registration successful! Login, complete your tutor profile, and wait for admin approval.');
            } else {
                set_flash('success', 'Registration successful! You can login now.');
            }
            redirect('login.php');
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() == 1062) {
                $errors['email'] = 'This email is already registered. Please login instead.';
            } else {
                $errors['general'] = 'Something went wrong while saving. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>
<div class="form-card">
  <h1>Create an account</h1>
  <p class="muted">Students/parents can book tutors. Tutors are shown to students after admin approval.</p>

  <?php if (isset($errors['general'])): ?>
    <div class="alert alert-error"><?= e($errors['general']) ?></div>
  <?php endif; ?>

  <form method="post" action="register.php" id="registerForm" data-validate="register" novalidate>
    <?= csrf_field() ?>

    <div class="field">
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" maxlength="50" value="<?= e($old['name']) ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['name']) ? $errors['name'] : '') ?></small>
    </div>

    <div class="field">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" maxlength="100" placeholder="name@gmail.com" value="<?= e($old['email']) ?>" class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['email']) ? $errors['email'] : '') ?></small>
    </div>

    <div class="field">
      <label for="phone">Phone number</label>
      <input type="text" id="phone" name="phone" maxlength="10" inputmode="numeric" placeholder="98XXXXXXXX" value="<?= e($old['phone']) ?>" class="<?= isset($errors['phone']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['phone']) ? $errors['phone'] : '') ?></small>
    </div>

    <div class="field">
      <label for="role">I am a</label>
      <select id="role" name="role" class="<?= isset($errors['role']) ? 'invalid' : '' ?>">
        <option value="student" <?= $old['role'] === 'student' ? 'selected' : '' ?>>Student / Parent</option>
        <option value="tutor" <?= $old['role'] === 'tutor' ? 'selected' : '' ?>>Tutor</option>
      </select>
      <small class="field-error"><?= e(isset($errors['role']) ? $errors['role'] : '') ?></small>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" maxlength="64" class="<?= isset($errors['password']) ? 'invalid' : '' ?>">
      <small class="hint">At least 8 characters with a letter and a number.</small>
      <small class="field-error"><?= e(isset($errors['password']) ? $errors['password'] : '') ?></small>
    </div>

    <div class="field">
      <label for="confirm_password">Confirm password</label>
      <input type="password" id="confirm_password" name="confirm_password" maxlength="64" class="<?= isset($errors['confirm_password']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['confirm_password']) ? $errors['confirm_password'] : '') ?></small>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Register</button>
  </form>
  <p class="center">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php include 'includes/footer.php'; ?>
