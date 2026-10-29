<?php
require_once __DIR__ . '/config.php';

require_admin();

$courses = db()->query('SELECT id, code, title FROM courses ORDER BY code')->fetchAll();
$courseId = (int) ($_GET['course_id'] ?? ($_POST['course_id'] ?? ($courses[0]['id'] ?? 0)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (isset($_POST['remove'])) {
        db()->prepare('DELETE FROM enrollments WHERE id = ? AND course_id = ?')
            ->execute([(int) $_POST['remove'], $courseId]);
        flash('success', 'Student removed from the course.');
    } else {
        $update = db()->prepare('UPDATE enrollments SET grade = ?, graded_at = ? WHERE id = ? AND course_id = ?');
        $saved = 0;
        foreach ($_POST['grade'] ?? [] as $enrollmentId => $grade) {
            if ($grade !== '' && !array_key_exists($grade, GRADE_POINTS)) {
                continue;
            }
            $grade = $grade === '' ? null : $grade;
            $update->execute([$grade, $grade === null ? null : date('Y-m-d H:i:s'), (int) $enrollmentId, $courseId]);
            $saved += $update->rowCount();
        }
        flash('success', "Grades saved ($saved changed).");
    }
    redirect('enrollments.php?course_id=' . $courseId);
}

$students = [];
$course = null;
if ($courseId) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    $stmt = db()->prepare(
        'SELECT en.id, en.grade, en.enrolled_at, u.name, u.reg_no, u.email
         FROM enrollments en
         JOIN users u ON u.id = en.student_id
         WHERE en.course_id = ?
         ORDER BY u.reg_no'
    );
    $stmt->execute([$courseId]);
    $students = $stmt->fetchAll();
}

$pageTitle = 'Enrollments & grades';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="row-between">
        <h1>Enrollments &amp; grades</h1>
        <form method="get" class="inline">
            <select name="course_id" onchange="this.form.submit()">
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $c['id'] == $courseId ? 'selected' : '' ?>>
                        <?= e($c['code'] . ' – ' . $c['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Show</button></noscript>
        </form>
    </div>

    <?php if (!$course): ?>
        <p class="muted">No courses yet. <a href="course_form.php">Add a course</a> first.</p>
    <?php else: ?>
        <p class="muted"><?= count($students) ?> of <?= (int) $course['capacity'] ?> seats filled &middot;
            Instructor: <?= e($course['instructor']) ?></p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="course_id" value="<?= (int) $courseId ?>">
            <table>
                <thead>
                <tr><th>Reg no.</th><th>Name</th><th>Email</th><th>Enrolled on</th><th>Grade</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['reg_no']) ?></td>
                        <td><?= e($s['name']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e(date('d M Y', strtotime($s['enrolled_at']))) ?></td>
                        <td>
                            <select name="grade[<?= (int) $s['id'] ?>]">
                                <option value="">— not graded —</option>
                                <?php foreach (array_keys(GRADE_POINTS) as $g): ?>
                                    <option value="<?= e($g) ?>" <?= $s['grade'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <button type="submit" name="remove" value="<?= (int) $s['id'] ?>" class="small danger"
                                    onclick="return confirm('Remove this student from the course?');">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?>
                    <tr><td colspan="6" class="muted">No students enrolled in this course.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($students): ?><button type="submit">Save grades</button><?php endif; ?>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
