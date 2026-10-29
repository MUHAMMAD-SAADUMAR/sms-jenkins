<?php
require_once __DIR__ . '/config.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role = 'student'")->execute([$id]);
    flash('success', 'Student status updated.');
    redirect('users.php');
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.name, u.email, u.reg_no, u.is_active, u.created_at,
               COUNT(en.id) AS enrolled
        FROM users u
        LEFT JOIN enrollments en ON en.student_id = u.id
        WHERE u.role = 'student'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.reg_no LIKE ?)';
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= ' GROUP BY u.id ORDER BY u.name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Students';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="row-between">
        <h1>Students</h1>
        <form method="get" class="inline">
            <input name="q" value="<?= e($q) ?>" placeholder="Search name, email or reg no.">
            <button type="submit">Search</button>
        </form>
    </div>
    <table>
        <thead>
        <tr><th>Reg no.</th><th>Name</th><th>Email</th><th>Courses</th><th>Joined</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= e($s['reg_no']) ?></td>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= (int) $s['enrolled'] ?></td>
                <td><?= e(date('d M Y', strtotime($s['created_at']))) ?></td>
                <td><span class="badge <?= $s['is_active'] ? 'ok' : 'off' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                    <form method="post" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <button type="submit" class="small"><?= $s['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?>
            <tr><td colspan="7" class="muted">No students found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
