<?php
require_once __DIR__ . '/config.php';

$user = require_login();
$pdo = db();

if ($user['role'] === 'admin') {
    $stats = [
        'Students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
        'Courses' => $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
        'Enrollments' => $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
        'Awaiting grade' => $pdo->query('SELECT COUNT(*) FROM enrollments WHERE grade IS NULL')->fetchColumn(),
    ];
    $popular = $pdo->query(
        'SELECT c.code, c.title, c.capacity, COUNT(en.id) AS enrolled
         FROM courses c LEFT JOIN enrollments en ON en.course_id = c.id
         GROUP BY c.id ORDER BY enrolled DESC, c.code LIMIT 5'
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS courses,
                COALESCE(SUM(CASE WHEN en.grade IS NULL THEN c.credit_hours END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN en.grade IS NOT NULL THEN c.credit_hours END), 0) AS completed
         FROM enrollments en JOIN courses c ON c.id = en.course_id
         WHERE en.student_id = ?'
    );
    $stmt->execute([$user['id']]);
    $s = $stmt->fetch();
    $stats = [
        'My courses' => $s['courses'],
        'Credit hrs in progress' => $s['in_progress'],
        'Credit hrs completed' => $s['completed'],
    ];
}

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Welcome, <?= e($user['name']) ?></h1>
    <p class="muted">
        Logged in as <strong><?= e(ucfirst($user['role'])) ?></strong>
        <?php if ($user['reg_no']): ?>&middot; Registration no. <strong><?= e($user['reg_no']) ?></strong><?php endif; ?>
    </p>
</section>
<section class="stats">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat"><span><?= (int) $value ?></span><?= e($label) ?></div>
    <?php endforeach; ?>
</section>
<?php if ($user['role'] === 'admin'): ?>
    <section class="card">
        <h2>Most popular courses</h2>
        <table>
            <thead><tr><th>Code</th><th>Title</th><th>Seats filled</th></tr></thead>
            <tbody>
            <?php foreach ($popular as $p): ?>
                <tr>
                    <td><?= e($p['code']) ?></td>
                    <td><?= e($p['title']) ?></td>
                    <td><?= (int) $p['enrolled'] ?> / <?= (int) $p['capacity'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php else: ?>
    <section class="card">
        <p><a class="button" href="courses.php">Browse &amp; enroll in courses</a>
           <a class="button secondary" href="my_courses.php">View my transcript</a></p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
