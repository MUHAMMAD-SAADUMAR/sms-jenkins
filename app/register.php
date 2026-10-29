<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$form = ['name' => '', 'email' => '', 'reg_no' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = strtolower(trim($_POST['email'] ?? ''));
    $form['reg_no'] = strtoupper(trim($_POST['reg_no'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($form['name']) < 3) {
        $errors[] = 'Name must be at least 3 characters.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[A-Z]{2}\d{2}-[A-Z]{3}-\d{3}$/', $form['reg_no'])) {
        $errors[] = 'Registration number must look like FA23-BDS-054.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR reg_no = ?');
        $stmt->execute([$form['email'], $form['reg_no']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'An account with this email or registration number already exists.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            "INSERT INTO users (name, email, reg_no, password_hash, role) VALUES (?, ?, ?, ?, 'student')"
        );
        $stmt->execute([$form['name'], $form['email'], $form['reg_no'], password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Account created. You can now log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1>Student registration</h1>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Full name <input name="name" value="<?= e($form['name']) ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= e($form['email']) ?>" required></label>
        <label>Registration number <input name="reg_no" value="<?= e($form['reg_no']) ?>" placeholder="FA23-BDS-054" required></label>
        <label>Password <input type="password" name="password" minlength="8" required></label>
        <label>Confirm password <input type="password" name="confirm" minlength="8" required></label>
        <button type="submit">Create account</button>
    </form>
    <p class="muted">Already registered? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
