<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';
$next = isset($_GET['next']) ? (string)$_GET['next'] : 'dashboard.php';
if (!preg_match('#^(?:/(?:photographsite/)?|)(?:[a-z_]+\.php)(?:\?[a-zA-Z0-9_=&%-]+)?$#i', $next)) $next = 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $nextPost = (string)($_POST['next'] ?? 'dashboard.php');
        if (preg_match('#^(?:/(?:photographsite/)?|)(?:[a-z_]+\.php)(?:\?[a-zA-Z0-9_=&%-]+)?$#i', $nextPost)) $next = $nextPost;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            throw new RuntimeException('Please enter a valid email and password.');
        }

        $stmt = db()->prepare('SELECT id, fullname, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, (string)$user['password'])) {
            throw new RuntimeException('Email or password is incorrect.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = (string)$user['fullname'];
        $_SESSION['user_role'] = (string)$user['role'];
        flash('success', 'Welcome back, ' . $user['fullname'] . '!');
        redirect($next);
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to sign in. Check your database connection and try again.';
    }
}

$page_title = 'Sign In | LensCraft';
require_once __DIR__ . '/partials_header.php';
?>
<section class="auth-shell">
  <div class="auth-card">
    <span class="eyebrow">WELCOME BACK</span>
    <h1>Sign in</h1>
    <p class="muted">Access your bookings, saved photographers and account.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="login.php" class="auth-form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label>Email
        <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
      </label>
      <label>Password
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button type="submit" class="btn btn-primary btn-full">Sign in</button>
    </form>
    <p class="auth-foot">New to LensCraft? <a href="register.php">Create an account</a></p>
  </div>
</section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
