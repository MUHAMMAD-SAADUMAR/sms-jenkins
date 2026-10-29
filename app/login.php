<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            $error = 'Incorrect email or password.';
        } elseif (!$row['is_active']) {
            $error = 'Your account has been deactivated. Please contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$row['id']]);
            flash('success', 'Welcome back!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1>Log in</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Log in</button>
    </form>
    <p class="muted">New student? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
