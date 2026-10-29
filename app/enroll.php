<?php
require_once __DIR__ . '/config.php';

const MAX_CREDIT_HOURS = 18;

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}
check_csrf();

if ($user['role'] !== 'student') {
    flash('error', 'Only students can enroll in courses.');
    redirect('courses.php');
}

$courseId = (int) ($_POST['course_id'] ?? 0);
$action = $_POST['action'] ?? '';
$back = ($_POST['back'] ?? '') === 'my_courses' ? 'my_courses.php' : 'courses.php';

$stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) {
    flash('error', 'Course not found.');
    redirect($back);
}

$stmt = db()->prepare('SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?');
$stmt->execute([$user['id'], $courseId]);
$existing = $stmt->fetch();

if ($action === 'enroll') {
    if ($existing) {
        flash('error', "You are already enrolled in {$course['code']}.");
        redirect($back);
    }

    $pdo = db();
    $pdo->beginTransaction();

    // Lock the course row so two students cannot take the last seat at the same time.
    $stmt = $pdo->prepare('SELECT capacity FROM courses WHERE id = ? FOR UPDATE');
    $stmt->execute([$courseId]);
    $capacity = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = ?');
    $stmt->execute([$courseId]);
    if ((int) $stmt->fetchColumn() >= $capacity) {
        $pdo->rollBack();
        flash('error', "{$course['code']} is full.");
        redirect($back);
    }

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(c.credit_hours), 0) FROM enrollments en
         JOIN courses c ON c.id = en.course_id
         WHERE en.student_id = ? AND en.grade IS NULL'
    );
    $stmt->execute([$user['id']]);
    if ((int) $stmt->fetchColumn() + (int) $course['credit_hours'] > MAX_CREDIT_HOURS) {
        $pdo->rollBack();
        flash('error', 'Enrolling would exceed the limit of ' . MAX_CREDIT_HOURS . ' credit hours in progress.');
        redirect($back);
    }

    $pdo->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)')->execute([$user['id'], $courseId]);
    $pdo->commit();
    flash('success', "Enrolled in {$course['code']} – {$course['title']}.");
} elseif ($action === 'drop') {
    if (!$existing) {
        flash('error', "You are not enrolled in {$course['code']}.");
    } elseif ($existing['grade'] !== null) {
        flash('error', "You cannot drop {$course['code']} after a grade has been awarded.");
    } else {
        db()->prepare('DELETE FROM enrollments WHERE id = ?')->execute([$existing['id']]);
        flash('success', "Dropped {$course['code']}.");
    }
}

redirect($back);
