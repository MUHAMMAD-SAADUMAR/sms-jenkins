<?php
require_once __DIR__ . '/config.php';

redirect(current_user() ? 'dashboard.php' : 'login.php');
