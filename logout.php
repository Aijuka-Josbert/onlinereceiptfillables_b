<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
logoutUser();
// logoutUser() already redirects to login.php, but we keep it safe