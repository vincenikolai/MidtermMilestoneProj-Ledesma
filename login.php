<?php
declare(strict_types=1);

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!(new User())->login($email, $password)) {
        $error = 'The email or password is incorrect.';
    } else {
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/header.php';
?>

<div class="auth-page">
    <section class="auth-card">
        <h1>Welcome back</h1>
        <p>Sign in to discover and save home-cooked Filipino favorites.</p>

        <?php if (isset($_GET['registered'])): ?>
            <p class="success">Account created. You can now log in.</p>
        <?php endif; ?>

        <?php if (isset($_GET['session'])): ?>
            <p class="error">Your previous session is no longer valid. Please log in again.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>

            <button class="btn" type="submit">Log in</button>
        </form>

        <p>New here? <a href="register.php">Create an account</a></p>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
