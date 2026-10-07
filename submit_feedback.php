<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {http_response_code(405);exit('Method not allowed');}
verify_csrf();
$pid=filter_input(INPUT_POST,'photographer_id',FILTER_VALIDATE_INT);
$bookingId=filter_input(INPUT_POST,'booking_id',FILTER_VALIDATE_INT);
$rating=filter_input(INPUT_POST,'rating',FILTER_VALIDATE_INT);
$comment=trim((string)($_POST['comment']??''));
if(!$pid||!$bookingId||!$rating||$rating<1||$rating>5||mb_strlen($comment)>1500||mb_strlen($comment)<10){
 flash('error','Please choose a rating and write feedback between 10 and 1500 characters.');
 redirect('profile.php?id='.(int)$pid.'#customer-feedback');
}
$uid=user_id();
try {
 // Only the actual customer of a completed booking can submit feedback.
 // Booking ID is never trusted without this ownership check.
 $q=db()->prepare("SELECT id FROM bookings WHERE id=? AND user_id=? AND photographer_id=? AND status='completed' LIMIT 1");
 $q->bind_param('iii',$bookingId,$uid,$pid);$q->execute();
 if(!$q->get_result()->fetch_assoc()){flash('error','Only customers with completed bookings can leave feedback.');redirect('profile.php?id='.$pid.'#customer-feedback');}
 $q=db()->prepare('INSERT INTO photographer_reviews(booking_id,photographer_id,user_id,rating,comment) VALUES(?,?,?,?,?)');
 $q->bind_param('iiiis',$bookingId,$pid,$uid,$rating,$comment);$q->execute();
 flash('success','Thank you! Your feedback has been published.');
} catch(mysqli_sql_exception $ex){
 if($ex->getCode()===1062){flash('error','Feedback has already been submitted for this booking.');}
 else {error_log('Feedback insert error: '.$ex->getMessage());flash('error','Unable to save feedback. Please check that the feedback database migration was imported.');}
}
redirect('profile.php?id='.$pid.'#customer-feedback');
