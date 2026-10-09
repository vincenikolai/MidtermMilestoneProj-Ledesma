<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/auth.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $errors[] = 'Your session expired. Please try again.';
    if (!preg_match('/^[a-zA-Z0-9_ ]{3,50}$/', $username)) $errors[] = 'Username must be 3-50 letters, numbers, spaces, or underscores.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $user = new User();
        if ($user->emailExists($email)) {
            $errors[] = 'That email is already registered.';
        } elseif ($user->usernameExists($username)) {
            $errors[] = 'That username is already registered.';
        } else {
            try {
                $user->register($username, $email, $password);
                header('Location: login.php?registered=1');
                exit;
            } catch (PDOException $exception) {
                $errors[] = $exception->getCode() === '23000'
                    ? 'That email or username is already registered.'
                    : 'Registration could not be completed.';
            }
        }
    }
}
$pageTitle = 'Create account'; require __DIR__ . '/header.php';
?><div class="auth-page"><section class="auth-card"><h1>Join Timplada</h1><p>Keep your family recipes close and share them with the community.</p>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<form id="register-form" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-group"><label for="username">Username</label><input class="form-control" id="username" name="username" required value="<?= e($_POST['username'] ?? '') ?>"></div><div class="form-group"><label for="email">Email</label><input class="form-control" type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div><div class="form-group"><label for="password">Password</label><input class="form-control" type="password" id="password" name="password" minlength="8" required></div><div class="form-group"><label for="confirm_password">Confirm password</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" required><small id="password-match" class="field-hint" aria-live="polite"></small></div><button class="btn" type="submit">Create account</button></form><p>Already a member? <a href="login.php">Log in</a></p></section></div><?php require __DIR__ . '/footer.php'; ?>
