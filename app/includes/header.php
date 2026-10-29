<?php $user = current_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Home') . ' | ' . APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php"><?= e(APP_NAME) ?></a>
    <?php if ($user): ?>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="courses.php">Courses</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="enrollments.php">Enrollments &amp; Grades</a>
                <a href="users.php">Students</a>
            <?php else: ?>
                <a href="my_courses.php">My Courses</a>
            <?php endif; ?>
            <a href="profile.php"><?= e($user['name']) ?></a>
            <a href="logout.php">Log out</a>
        </nav>
    <?php else: ?>
        <nav>
            <a href="login.php">Log in</a>
            <a href="register.php">Register</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
