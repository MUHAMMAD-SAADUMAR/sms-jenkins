<?php
require_once __DIR__ . '/config.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT code FROM courses WHERE id = ?');
$stmt->execute([$id]);
$code = $stmt->fetchColumn();

if ($code === false) {
    flash('error', 'Course not found.');
} else {
    // Enrollments are removed by the ON DELETE CASCADE foreign key.
    db()->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
    flash('success', "Course $code deleted.");
}
redirect('courses.php');
