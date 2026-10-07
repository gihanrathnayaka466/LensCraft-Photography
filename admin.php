<?php
require_once __DIR__.'/bootstrap.php';
require_admin();
$db=db();

$stats=[];
$queries=[
 'customers'=>"SELECT COUNT(*) n FROM users WHERE role='customer'",
 'photographers'=>"SELECT COUNT(*) n FROM users WHERE role='photographer'",
 'bookings'=>"SELECT COUNT(*) n FROM bookings",
 'payments'=>"SELECT COUNT(*) n FROM payments WHERE status='paid'"
];
foreach($queries as $k=>$sql){$stats[$k]=(int)$db->query($sql)->fetch_assoc()['n'];}

$bookings=$db->query("SELECT b.id,b.booking_code,b.booking_date,b.amount,b.status,u.fullname,p.studio_name,pa.name package_name,COALESCE(pm.status,'pending') payment_status FROM bookings b JOIN users u ON u.id=b.user_id JOIN photographers p ON p.id=b.photographer_id JOIN packages pa ON pa.id=b.package_id LEFT JOIN payments pm ON pm.id=(SELECT MAX(id) FROM payments WHERE booking_id=b.id) ORDER BY b.created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$users=$db->query("SELECT id,fullname,email,role,created_at FROM users ORDER BY id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$photographers=$db->query("SELECT p.id,p.studio_name,p.location,p.category,p.is_active,u.fullname,u.email FROM photographers p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$payments=$db->query("SELECT pm.id,pm.status,pm.amount,pm.created_at,b.booking_code,u.fullname FROM payments pm JOIN bookings b ON b.id=pm.booking_id JOIN users u ON u.id=b.user_id ORDER BY pm.id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);

$page_title='Admin Dashboard | LensCraft'; require __DIR__.'/partials_header.php';
?>
<div class="container lc-admin-page">
 <div class="lc-admin-head"><div><span class="eyebrow">WEEK 08 · SYSTEM ADMINISTRATION</span><h1>Admin dashboard</h1><p class="muted">Manage users, photographers, bookings and payments. Package prices remain under each photographer's control.</p></div></div>
 <section class="lc-admin-stats">
  <div><span>Customers</span><strong><?= $stats['customers'] ?></strong></div><div><span>Photographers</span><strong><?= $stats['photographers'] ?></strong></div><div><span>Bookings</span><strong><?= $stats['bookings'] ?></strong></div><div><span>Paid payments</span><strong><?= $stats['payments'] ?></strong></div>
 </section>

 <section class="lc-card"><div class="lc-card-heading"><h2>Photographers</h2><span class="muted">System-level monitoring only</span></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Studio</th><th>Owner</th><th>Location</th><th>Category</th><th>Status</th></tr></thead><tbody>
 <?php foreach($photographers as $p): ?><tr><td>#<?= (int)$p['id'] ?></td><td><strong><?= e($p['studio_name']) ?></strong></td><td><?= e($p['fullname']) ?><small><?= e($p['email']) ?></small></td><td><?= e($p['location']) ?></td><td><?= e($p['category']) ?></td><td><span class="status"><?= $p['is_active']?'Active':'Inactive' ?></span></td></tr><?php endforeach; ?>
 <?php if(!$photographers): ?><tr><td colspan="6" class="lc-empty">No photographers registered yet.</td></tr><?php endif; ?>
 </tbody></table></div></section>

 <section class="lc-card"><div class="lc-card-heading"><h2>Customer bookings / orders</h2></div><div class="table-wrap"><table><thead><tr><th>Booking</th><th>Customer</th><th>Photographer / Package</th><th>Total</th><th>Payment</th><th>Booking status</th></tr></thead><tbody>
 <?php foreach($bookings as $b): ?><tr><td><strong><?= e($b['booking_code']) ?></strong><small><?= e($b['booking_date']) ?></small></td><td><?= e($b['fullname']) ?></td><td><?= e($b['studio_name'].' · '.$b['package_name']) ?></td><td><?= money((float)$b['amount']) ?></td><td><span class="status <?= e($b['payment_status']) ?>"><?= e(ucfirst($b['payment_status'])) ?></span></td><td><form method="post" action="admin_action.php" class="lc-inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="booking_status"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><select name="status"><?php foreach(['pending','confirmed','completed','cancelled'] as $st): ?><option value="<?= $st ?>" <?= $b['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select><button class="btn btn-ghost">Update</button></form></td></tr><?php endforeach; ?>
 <?php if(!$bookings): ?><tr><td colspan="6" class="lc-empty">No bookings yet.</td></tr><?php endif; ?>
 </tbody></table></div></section>

 <section class="lc-card"><div class="lc-card-heading"><h2>Payments</h2><span class="muted">Monitor gateway payment records</span></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Booking</th><th>Customer</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead><tbody>
 <?php foreach($payments as $pm): ?><tr><td>#<?= (int)$pm['id'] ?></td><td><?= e($pm['booking_code']) ?></td><td><?= e($pm['fullname']) ?></td><td><?= money((float)$pm['amount']) ?></td><td><span class="status <?= e($pm['status']) ?>"><?= e(ucfirst($pm['status'])) ?></span></td><td><?= e($pm['created_at']) ?></td></tr><?php endforeach; ?>
 <?php if(!$payments): ?><tr><td colspan="6" class="lc-empty">No payment records yet.</td></tr><?php endif; ?>
 </tbody></table></div></section>

 <section class="lc-card"><div class="lc-card-heading"><h2>Users & role-based access</h2></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td>#<?= (int)$u['id'] ?></td><td><?= e($u['fullname']) ?></td><td><?= e($u['email']) ?></td><td><span class="status"><?= e($u['role']) ?></span></td><td><?= e($u['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</div>
<?php require __DIR__.'/partials_footer.php'; ?>
