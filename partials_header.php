<?php
require_once __DIR__ . '/bootstrap.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<header class="nav">
  <a class="brand" href="index.php">LensCraft<span>.</span></a>
  <nav class="nav-links">
    <a href="index.php">Home</a><a href="browse.php">Browse</a>
    <?php if($user): ?><a href="dashboard.php">Dashboard</a><?php if(($user['role']??'')==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?><a href="account.php">My profile</a><?php endif; ?>
    <a class="cart-link" href="cart.php"><i class="fa-solid fa-bag-shopping"></i><span>Cart</span><b id="cart-count"><?= cart_count() ?></b></a>
  </nav>
  <div class="nav-actions">
    <?php if($user): ?>
      <a class="user-chip" href="account.php"><i class="fa-regular fa-circle-user"></i> <?= e($user['fullname']) ?></a>
      <a class="btn btn-ghost" href="logout.php">Sign out</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="login.php">Sign in</a>
      <a class="btn btn-primary" href="register.php">Create account</a>
    <?php endif; ?>
  </div>
</header>
<?php foreach(flashes() as [$type,$message]): ?><div class="toast toast-<?= e($type) ?>"><?= e($message) ?></div><?php endforeach; ?>
<main>
