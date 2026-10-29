<?php
require_once __DIR__ . '/config.php';

$user = require_login();
if ($user['role'] !== 'student') {
    redirect('enrollments.php');
}

$stmt = db()->prepare(
    'SELECT en.grade, en.enrolled_at, c.id AS course_id, c.code, c.title, c.credit_hours, c.instructor
     FROM enrollments en
     JOIN courses c ON c.id = en.course_id
     WHERE en.student_id = ?
     ORDER BY c.code'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$gradedCredits = 0;
$qualityPoints = 0;
$inProgressCredits = 0;
foreach ($rows as $r) {
    if ($r['grade'] === null) {
        $inProgressCredits += $r['credit_hours'];
    } else {
        $gradedCredits += $r['credit_hours'];
        $qualityPoints += GRADE_POINTS[$r['grade']] * $r['credit_hours'];
    }
}
$gpa = $gradedCredits ? $qualityPoints / $gradedCredits : null;

$pageTitle = 'My courses';
require __DIR__ . '/includes/header.php';
?>
<section class="stats">
    <div class="stat"><span><?= count($rows) ?></span>Courses</div>
    <div class="stat"><span><?= $inProgressCredits ?></span>Credit hrs in progress</div>
    <div class="stat"><span><?= $gradedCredits ?></span>Credit hrs completed</div>
    <div class="stat"><span><?= $gpa === null ? '—' : number_format($gpa, 2) ?></span>CGPA</div>
</section>
<section class="card">
    <div class="row-between">
        <h1>My courses &amp; transcript</h1>
        <a class="button" href="courses.php">Browse courses</a>
    </div>
    <table>
        <thead>
        <tr><th>Code</th><th>Title</th><th>Credit hrs</th><th>Instructor</th><th>Enrolled on</th><th>Grade</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= e($r['code']) ?></strong></td>
                <td><?= e($r['title']) ?></td>
                <td><?= (int) $r['credit_hours'] ?></td>
                <td><?= e($r['instructor']) ?></td>
                <td><?= e(date('d M Y', strtotime($r['enrolled_at']))) ?></td>
                <td><?= $r['grade'] === null ? '<span class="badge">In progress</span>' : '<strong>' . e($r['grade']) . '</strong>' ?></td>
                <td>
                    <?php if ($r['grade'] === null): ?>
                        <form method="post" action="enroll.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="course_id" value="<?= (int) $r['course_id'] ?>">
                            <input type="hidden" name="action" value="drop">
                            <input type="hidden" name="back" value="my_courses">
                            <button type="submit" class="small danger">Drop</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="7" class="muted">You are not enrolled in any course yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
