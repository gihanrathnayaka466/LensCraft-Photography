<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$name = $email = $phone = $address = $studio = '';
$role = 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $name = trim((string)($_POST['fullname'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $role = (string)($_POST['role'] ?? 'customer');
        $role = in_array($role, ['customer', 'photographer'], true) ? $role : 'customer';
        $studio = trim((string)($_POST['studio_name'] ?? ''));

        if (mb_strlen($name) < 2) throw new RuntimeException('Please enter your full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please enter a valid email address.');
        if (mb_strlen($phone) < 7) throw new RuntimeException('Please enter a valid phone number.');
        if (mb_strlen($address) < 3) throw new RuntimeException('Please enter your location/address.');
        if (strlen($password) < 8) throw new RuntimeException('Password must be at least 8 characters.');
        if ($password !== $confirm) throw new RuntimeException('Passwords do not match.');
        if ($role === 'photographer' && mb_strlen($studio) < 2) throw new RuntimeException('Please enter your studio/brand name.');

        $db = db();
        $check = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            throw new RuntimeException('An account with this email already exists. Please sign in.');
        }

        $db->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (fullname, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $address, $role);
            $stmt->execute();
            $uid = (int)$db->insert_id;

            if ($role === 'photographer') {
                $category = 'Photography';
                $bio = 'Professional photographer profile. Add your packages and portfolio from the dashboard.';
                $avatar = 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=85';
                $cover = 'https://images.unsplash.com/photo-1554048612-b6a482bc67e5?auto=format&fit=crop&w=1400&q=85';
                $basePrice = 0.00;
                $p = $db->prepare('INSERT INTO photographers (user_id, studio_name, category, location, bio, avatar_url, cover_url, base_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $p->bind_param('issssssd', $uid, $studio, $category, $address, $bio, $avatar, $cover, $basePrice);
                $p->execute();
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        flash('success', 'Account created! Set up your profile.');
        redirect('account.php');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Account creation failed. Please check your database setup.';
    }
}

$page_title = 'Create Account | LensCraft';
require_once __DIR__ . '/partials_header.php';
?>
<section class="auth-shell">
  <div class="auth-card wide">
    <span class="eyebrow">JOIN LENsCRAFT</span>
    <h1>Create your account</h1>
    <p class="muted">Book photographers or create your professional photographer profile.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="register.php" class="auth-form two-col" novalidate>
      <?= csrf_field() ?>
      <label>Full name<input name="fullname" value="<?= e($name) ?>" autocomplete="name" required></label>
      <label>Email<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
      <label>Phone<input name="phone" value="<?= e($phone) ?>" autocomplete="tel" required></label>
      <label>Address / Location<input name="address" value="<?= e($address) ?>" autocomplete="street-address" required></label>
      <label>Password<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
      <label>Confirm password<input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
      <label>Account type
        <select name="role" id="role">
          <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
          <option value="photographer" <?= $role === 'photographer' ? 'selected' : '' ?>>Photographer</option>
        </select>
      </label>
      <label id="studio-wrap">Studio / Brand name<input name="studio_name" value="<?= e($studio) ?>" placeholder="Required for photographers"></label>
      <button type="submit" class="btn btn-primary btn-full full">Create account</button>
    </form>
    <p class="auth-foot">Already registered? <a href="login.php">Sign in</a></p>
  </div>
</section>
<script>
const roleSelect = document.getElementById('role');
const studioWrap = document.getElementById('studio-wrap');
function syncStudio(){ studioWrap.style.display = roleSelect.value === 'photographer' ? 'grid' : 'none'; }
roleSelect.addEventListener('change', syncStudio); syncStudio();
</script>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
