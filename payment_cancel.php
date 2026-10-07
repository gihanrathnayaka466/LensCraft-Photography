<?php
require_once __DIR__ . '/bootstrap.php';
require_login(); $bid=(int)($_GET['booking']??0); $uid=user_id();
$stmt=db()->prepare("SELECT booking_code FROM bookings WHERE id=? AND user_id=?"); $stmt->bind_param('ii',$bid,$uid); $stmt->execute(); $b=$stmt->get_result()->fetch_assoc();
if($b){ $u=db()->prepare("UPDATE payments SET status='cancelled' WHERE booking_id=? AND status='pending'"); $u->bind_param('i',$bid); $u->execute(); }
$page_title='Payment Cancelled | LensCraft'; require_once __DIR__ . '/partials_header.php'; ?>
<section class="section container narrow"><div class="result panel"><div class="result-icon"><i class="fa-solid fa-circle-xmark"></i></div><span class="eyebrow">PAYMENT CANCELLED</span><h1>No payment was taken.</h1><p>Your booking is not confirmed. You can return to your bookings and try again.</p><a class="btn btn-primary" href="dashboard.php">My bookings</a></div></section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
