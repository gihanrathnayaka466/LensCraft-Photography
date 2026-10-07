<?php
require_once __DIR__ . '/bootstrap.php';
$merchant_id=$_POST['merchant_id']??''; $order_id=$_POST['order_id']??''; $amount=$_POST['payhere_amount']??''; $currency=$_POST['payhere_currency']??''; $status=$_POST['status_code']??''; $md5=$_POST['md5sig']??'';
if($merchant_id!==PAYHERE_MERCHANT_ID || !$order_id || !$md5){ http_response_code(400); exit('Invalid notification'); }
$local=strtoupper(md5($merchant_id.$order_id.$amount.$currency.$status.strtoupper(md5(PAYHERE_MERCHANT_SECRET))));
if(!hash_equals($local,$md5)){ http_response_code(400); exit('Invalid signature'); }
$stmt=db()->prepare("SELECT id,booking_id,amount,status FROM payments WHERE order_id=? LIMIT 1"); $stmt->bind_param('s',$order_id); $stmt->execute(); $payment=$stmt->get_result()->fetch_assoc();
if(!$payment){ http_response_code(404); exit('Order not found'); }
$payAmount=(float)$amount;
if(abs($payAmount-(float)$payment['amount'])>0.01 || $currency!=='LKR'){ http_response_code(400); exit('Amount/currency mismatch'); }
$map=['2'=>['paid','confirmed'],'0'=>['pending','pending'],'-1'=>['cancelled','cancelled'],'-2'=>['failed','pending'],'-3'=>['chargedback','cancelled']];
[$payStatus,$bookingStatus]=$map[(string)$status]??['failed','pending'];
$paymentId=$_POST['payment_id']??null; $method=$_POST['method']??null; $message=$_POST['status_message']??null;
if($payment['status']==='paid' && $payStatus!=='paid'){http_response_code(200);exit('OK');}
if($payStatus==='paid'){
 db()->begin_transaction();
 try{
  $u=db()->prepare("UPDATE payments SET payment_id=?,method=?,status_code=?,status='paid',gateway_message=?,paid_at=NOW() WHERE id=? AND status<>'paid'");
  $u->bind_param('ssssi',$paymentId,$method,$status,$message,$payment['id']); $u->execute();
  $b=db()->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND status='pending'"); $b->bind_param('i',$payment['booking_id']); $b->execute();
  if ($u->affected_rows > 0) {
   $stock=db()->prepare("UPDATE packages pa JOIN bookings b ON b.package_id=pa.id SET pa.stock_qty=GREATEST(pa.stock_qty-1,0) WHERE b.id=?");
   $stock->bind_param('i',$payment['booking_id']); $stock->execute();
  }
  db()->commit();
 }catch(Throwable $e){ db()->rollback(); http_response_code(500); exit('Database error'); }
}else{
 $u=db()->prepare("UPDATE payments SET payment_id=?,method=?,status_code=?,status=?,gateway_message=? WHERE id=?");
 $u->bind_param('sssssi',$paymentId,$method,$status,$payStatus,$message,$payment['id']); $u->execute();
 if($bookingStatus==='cancelled'){ $b=db()->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND status='pending'"); $b->bind_param('i',$payment['booking_id']); $b->execute(); }
}
http_response_code(200); echo 'OK';
