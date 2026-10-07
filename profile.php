<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
$id=(int)($_GET['id']??0);
$q=db()->prepare('SELECT * FROM photographers WHERE id=? AND is_active=1 LIMIT 1');$q->bind_param('i',$id);$q->execute();$p=$q->get_result()->fetch_assoc();if(!$p){http_response_code(404);exit('Photographer not found');}
$q=db()->prepare('SELECT * FROM packages WHERE photographer_id=? AND is_active=1 ORDER BY price');$q->bind_param('i',$id);$q->execute();$packages=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$q=db()->prepare('SELECT * FROM portfolio_images WHERE photographer_id=? ORDER BY id DESC LIMIT 40');$q->bind_param('i',$id);$q->execute();$photos=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$q=db()->prepare('SELECT * FROM availability WHERE photographer_id=? AND available_date>=CURDATE() ORDER BY available_date,start_time LIMIT 60');$q->bind_param('i',$id);$q->execute();$slots=$q->get_result()->fetch_all(MYSQLI_ASSOC);
// Reviews are optional until the migration has been imported.
$reviews=[];$reviewAverage=null;$eligibleBookings=[];$reviewDbReady=true;
try {
 $r=db()->prepare("SELECT r.rating,r.comment,r.created_at,u.fullname FROM photographer_reviews r JOIN users u ON u.id=r.user_id WHERE r.photographer_id=? ORDER BY r.created_at DESC LIMIT 40");
 $r->bind_param('i',$id);$r->execute();$reviews=$r->get_result()->fetch_all(MYSQLI_ASSOC);
 $avg=db()->prepare('SELECT ROUND(AVG(rating),1) AS average_rating, COUNT(*) AS total FROM photographer_reviews WHERE photographer_id=?');
 $avg->bind_param('i',$id);$avg->execute();$ratingStats=$avg->get_result()->fetch_assoc();
 $reviewAverage=$ratingStats['average_rating']===null?null:(float)$ratingStats['average_rating'];
 if(is_logged_in()){
   $uid=user_id();
   $r=db()->prepare("SELECT b.id,b.booking_code,b.booking_date FROM bookings b LEFT JOIN photographer_reviews rv ON rv.booking_id=b.id WHERE b.photographer_id=? AND b.user_id=? AND b.status='completed' AND rv.id IS NULL ORDER BY b.booking_date DESC LIMIT 20");
   $r->bind_param('ii',$id,$uid);$r->execute();$eligibleBookings=$r->get_result()->fetch_all(MYSQLI_ASSOC);
 }
} catch(mysqli_sql_exception $ex) {
 $reviewDbReady=false;
 error_log('Feedback database setup: '.$ex->getMessage());
}
$page_title=$p['studio_name'].' | LensCraft';require __DIR__.'/partials_header.php';
?>
<section class="public-cover" style="background-image:linear-gradient(0deg,#101219 0%,rgba(16,18,25,.08) 75%),url('<?= e($p['cover_url']??'') ?>')"></section>
<div class="container public-wrap"><div class="public-identity"><img src="<?= e($p['avatar_url']??'') ?>" alt="<?= e($p['studio_name']) ?>"><div><span class="eyebrow"><?= e($p['category']) ?></span><h1><?= e($p['studio_name']) ?></h1><p><i class="fa-solid fa-location-dot"></i> <?= e($p['location']) ?> &nbsp; <i class="fa-solid fa-star" style="color:#ffc65e"></i> <?= $reviewAverage===null ? 'No reviews yet' : e(number_format($reviewAverage,1)).' reviews' ?></p></div><?php if(current_user() && (int)$p['user_id']===(int)user_id()): ?><a class="btn btn-primary" href="dashboard.php">Manage studio</a><?php endif; ?></div>
<div class="public-layout"><div><div class="advanced-panel"><span class="eyebrow">MEET YOUR PHOTOGRAPHER</span><h2>About the studio</h2><p class="long-copy"><?= nl2br(e($p['bio']??'')) ?></p></div><div class="advanced-section"><div class="advanced-heading"><div><span class="eyebrow">SELECTED WORK</span><h2>Portfolio</h2></div><span class="muted"><?= count($photos) ?> photographs</span></div><div class="public-gallery"><?php foreach($photos as $photo): ?><figure><img src="<?= e($photo['image_path']) ?>" alt="<?= e($photo['caption']) ?>" loading="lazy"><figcaption><?= e($photo['caption']) ?></figcaption></figure><?php endforeach; ?><?php if(!$photos): ?><div class="advanced-panel muted">Portfolio photos will appear here when the photographer uploads them.</div><?php endif; ?></div></div><div class="advanced-section">
<div class="advanced-heading"><div><span class="eyebrow">LIVE AVAILABILITY</span><h2>Booking calendar</h2></div><span class="muted">Select a date to view times</span></div>
<div class="lc-public-calendar" data-feed="public_calendar_feed.php?photographer_id=<?= (int)$id ?>">
 <div class="lc-public-calendar-head"><button type="button" data-prev aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong data-month></strong><button type="button" data-next aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div>
 <div class="lc-public-calendar-grid" data-grid></div>
 <div class="lc-public-calendar-legend"><span><i class="lc-dot lc-green"></i> Available</span><span><i class="lc-dot lc-red"></i> Reserved</span><span><i class="lc-dot lc-gray"></i> Not published</span></div>
 <div class="lc-public-calendar-details" data-detail aria-live="polite">Select a date to view available and reserved times.</div>
 <small class="lc-public-calendar-sync" data-sync>Loading calendar…</small>
 <p class="lc-public-calendar-note">Reserved dates may still have free time slots. Final availability is checked when you submit a booking.</p>
</div></div></div>
<section class="advanced-section lc-feedback" id="customer-feedback">
<div class="advanced-heading"><div><span class="eyebrow">REAL CUSTOMER EXPERIENCES</span><h2>Customer comments &amp; ratings</h2></div>
<?php if($reviewAverage!==null): ?><span class="lc-feedback-score"><i class="fa-solid fa-star"></i> <?= e(number_format($reviewAverage,1)) ?> / 5</span><?php endif; ?></div>
<?php if(!$reviewDbReady): ?><div class="advanced-panel muted">Feedback is not enabled yet. Import <code>feedback_migration.sql</code> into your existing database.</div>
<?php else: ?>
<?php if($eligibleBookings): ?>
<div class="advanced-panel lc-feedback-form">
<h3>Share your experience</h3><p class="muted">Only customers with completed bookings can publish one review per booking.</p>
<form action="submit_feedback.php" method="post">
<?= csrf_field() ?><input type="hidden" name="photographer_id" value="<?= (int)$id ?>">
<label>Completed booking<select name="booking_id" required><?php foreach($eligibleBookings as $b): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['booking_code'].' · '.$b['booking_date']) ?></option><?php endforeach; ?></select></label>
<label>Rating<select name="rating" required><option value="">Choose a rating</option><?php for($star=5;$star>=1;$star--): ?><option value="<?= $star ?>"><?= $star ?> / 5 stars</option><?php endfor; ?></select></label>
<label>Your feedback<textarea name="comment" minlength="10" maxlength="1500" rows="4" required placeholder="Tell others about your experience…"></textarea></label>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Publish feedback</button>
</form></div>
<?php elseif(!is_logged_in()): ?><div class="advanced-panel"><p class="muted">Have you completed a photography booking? Sign in to leave a verified comment and rating.</p><a class="btn btn-primary" href="login.php">Sign in to comment</a></div><?php else: ?><div class="advanced-panel"><p class="muted">Comments are available after a completed photography booking. If you have already submitted feedback, it is displayed below.</p></div><?php endif; ?>
<?php if(!$reviews): ?><div class="advanced-panel muted">No customer reviews yet. Feedback from completed bookings will appear here.</div><?php else: ?>
<div class="lc-feedback-list"><?php foreach($reviews as $rv): ?>
<article class="lc-feedback-item"><div class="lc-feedback-review-head"><div class="lc-feedback-avatar"><i class="fa-solid fa-user"></i></div><div><strong><?= e($rv['fullname']) ?></strong><small>Verified booking · <?= e(date('M j, Y',strtotime($rv['created_at']))) ?></small></div><span class="lc-feedback-stars" aria-label="<?= (int)$rv['rating'] ?> out of 5 stars"><?= str_repeat('★',(int)$rv['rating']).str_repeat('☆',5-(int)$rv['rating']) ?></span></div><p><?= nl2br(e($rv['comment'])) ?></p></article>
<?php endforeach; ?></div>
<?php endif; ?>
<?php endif; ?>
</section>
<aside class="advanced-panel public-pricing"><span class="eyebrow">YOUR EXPERIENCE</span><h2>Choose a package</h2><p class="muted">Explore services and add your preferred package to the cart.</p><?php foreach($packages as $pack): ?><form method="post" action="cart.php" class="public-package"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="package_id" value="<?= (int)$pack['id'] ?>"><h3><?= e($pack['name']) ?></h3><p><?= nl2br(e($pack['description'])) ?></p><small><i class="fa-regular fa-clock"></i> <?= e((string)$pack['duration_hours']) ?> hours</small><strong><?= money((float)$pack['price']) ?></strong><button class="btn btn-primary btn-full">Add to cart <i class="fa-solid fa-arrow-right"></i></button></form><?php endforeach; ?><?php if(!$packages): ?><p class="muted">Packages coming soon.</p><?php endif; ?><p class="hint"><i class="fa-solid fa-lock"></i> Payments are processed through the configured payment gateway.</p></aside></div></div>
<script defer src="public_calendar.js"></script>
<?php require __DIR__.'/partials_footer.php'; ?>
