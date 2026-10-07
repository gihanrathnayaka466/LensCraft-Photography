<?php
require_once __DIR__ . '/bootstrap.php';
require_login(); $bid=(int)($_GET['booking']??0);
$stmt=db()->prepare("SELECT b.*,p.studio_name,pa.name package_name,u.fullname,u.email,u.phone,u.address,pm.order_id,pm.status AS payment_status FROM bookings b JOIN photographers p ON p.id=b.photographer_id JOIN packages pa ON pa.id=b.package_id JOIN users u ON u.id=b.user_id JOIN payments pm ON pm.booking_id=b.id WHERE b.id=? AND b.user_id=? ORDER BY pm.id DESC LIMIT 1");
$uid=user_id(); $stmt->bind_param('ii',$bid,$uid); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc();
if(!$booking){ http_response_code(404); exit('Booking not found'); }
if($booking['payment_status']==='paid'||$booking['status']==='cancelled'){flash('error','This booking is already paid or cancelled.');redirect('dashboard.php');}
$payhereConfigured=PAYHERE_MERCHANT_ID!=='YOUR_MERCHANT_ID' && PAYHERE_MERCHANT_SECRET!=='YOUR_MERCHANT_SECRET' && PAYHERE_MERCHANT_ID!=='' && PAYHERE_MERCHANT_SECRET!=='';
// The customer can review and correct billing/contact details before leaving for PayHere.
// These are stored only in the current session, not in the payments database.
[$defaultFirst,$defaultLast]=split_name($booking['fullname']);
$billing=$_SESSION['checkout_billing'][$bid] ?? [
 'first_name'=>$defaultFirst,'last_name'=>$defaultLast,'email'=>$booking['email'],
 'phone'=>$booking['phone'] ?? '', 'address'=>$booking['address'] ?? '',
 'city'=>'Colombo', 'country'=>'Sri Lanka'
];
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $fields=['first_name'=>100,'last_name'=>100,'email'=>190,'phone'=>30,'address'=>250,'city'=>100];
 $candidate=[];
 foreach($fields as $key=>$max) {
  $value=trim((string)($_POST[$key] ?? ''));
  if ($value==='' || mb_strlen($value)>$max) {flash('error','Please complete all billing fields.');redirect('checkout.php?booking='.$bid);}
  $candidate[$key]=$value;
 }
 if(!filter_var($candidate['email'],FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9() .-]{7,30}$/',$candidate['phone'])) {
  flash('error','Please enter a valid email and phone number.');redirect('checkout.php?booking='.$bid);
 }
 $candidate['country']='Sri Lanka';
 $_SESSION['checkout_billing'][$bid]=$candidate;
 redirect('checkout.php?booking='.$bid.'#payhere-payment');
}
$page_title='Secure Checkout | LensCraft'; require_once __DIR__ . '/partials_header.php';
$payhereAmount = number_format((float)$booking['amount'], 2, '.', '');
$hash = strtoupper(md5(
    PAYHERE_MERCHANT_ID .
    $booking['order_id'] .
    $payhereAmount .
    CURRENCY .
    strtoupper(md5(PAYHERE_MERCHANT_SECRET))
));
$payhereAction = PAYHERE_MODE === 'sandbox'
    ? 'https://sandbox.payhere.lk/pay/checkout'
    : 'https://www.payhere.lk/pay/checkout';
?>
<section class="section container narrow">
 <div class="checkout-head"><span class="eyebrow">STEP 2 OF 2</span><h1>Secure checkout</h1><p class="muted">You will be redirected to PayHere to complete the payment.</p></div>
 <div class="checkout-grid">
  <div class="panel">
   <h2>Booking</h2><div class="receipt"><div><span class="muted">Booking code</span><strong><?= e($booking['booking_code']) ?></strong></div><div><span class="muted">Photographer</span><strong><?= e($booking['studio_name']) ?></strong></div><div><span class="muted">Package</span><strong><?= e($booking['package_name']) ?></strong></div><div><span class="muted">Date & time</span><strong><?= e($booking['booking_date'].' · '.$booking['start_time'].'–'.$booking['end_time']) ?></strong></div></div>
   <div class="security-note"><i class="fa-solid fa-lock"></i><span>Payment is processed by PayHere. LensCraft does not receive or store your card number or CVV.</span></div>
  </div>
  <aside class="panel summary" id="payhere-payment"><h2>Amount due</h2><div class="sum-total"><span>Total</span><strong><?= money((float)$booking['amount']) ?></strong></div>
   <?php if(!$payhereConfigured): ?><div class="alert error">PayHere sandbox credentials are not configured. Add your own sandbox Merchant ID and Merchant Secret in config.php. No payment has been made.</div><?php endif; ?>
   <form method="post" action="checkout.php?booking=<?= (int)$bid ?>" class="lc-billing-form">
    <?= csrf_field() ?>
    <h3>Billing & contact details</h3>
    <div class="lc-billing-two"><label>First name<input name="first_name" value="<?= e($billing['first_name']) ?>" required maxlength="100"></label><label>Last name<input name="last_name" value="<?= e($billing['last_name']) ?>" required maxlength="100"></label></div>
    <label>Email<input type="email" name="email" value="<?= e($billing['email']) ?>" required maxlength="190"></label>
    <label>Phone<input type="tel" name="phone" value="<?= e($billing['phone']) ?>" required maxlength="30"></label>
    <label>Billing address<input name="address" value="<?= e($billing['address']) ?>" required maxlength="250"></label>
    <label>City<input name="city" value="<?= e($billing['city']) ?>" required maxlength="100"></label>
    <label>Country<input value="Sri Lanka" disabled></label>
    <button type="submit" class="btn btn-secondary btn-full">Save billing details</button>
   </form>
   <div class="lc-payment-warning">Sandbox payments only. PayHere must send a verified server callback before a booking is marked paid. localhost cannot receive public PayHere callbacks.</div>
   <form method="post" action="<?= e($payhereAction) ?>">
    <input type="hidden" name="merchant_id" value="<?= e(PAYHERE_MERCHANT_ID) ?>">
    <input type="hidden" name="return_url" value="<?= e(APP_URL.'/payment_return.php?booking='.$bid) ?>">
    <input type="hidden" name="cancel_url" value="<?= e(APP_URL.'/payment_cancel.php?booking='.$bid) ?>">
    <input type="hidden" name="notify_url" value="<?= e(APP_URL.'/payhere_notify.php') ?>">
    <input type="hidden" name="first_name" value="<?= e($billing['first_name']) ?>"><input type="hidden" name="last_name" value="<?= e($billing['last_name']) ?>">
    <input type="hidden" name="email" value="<?= e($billing['email']) ?>"><input type="hidden" name="phone" value="<?= e($billing['phone']) ?>">
    <input type="hidden" name="address" value="<?= e($billing['address']) ?>"><input type="hidden" name="city" value="<?= e($billing['city']) ?>"><input type="hidden" name="country" value="Sri Lanka">
    <input type="hidden" name="order_id" value="<?= e($booking['order_id']) ?>"><input type="hidden" name="items" value="<?= e($booking['studio_name'].' - '.$booking['package_name']) ?>">
    <input type="hidden" name="currency" value="LKR"><input type="hidden" name="amount" value="<?= e($payhereAmount) ?>"><input type="hidden" name="hash" value="<?= e($hash) ?>">
    <button <?= !$payhereConfigured?'disabled title="Configure PayHere first"':'' ?> class="btn btn-primary btn-full btn-pay"><i class="fa-solid fa-lock"></i> Pay <?= money((float)$booking['amount']) ?></button>
   </form>
  </aside>
 </div>
</section>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
