<?php
require_once __DIR__ . '/config.php';

require_admin();

$id = (int) ($_GET['id'] ?? 0);
$course = ['code' => '', 'title' => '', 'description' => '', 'credit_hours' => 3, 'instructor' => '', 'capacity' => 30];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if (!$course) {
        flash('error', 'Course not found.');
        redirect('courses.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $course['code'] = strtoupper(trim($_POST['code'] ?? ''));
    $course['title'] = trim($_POST['title'] ?? '');
    $course['description'] = trim($_POST['description'] ?? '');
    $course['credit_hours'] = (int) ($_POST['credit_hours'] ?? 0);
    $course['instructor'] = trim($_POST['instructor'] ?? '');
    $course['capacity'] = (int) ($_POST['capacity'] ?? 0);

    if (!preg_match('/^[A-Z]{3}\d{3}$/', $course['code'])) {
        $errors[] = 'Course code must be 3 letters followed by 3 digits, e.g. CSC418.';
    }
    if (strlen($course['title']) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    }
    if ($course['credit_hours'] < 1 || $course['credit_hours'] > 4) {
        $errors[] = 'Credit hours must be between 1 and 4.';
    }
    if ($course['instructor'] === '') {
        $errors[] = 'Instructor is required.';
    }
    if ($course['capacity'] < 1 || $course['capacity'] > 200) {
        $errors[] = 'Capacity must be between 1 and 200.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE code = ? AND id <> ?');
        $stmt->execute([$course['code'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'A course with this code already exists.';
        }
    }

    if (!$errors && $id) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = ?');
        $stmt->execute([$id]);
        if ($course['capacity'] < $stmt->fetchColumn()) {
            $errors[] = 'Capacity cannot be lower than the number of students already enrolled.';
        }
    }

    if (!$errors) {
        $values = [$course['code'], $course['title'], $course['description'],
                   $course['credit_hours'], $course['instructor'], $course['capacity']];
        if ($id) {
            $values[] = $id;
            db()->prepare('UPDATE courses SET code = ?, title = ?, description = ?, credit_hours = ?, instructor = ?, capacity = ? WHERE id = ?')
                ->execute($values);
            flash('success', 'Course ' . $course['code'] . ' updated.');
        } else {
            db()->prepare('INSERT INTO courses (code, title, description, credit_hours, instructor, capacity) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute($values);
            flash('success', 'Course ' . $course['code'] . ' added.');
        }
        redirect('courses.php');
    }
}

$pageTitle = $id ? 'Edit course' : 'Add course';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1><?= e($pageTitle) ?></h1>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Course code <input name="code" value="<?= e($course['code']) ?>" placeholder="CSC418" required></label>
        <label>Title <input name="title" value="<?= e($course['title']) ?>" required></label>
        <label>Description <textarea name="description" rows="3"><?= e($course['description']) ?></textarea></label>
        <label>Credit hours <input type="number" name="credit_hours" min="1" max="4" value="<?= (int) $course['credit_hours'] ?>" required></label>
        <label>Instructor <input name="instructor" value="<?= e($course['instructor']) ?>" required></label>
        <label>Capacity <input type="number" name="capacity" min="1" max="200" value="<?= (int) $course['capacity'] ?>" required></label>
        <div class="row">
            <button type="submit"><?= $id ? 'Save changes' : 'Add course' ?></button>
            <a class="button secondary" href="courses.php">Cancel</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
