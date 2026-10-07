<?php
require_once __DIR__ . '/bootstrap.php';
require_login(); $bid=(int)($_GET['booking']??0); $uid=user_id();
$stmt=db()->prepare("SELECT b.*,p.studio_name,pa.name package_name,pm.status payment_status FROM bookings b JOIN photographers p ON p.id=b.photographer_id JOIN packages pa ON pa.id=b.package_id JOIN payments pm ON pm.booking_id=b.id WHERE b.id=? AND b.user_id=? LIMIT 1"); $stmt->bind_param('ii',$bid,$uid); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc();
if(!$booking) exit('Booking not found');
$page_title='Payment Status | LensCraft'; require_once __DIR__ . '/partials_header.php';
$paid=$booking['payment_status']==='paid';
?>
<section class="section container narrow"><div class="result panel <?= $paid?'success-box':'pending-box' ?>"><div class="result-icon"><i class="fa-solid <?= $paid?'fa-circle-check':'fa-clock' ?>"></i></div><span class="eyebrow"><?= $paid?'PAYMENT CONFIRMED':'PAYMENT PROCESSING' ?></span><h1><?= $paid?'Booking confirmed':'Payment is being verified' ?></h1><p><?= $paid?'Your payment has been verified and your booking is confirmed.':'The gateway has returned you to LensCraft. The server callback will update the payment status securely.' ?></p><div class="receipt"><div><span>Booking code</span><strong><?= e($booking['booking_code']) ?></strong></div><div><span>Photographer</span><strong><?= e($booking['studio_name']) ?></strong></div><div><span>Amount</span><strong><?= money((float)$booking['amount']) ?></strong></div></div><a class="btn btn-primary" href="dashboard.php">Go to my bookings</a></div></section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
