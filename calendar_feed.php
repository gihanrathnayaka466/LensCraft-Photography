<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Sign in required']); exit; }
$user=current_user();
if (!$user || $user['role']!=='photographer') { http_response_code(403); echo json_encode(['error'=>'Photographer account required']); exit; }
$uid=(int)user_id();
$q=db()->prepare('SELECT id FROM photographers WHERE user_id=? LIMIT 1');
$q->bind_param('i',$uid);$q->execute();$studio=$q->get_result()->fetch_assoc();
if (!$studio) { http_response_code(404); echo json_encode(['error'=>'Studio not found']); exit; }
$pid=(int)$studio['id'];
$q=db()->prepare("SELECT b.booking_date,b.start_time,b.end_time,b.status,b.booking_code,u.fullname,pa.name AS package_name FROM bookings b JOIN users u ON u.id=b.user_id JOIN packages pa ON pa.id=b.package_id WHERE b.photographer_id=? AND b.status IN ('pending','confirmed') AND b.booking_date>=DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND b.booking_date<DATE_ADD(CURDATE(), INTERVAL 24 MONTH) ORDER BY b.booking_date,b.start_time");
$q->bind_param('i',$pid);$q->execute();$bookings=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$q=db()->prepare("SELECT DISTINCT available_date FROM availability WHERE photographer_id=? AND is_booked=0 AND available_date>=DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND available_date<DATE_ADD(CURDATE(), INTERVAL 24 MONTH)");
$q->bind_param('i',$pid);$q->execute();$slots=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$q=db()->prepare("SELECT DISTINCT available_date FROM availability WHERE photographer_id=? AND is_booked=1");$q->bind_param('i',$pid);$q->execute();$blocked=array_column($q->get_result()->fetch_all(MYSQLI_ASSOC),'available_date');
echo json_encode(['bookings'=>$bookings,'booked'=>array_values(array_unique(array_merge(array_column($bookings,'booking_date'),$blocked))),'available'=>array_values(array_column($slots,'available_date'))],JSON_INVALID_UTF8_SUBSTITUTE);
