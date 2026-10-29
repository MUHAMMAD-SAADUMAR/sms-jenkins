<?php
require_once __DIR__ . '/config.php';

$user = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $current = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);

    if (!password_verify($current, $stmt->fetchColumn())) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password changed successfully.');
        redirect('profile.php');
    }
}

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>My profile</h1>
    <table class="details">
        <tr><th>Name</th><td><?= e($user['name']) ?></td></tr>
        <tr><th>Email</th><td><?= e($user['email']) ?></td></tr>
        <tr><th>Registration no.</th><td><?= e($user['reg_no'] ?? '—') ?></td></tr>
        <tr><th>Role</th><td><?= e(ucfirst($user['role'])) ?></td></tr>
    </table>
</section>
<section class="card narrow">
    <h2>Change password</h2>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Current password <input type="password" name="current" required></label>
        <label>New password <input type="password" name="new" minlength="8" required></label>
        <label>Confirm new password <input type="password" name="confirm" minlength="8" required></label>
        <button type="submit">Change password</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
