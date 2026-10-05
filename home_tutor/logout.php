<?php
require_once 'includes/functions.php';
$_SESSION = array();
session_destroy();
session_start();
set_flash('success', 'You have been logged out.');
redirect('login.php');
