<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'add') {
        $pid = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
        if (!$pid || $pid < 1) {
            flash('error', 'Please select a valid package.');
            redirect('browse.php');
        }
        $stmt = db()->prepare("SELECT pa.id, pa.name, pa.price, p.id AS photographer_id, p.user_id AS owner_id, p.studio_name FROM packages pa JOIN photographers p ON p.id=pa.photographer_id WHERE pa.id=? AND p.is_active=1 AND pa.is_active=1 AND pa.stock_qty>0 LIMIT 1");
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        if (!$item) {
            flash('error', 'This package is no longer available.');
            redirect('browse.php');
        }
        if (is_logged_in() && (int)$item['owner_id'] === (int)user_id()) {
            flash('error', 'You cannot book your own photography package.');
            redirect('profile.php?id=' . (int)$item['photographer_id']);
        }
        foreach (cart() as $entry) {
            if ((int)($entry['package_id'] ?? 0) === (int)$pid) {
                flash('error', 'This package is already in your cart. Select a different package or continue to booking.');
                redirect('cart.php');
            }
        }
        $_SESSION['cart'][] = [
            'package_id' => (int)$item['id'],
            'photographer_id' => (int)$item['photographer_id'],
            'photographer' => (string)$item['studio_name'],
            'package' => (string)$item['name'],
            'price' => (float)$item['price'],
        ];
        flash('success', 'Package added to your cart.');
        redirect('cart.php');
    }
    if ($action === 'remove') {
        $pid = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
        if ($pid && $pid > 0) {
            $_SESSION['cart'] = array_values(array_filter(cart(), static fn(array $x): bool => (int)($x['package_id'] ?? 0) !== $pid));
            flash('success', 'Package removed from your cart.');
        }
        redirect('cart.php');
    }
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('success', 'Your cart is now empty.');
        redirect('cart.php');
    }
    http_response_code(400);
    exit('Unknown cart action.');
}

$items = cart();
$page_title = 'Booking Cart | LensCraft';
require_once __DIR__ . '/partials_header.php';
?>
<section class="section container lc-cart-page">
  <div class="lc-cart-heading"><div><span class="eyebrow">YOUR PHOTOGRAPHY SELECTION</span><h1>Your booking cart<span>.</span></h1><p class="muted">Choose a session for each package before checkout.</p></div><a class="btn btn-ghost" href="browse.php"><i class="fa-solid fa-arrow-left"></i> Explore photographers</a></div>
  <?php if (!$items): ?>
  <div class="lc-cart-empty panel"><div class="lc-cart-empty-icon"><i class="fa-solid fa-bag-shopping"></i></div><h2>Nothing in your cart yet.</h2><p>Explore talented photographers and add a package you love.</p><a class="btn btn-primary" href="browse.php">Browse photographers <i class="fa-solid fa-arrow-right"></i></a></div>
  <?php else: ?>
  <div class="lc-cart-layout">
    <div class="panel lc-cart-items">
      <div class="lc-cart-panel-head"><h2>Selected packages <span><?= count($items) ?></span></h2><form method="post" onsubmit="return confirm('Remove all packages from your cart?')"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button type="submit" class="lc-cart-clear"><i class="fa-solid fa-trash-can"></i> Clear cart</button></form></div>
      <?php foreach ($items as $item): ?>
      <div class="lc-cart-item">
        <div class="lc-cart-item-icon"><i class="fa-solid fa-camera-retro"></i></div>
        <div class="lc-cart-item-info"><span class="lc-cart-studio"><?= e((string)$item['photographer']) ?></span><h3><?= e((string)$item['package']) ?></h3><small>1 photography session · Date selected at booking</small><a href="profile.php?id=<?= (int)$item['photographer_id'] ?>">View photographer <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
        <div class="lc-cart-item-end"><strong><?= money((float)$item['price']) ?></strong><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="package_id" value="<?= (int)$item['package_id'] ?>"><button class="lc-cart-remove" type="submit" aria-label="Remove <?= e((string)$item['package']) ?>"><i class="fa-solid fa-trash"></i> Remove</button></form></div>
      </div>
      <?php endforeach; ?>
      <div class="lc-cart-more"><i class="fa-solid fa-circle-info"></i> Each package represents one session. Book additional sessions separately to choose their own dates and times.</div>
    </div>
    <aside class="panel lc-cart-summary"><span class="eyebrow">YOUR ORDER</span><h2>Booking summary</h2><div class="lc-cart-summary-line"><span>Packages</span><strong><?= count($items) ?></strong></div><div class="lc-cart-summary-line"><span>Subtotal</span><strong><?= money(cart_total()) ?></strong></div><div class="lc-cart-summary-line"><span>Service fee</span><strong><?= money(0) ?></strong></div><div class="lc-cart-summary-total"><span>Total</span><strong><?= money(cart_total()) ?></strong></div><a class="btn btn-primary btn-full" href="booking.php">Continue to booking <i class="fa-solid fa-arrow-right"></i></a><p><i class="fa-solid fa-lock"></i> Booking dates and payment are confirmed in the next steps.</p></aside>
  </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
