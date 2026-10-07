<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_login();
$user=current_user();
$uid=(int)$user['id'];
$photographer=null;
if($user['role']==='photographer'){
 $s=db()->prepare('SELECT * FROM photographers WHERE user_id=? LIMIT 1');$s->bind_param('i',$uid);$s->execute();$photographer=$s->get_result()->fetch_assoc();
}
$page_title='My Profile | LensCraft';
require_once __DIR__ . '/partials_header.php';
?>
<section class="section container narrow"><div class="dashboard-head"><div><span class="eyebrow">MY ACCOUNT</span><h1>My profile</h1><p class="muted">Update your details and manage your <?= $photographer?'photography studio':'bookings' ?>.</p></div><a class="btn btn-ghost" href="dashboard.php">Dashboard</a></div>
<div class="panel"><h2>Personal details</h2><form method="post" action="account_save.php" class="auth-form"><?= csrf_field() ?><label>Full name<input name="fullname" value="<?= e($user['fullname']) ?>" maxlength="120" required></label><label>Email<input type="email" value="<?= e($user['email']) ?>" disabled></label><label>Phone<input name="phone" value="<?= e($user['phone']) ?>" maxlength="30" required></label><label>Address<input name="address" value="<?= e($user['address']) ?>" maxlength="255" required></label><button class="btn btn-primary">Save my details</button></form></div>
<?php if($photographer): ?>
<div class="panel" style="margin-top:20px"><h2>Studio profile</h2><p class="muted">Shown on your public photographer page.</p>
<form method="post" action="account_save.php" enctype="multipart/form-data" class="auth-form"><?= csrf_field() ?><input type="hidden" name="action" value="studio">
<?php if($photographer['avatar_url']): ?><img class="avatar" src="<?= e($photographer['avatar_url']) ?>" alt="Current profile photo"><?php endif; ?>
<label>Change profile photo (JPG, PNG or WEBP, max 5 MB)<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
<label>Change cover photo (JPG, PNG or WEBP, max 5 MB)<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
<label>Studio name<input name="studio_name" value="<?= e($photographer['studio_name']) ?>" maxlength="150" required></label>
<label>Category<input name="category" value="<?= e($photographer['category']) ?>" maxlength="80" required></label>
<label>Location<input name="location" value="<?= e($photographer['location']) ?>" maxlength="100" required></label>
<label>About your work<textarea name="bio" rows="5" maxlength="4000"><?= e($photographer['bio']) ?></textarea></label>
<button class="btn btn-primary">Save studio profile</button></form>
<a class="btn btn-ghost" href="profile.php?id=<?= (int)$photographer['id'] ?>">View public profile</a></div>
<?php else: ?><div class="panel" style="margin-top:20px"><h2>Your bookings</h2><p class="muted">Check upcoming sessions and payment status from your dashboard.</p><a class="btn btn-primary" href="dashboard.php">View my bookings</a></div><?php endif; ?>
</section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
