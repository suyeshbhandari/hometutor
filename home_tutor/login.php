<?php
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = array();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(post('email'));
    $password = post_raw('password');

    if (($m = validate_email($email)) !== '') $errors['email'] = $m;
    if ($password === '') $errors['password'] = 'Password is required.';

    if (!$errors) {
        $user = db_one('SELECT id, password, status FROM users WHERE email = ?', 's', array($email));
        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = 'Invalid email or password.';
        } elseif ($user['status'] === 'rejected') {
            $errors['general'] = 'Your account has been rejected or blocked by the admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            redirect('dashboard.php');
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>
<div class="form-card">
  <h1>Login</h1>

  <?php if (isset($errors['general'])): ?>
    <div class="alert alert-error"><?= e($errors['general']) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php" data-validate="login" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" maxlength="100" value="<?= e($email) ?>" class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['email']) ? $errors['email'] : '') ?></small>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" maxlength="64" class="<?= isset($errors['password']) ? 'invalid' : '' ?>">
      <small class="field-error"><?= e(isset($errors['password']) ? $errors['password'] : '') ?></small>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Login</button>
  </form>
  <p class="center">New here? <a href="register.php">Create an account</a></p>
</div>
<?php include 'includes/footer.php'; ?>
