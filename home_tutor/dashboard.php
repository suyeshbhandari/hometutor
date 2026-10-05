<?php
// Sends each user to the dashboard of their role.
require_once 'includes/functions.php';
require_login();
$u = current_user();
if ($u['role'] === 'admin') {
    redirect('admin_dashboard.php');
} elseif ($u['role'] === 'tutor') {
    redirect('tutor_dashboard.php');
}
redirect('student_dashboard.php');
