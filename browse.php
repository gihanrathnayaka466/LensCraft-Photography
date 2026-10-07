<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$page_title = 'Explore Photographers | LensCraft';
$location = trim((string)($_GET['location'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$date = trim((string)($_GET['date'] ?? ''));
$maxPrice = trim((string)($_GET['max_price'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'recommended');
$validSorts = ['recommended','price_asc','price_desc','newest'];
if (!in_array($sort,$validSorts,true)) $sort='recommended';
if ($date !== '') {
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $date < date('Y-m-d')) $date='';
}
if ($maxPrice !== '' && (!ctype_digit($maxPrice) || (int)$maxPrice < 1)) $maxPrice='';
$where=['p.is_active=1']; $types=''; $params=[];
if ($location!=='') { $where[]='p.location LIKE ?'; $types.='s'; $params[]='%'.$location.'%'; }
if ($category!=='') { $where[]='p.category LIKE ?'; $types.='s'; $params[]='%'.$category.'%'; }
if ($maxPrice!=='') {
    $where[]='COALESCE((SELECT MIN(px.price) FROM packages px WHERE px.photographer_id=p.id AND px.is_active=1 AND px.stock_qty>0),p.base_price) <= ?';
    $types.='d'; $params[]=(float)$maxPrice;
}
if ($date!=='') {
    $where[]="EXISTS (
       SELECT 1 FROM availability av
       WHERE av.photographer_id=p.id AND av.available_date=? AND av.is_booked=0
       AND NOT EXISTS (
         SELECT 1 FROM bookings b
         WHERE b.photographer_id=p.id AND b.booking_date=av.available_date
           AND b.status IN ('pending','confirmed')
           AND b.start_time < av.end_time AND b.end_time > av.start_time
       )
    )";
    $types.='s'; $params[]=$date;
}
$sortSql = match($sort) {
    'price_asc'=>'starting_price ASC,p.id DESC',
    'price_desc'=>'starting_price DESC,p.id DESC',
    'newest'=>'p.id DESC',
    default=>'p.rating DESC,p.id DESC'
};
$sql="SELECT p.id,p.studio_name,p.category,p.location,p.bio,p.cover_url,p.avatar_url,p.rating,
  COALESCE((SELECT MIN(px.price) FROM packages px WHERE px.photographer_id=p.id AND px.is_active=1 AND px.stock_qty>0),p.base_price) AS starting_price,
  (SELECT COUNT(*) FROM packages px WHERE px.photographer_id=p.id AND px.is_active=1 AND px.stock_qty>0) AS package_count,
  (SELECT px.id FROM packages px WHERE px.photographer_id=p.id AND px.is_active=1 AND px.stock_qty>0 ORDER BY px.price,px.id LIMIT 1) AS starter_package_id
  FROM photographers p WHERE ".implode(' AND ',$where)." ORDER BY ".$sortSql;
$stmt=db()->prepare($sql);
if ($types!=='') $stmt->bind_param($types,...$params);
$stmt->execute();
$photographers=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count=count($photographers);
require_once __DIR__ . '/partials_header.php';
?>
<main class="lc-directory">
  <section class="lc-directory-hero">
    <div class="container lc-directory-hero-inner">
      <div class="lc-directory-intro">
        <span class="lc-overline"><i class="fa-solid fa-sparkles"></i> THE CREATIVE DIRECTORY</span>
        <h1>Find the perfect <em>eye for your story.</em></h1>
        <p>Discover real portfolios, explore photography packages, and find a photographer who fits your vision.</p>
        <div class="lc-directory-pills"><span><i class="fa-solid fa-camera"></i> Real photographer profiles</span><span><i class="fa-regular fa-calendar-check"></i> Booking availability</span><span><i class="fa-solid fa-location-dot"></i> Across Sri Lanka</span></div>
      </div>
      <div class="lc-directory-art" aria-hidden="true"><div class="lc-directory-art-img"></div><div class="lc-directory-art-note"><i class="fa-solid fa-heart"></i> Your moment, beautifully captured.</div></div>
    </div>
  </section>
  <div class="container lc-directory-body">
    <form class="lc-directory-filters" method="get" action="browse.php" id="browse-filters">
      <div class="lc-filter-title"><span><i class="fa-solid fa-sliders"></i> Refine your search</span><a href="browse.php">Clear all <i class="fa-solid fa-rotate-left"></i></a></div>
      <div class="lc-filter-fields">
        <label><small>LOCATION</small><div class="lc-field-icon"><i class="fa-solid fa-location-dot"></i><input name="location" value="<?= e($location) ?>" placeholder="e.g. Kandy, Matara"></div></label>
        <label><small>PHOTOGRAPHY STYLE</small><div class="lc-field-icon"><i class="fa-solid fa-camera"></i><select name="category"><option value="">All photography styles</option><?php foreach(['Wedding','Portrait','Event','Fashion','Commercial','Birthday','Pre-shoot','Cinematic'] as $c): ?><option value="<?= e($c) ?>" <?= $category===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div></label>
        <label><small>PREFERRED DATE</small><div class="lc-field-icon"><i class="fa-regular fa-calendar"></i><input type="date" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>"></div></label>
        <label><small>MAXIMUM PRICE (LKR)</small><div class="lc-field-icon"><i class="fa-solid fa-wallet"></i><input type="number" min="1" step="1000" name="max_price" value="<?= e($maxPrice) ?>" placeholder="Any budget"></div></label>
        <button type="submit" class="btn btn-primary lc-filter-apply"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
      </div>
    </form>
    <div class="lc-results-head">
      <div><span class="lc-overline">EXPLORE THE TALENT</span><h2>Photographers <em>worth discovering.</em></h2><p><?= $count ?> <?= $count===1?'photographer':'photographers' ?> match your search<?php if($date!==''): ?> for <?= e(date('j M Y',strtotime($date))) ?><?php endif; ?>.</p></div>
      <label class="lc-sort">Sort by <select name="sort" form="browse-filters" onchange="this.form.requestSubmit()"><option value="recommended" <?= $sort==='recommended'?'selected':'' ?>>Recommended</option><option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option><option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: low to high</option><option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: high to low</option></select></label>
    </div>
    <?php if($photographers): ?>
      <div class="lc-directory-grid">
        <?php foreach($photographers as $p): ?>
        <article class="lc-directory-card">
          <a class="lc-directory-cover" href="profile.php?id=<?= (int)$p['id'] ?>">
            <img loading="lazy" src="<?= e($p['cover_url'] ?: 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=1100&q=85') ?>" alt="<?= e($p['studio_name']) ?> photography">
            <span class="lc-directory-tag"><?= e($p['category'] ?: 'Photography') ?></span>
          </a>
          <div class="lc-directory-card-body">
            <div class="lc-directory-person">
              <div class="lc-directory-avatar"><?php if(!empty($p['avatar_url'])): ?><img loading="lazy" src="<?= e($p['avatar_url']) ?>" alt=""><?php else: ?><i class="fa-solid fa-camera"></i><?php endif; ?></div>
              <div><h3><?= e($p['studio_name']) ?></h3><span><i class="fa-solid fa-location-dot"></i> <?= e($p['location'] ?: 'Sri Lanka') ?></span></div>
            </div>
            <p class="lc-directory-bio"><?= e(substr((string)($p['bio'] ?? ''),0,115)) ?></p>
            <div class="lc-directory-card-foot"><div><small>STARTING AT · <?= (int)$p['package_count'] ?> PACKAGES</small><strong><?= money((float)$p['starting_price']) ?></strong></div><a href="profile.php?id=<?= (int)$p['id'] ?>" class="lc-directory-view">View profile <i class="fa-solid fa-arrow-right"></i></a></div><?php if (!empty($p['starter_package_id'])): ?><form class="lc-directory-add-form" method="post" action="cart.php"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="package_id" value="<?= (int)$p['starter_package_id'] ?>"><button type="submit"><i class="fa-solid fa-bag-shopping"></i> Add starting package to cart</button></form><?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="lc-directory-empty"><i class="fa-solid fa-camera-retro"></i><h3>No photographers found for these filters.</h3><p>Try a different location, date, photography style, or budget.</p><a class="btn btn-primary" href="browse.php">See all photographers</a></div>
    <?php endif; ?>
    <section class="lc-directory-cta"><div><span class="lc-overline">BEHIND THE LENS?</span><h2>Let your next client find you.</h2><p>Build your studio profile, share your work, and publish your packages.</p></div><a class="btn btn-primary" href="register.php">Join as a photographer <i class="fa-solid fa-arrow-right"></i></a></section>
  </div>
</main>
<?php require_once __DIR__ . '/partials_footer.php'; ?>
