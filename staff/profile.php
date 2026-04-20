<?php
require_once __DIR__ . '/../includes/auth.php';
require_staff();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Profile — Staff';
$activePage = 'profile';

include __DIR__ . '/../includes/layout_header.php';
include __DIR__ . '/../includes/profile_content.php';
include __DIR__ . '/../includes/layout_footer.php';
