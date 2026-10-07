<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$id=filter_input(INPUT_GET,'photographer_id',FILTER_VALIDATE_INT);
if (!$id || $id<1) {http_response_code(400);echo json_encode(['error'=>'Invalid photographer']);exit;}
$q=db()->prepare('SELECT id FROM photographers WHERE id=? AND is_active=1 LIMIT 1');
$q->bind_param('i',$id);$q->execute();
if (!$q->get_result()->fetch_assoc()) {http_response_code(404);echo json_encode(['error'=>'Photographer not found']);exit;}
$q=db()->prepare("SELECT a.available_date,a.start_time,a.end_time,a.is_booked,
 EXISTS(SELECT 1 FROM bookings b WHERE b.photographer_id=a.photographer_id
 AND b.booking_date=a.available_date AND b.status IN ('pending','confirmed')
 AND b.start_time<a.end_time AND b.end_time>a.start_time) AS overlaps
 FROM availability a WHERE a.photographer_id=?
 AND a.available_date BETWEEN DATE_SUB(CURDATE(),INTERVAL 1 MONTH) AND DATE_ADD(CURDATE(),INTERVAL 18 MONTH)
 ORDER BY a.available_date,a.start_time");
$q->bind_param('i',$id);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);
$dates=[];$blockedDates=[];
foreach($rows as $r) {
 $day=$r['available_date'];
 if(!isset($dates[$day]))$dates[$day]=['available'=>[],'reserved'=>[]];
 $time=substr($r['start_time'],0,5).' – '.substr($r['end_time'],0,5);
 if((int)$r['is_booked']===1 || (int)$r['overlaps']===1){$dates[$day]['reserved'][]=$time;if((int)$r['is_booked']===1)$blockedDates[$day]=true;}
 else $dates[$day]['available'][]=$time;
}
$q=db()->prepare("SELECT booking_date,start_time,end_time FROM bookings
 WHERE photographer_id=? AND status IN ('pending','confirmed')
 AND booking_date BETWEEN DATE_SUB(CURDATE(),INTERVAL 1 MONTH) AND DATE_ADD(CURDATE(),INTERVAL 18 MONTH)");
$q->bind_param('i',$id);$q->execute();
foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $r){
 $day=$r['booking_date'];$time=substr($r['start_time'],0,5).' – '.substr($r['end_time'],0,5);
 if(!isset($dates[$day]))$dates[$day]=['available'=>[],'reserved'=>[]];
 if(!in_array($time,$dates[$day]['reserved'],true))$dates[$day]['reserved'][]=$time;
}
echo json_encode(['dates'=>$dates,'blocked'=>array_keys($blockedDates)],JSON_INVALID_UTF8_SUBSTITUTE);
