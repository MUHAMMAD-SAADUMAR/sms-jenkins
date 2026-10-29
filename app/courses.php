<?php
require_once __DIR__ . '/config.php';

$user = require_login();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT c.*, COUNT(en.id) AS enrolled,
               SUM(en.student_id = ?) AS mine
        FROM courses c
        LEFT JOIN enrollments en ON en.course_id = c.id';
$params = [$user['id']];
if ($q !== '') {
    $sql .= ' WHERE c.code LIKE ? OR c.title LIKE ? OR c.instructor LIKE ?';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$sql .= ' GROUP BY c.id ORDER BY c.code';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$pageTitle = 'Courses';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="row-between">
        <h1>Courses</h1>
        <div class="row">
            <form method="get" class="inline">
                <input name="q" value="<?= e($q) ?>" placeholder="Search code, title or instructor">
                <button type="submit">Search</button>
            </form>
            <?php if (is_admin()): ?>
                <a class="button" href="course_form.php">+ Add course</a>
            <?php endif; ?>
        </div>
    </div>
    <table>
        <thead>
        <tr><th>Code</th><th>Title</th><th>Credit hrs</th><th>Instructor</th><th>Seats</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($courses as $c): ?>
            <?php $full = $c['enrolled'] >= $c['capacity']; ?>
            <tr>
                <td><strong><?= e($c['code']) ?></strong></td>
                <td><?= e($c['title']) ?><div class="muted small"><?= e($c['description']) ?></div></td>
                <td><?= (int) $c['credit_hours'] ?></td>
                <td><?= e($c['instructor']) ?></td>
                <td><?= (int) $c['enrolled'] ?> / <?= (int) $c['capacity'] ?></td>
                <td class="actions">
                    <?php if (is_admin()): ?>
                        <a class="button small" href="course_form.php?id=<?= (int) $c['id'] ?>">Edit</a>
                        <a class="button small" href="enrollments.php?course_id=<?= (int) $c['id'] ?>">Students</a>
                        <form method="post" action="course_delete.php" class="inline"
                              onsubmit="return confirm('Delete <?= e($c['code']) ?>? This also removes its enrollments.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" class="small danger">Delete</button>
                        </form>
                    <?php elseif ($c['mine']): ?>
                        <form method="post" action="enroll.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
                            <input type="hidden" name="action" value="drop">
                            <button type="submit" class="small danger">Drop</button>
                        </form>
                    <?php elseif ($full): ?>
                        <span class="badge off">Full</span>
                    <?php else: ?>
                        <form method="post" action="enroll.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
                            <input type="hidden" name="action" value="enroll">
                            <button type="submit" class="small">Enroll</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?>
            <tr><td colspan="6" class="muted">No courses found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
