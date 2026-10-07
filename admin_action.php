<?php
require_once __DIR__.'/bootstrap.php';
require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('admin.php');
verify_csrf(); $db=db(); $action=(string)($_POST['action']??'');
try{
 if($action==='booking_status'){
  $id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');
  if($id<1||!in_array($status,['pending','confirmed','completed','cancelled'],true))throw new RuntimeException('Invalid booking status.');
  $q=$db->prepare('UPDATE bookings SET status=? WHERE id=?');$q->bind_param('si',$status,$id);$q->execute();flash('success','Booking status saved.');
 }else{
  throw new RuntimeException('This action is not available to administrators. Photographer packages and prices are managed by the photographer.');
 }
}catch(Throwable $e){flash('error',$e instanceof RuntimeException?$e->getMessage():'Admin update failed.');}
redirect('admin.php');
