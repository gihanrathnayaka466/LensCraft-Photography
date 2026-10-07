<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/upload_helpers.php';
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('dashboard.php');
verify_csrf();
$uid=(int)user_id();$user=current_user();
if($user['role']!=='photographer'){http_response_code(403);exit('Access denied');}
$s=db()->prepare('SELECT id FROM photographers WHERE user_id=? LIMIT 1');$s->bind_param('i',$uid);$s->execute();$pid=(int)($s->get_result()->fetch_assoc()['id']??0);
if(!$pid){flash('error','Studio not found.');redirect('dashboard.php');}
$action=(string)($_POST['action']??'');
try {
 switch($action){
  case 'add_photo':
   $path=store_photo($_FILES['photo']??[],'portfolio');$caption=trim((string)($_POST['caption']??''));$caption=substr($caption,0,255);
   try{$q=db()->prepare('INSERT INTO portfolio_images(photographer_id,image_path,caption) VALUES(?,?,?)');$q->bind_param('iss',$pid,$path,$caption);$q->execute();}catch(Throwable $e){delete_local_photo($path);throw $e;}
   flash('success','Portfolio photo uploaded.');break;
  case 'delete_photo':
   $id=(int)($_POST['id']??0);$q=db()->prepare('SELECT image_path FROM portfolio_images WHERE id=? AND photographer_id=?');$q->bind_param('ii',$id,$pid);$q->execute();$row=$q->get_result()->fetch_assoc();
   if(!$row) throw new RuntimeException('Photo not found.');
   $q=db()->prepare('DELETE FROM portfolio_images WHERE id=? AND photographer_id=?');$q->bind_param('ii',$id,$pid);$q->execute();delete_local_photo($row['image_path']);flash('success','Photo removed.');break;
  case 'block_day':
   $date=(string)($_POST['block_date']??'');$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
   if(!$d||$d->format('Y-m-d')!==$date||$date<date('Y-m-d'))throw new RuntimeException('Select a valid future date.');
   $db=db();$db->begin_transaction();
   try{
    $lock=$db->prepare('SELECT id FROM photographers WHERE id=? FOR UPDATE');$lock->bind_param('i',$pid);$lock->execute();
    $check=$db->prepare("SELECT id FROM bookings WHERE photographer_id=? AND booking_date=? AND status IN ('pending','confirmed') LIMIT 1");$check->bind_param('is',$pid,$date);$check->execute();
    if($check->get_result()->fetch_assoc())throw new RuntimeException('Cannot block a day with a customer booking.');
    $del=$db->prepare('DELETE FROM availability WHERE photographer_id=? AND available_date=?');$del->bind_param('is',$pid,$date);$del->execute();
    $insert=$db->prepare("INSERT INTO availability(photographer_id,available_date,start_time,end_time,is_booked) VALUES(? ,?,'00:00','23:59',1)");$insert->bind_param('is',$pid,$date);$insert->execute();
    $db->commit();flash('success','Date marked RED / blocked.');
   }catch(Throwable $e){$db->rollback();throw $e;}break;
  case 'unblock_day':
   $date=(string)($_POST['block_date']??'');$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
   if(!$d||$d->format('Y-m-d')!==$date||$date<date('Y-m-d'))throw new RuntimeException('Select a valid future date.');
   $del=db()->prepare('DELETE FROM availability WHERE photographer_id=? AND available_date=? AND is_booked=1');$del->bind_param('is',$pid,$date);$del->execute();
   flash('success','Date marked GREEN / available.');break;
  case 'add_slot':
   $date=(string)($_POST['available_date']??'');$start=(string)($_POST['start_time']??'');$end=(string)($_POST['end_time']??'');
   $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
   if(!$d||$d->format('Y-m-d')!==$date||$date<date('Y-m-d')||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$start)||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$end)||$start>=$end) throw new RuntimeException('Choose a valid future date and time range.');
   $q=db()->prepare('SELECT id FROM availability WHERE photographer_id=? AND available_date=? AND start_time<? AND end_time>? LIMIT 1');$q->bind_param('isss',$pid,$date,$end,$start);$q->execute();if($q->get_result()->num_rows)throw new RuntimeException('This time overlaps an existing slot.');
   $q=db()->prepare('INSERT INTO availability(photographer_id,available_date,start_time,end_time) VALUES(?,?,?,?)');$q->bind_param('isss',$pid,$date,$start,$end);$q->execute();flash('success','Available date added.');break;
  case 'toggle_slot':
   $id=(int)($_POST['id']??0);
   $q=db()->prepare('SELECT * FROM availability WHERE id=? AND photographer_id=? LIMIT 1');$q->bind_param('ii',$id,$pid);$q->execute();$slot=$q->get_result()->fetch_assoc();
   if(!$slot)throw new RuntimeException('Slot not found.');
   $q=db()->prepare("SELECT id FROM bookings WHERE photographer_id=? AND booking_date=? AND status IN ('pending','confirmed') AND start_time<? AND end_time>? LIMIT 1");
   $q->bind_param('isss',$pid,$slot['available_date'],$slot['end_time'],$slot['start_time']);$q->execute();
   if($q->get_result()->num_rows)throw new RuntimeException('Cannot change a slot with active customer bookings. Manage the booking first.');
   $new=(int)$slot['is_booked']===1?0:1;
   $q=db()->prepare('UPDATE availability SET is_booked=? WHERE id=? AND photographer_id=?');$q->bind_param('iii',$new,$id,$pid);$q->execute();
   flash('success',$new?'Slot marked as blocked.':'Slot published as available.');break;
  case 'delete_slot':
   $id=(int)($_POST['id']??0);
   $q=db()->prepare("SELECT a.* FROM availability a WHERE a.id=? AND a.photographer_id=? LIMIT 1");$q->bind_param('ii',$id,$pid);$q->execute();$slot=$q->get_result()->fetch_assoc();if(!$slot)throw new RuntimeException('Slot not found.');
   $q=db()->prepare("SELECT id FROM bookings WHERE photographer_id=? AND booking_date=? AND status IN ('pending','confirmed') AND start_time<? AND end_time>? LIMIT 1");$q->bind_param('isss',$pid,$slot['available_date'],$slot['end_time'],$slot['start_time']);$q->execute();if($q->get_result()->num_rows)throw new RuntimeException('Cannot delete a slot with an active booking.');
   $q=db()->prepare('DELETE FROM availability WHERE id=? AND photographer_id=?');$q->bind_param('ii',$id,$pid);$q->execute();flash('success','Available date removed.');break;
  case 'add_package':
   $name=trim((string)($_POST['package_name']??''));$desc=trim((string)($_POST['package_description']??''));$price=filter_var($_POST['package_price']??null,FILTER_VALIDATE_FLOAT);$duration=filter_var($_POST['duration_hours']??null,FILTER_VALIDATE_FLOAT);
   if(strlen($name)<2||strlen($name)>120||strlen($desc)>2000||$price===false||$price<=0||$duration===false||$duration<=0||$duration>48)throw new RuntimeException('Enter valid package name, price and duration.');
   $q=db()->prepare('INSERT INTO packages(photographer_id,name,description,price,duration_hours) VALUES(?,?,?,?,?)');$q->bind_param('issdd',$pid,$name,$desc,$price,$duration);$q->execute();flash('success','Package added.');break;
  case 'edit_package':
   $id=(int)($_POST['id']??0);$name=trim((string)($_POST['package_name']??''));$desc=trim((string)($_POST['package_description']??''));$price=filter_var($_POST['package_price']??null,FILTER_VALIDATE_FLOAT);$duration=filter_var($_POST['duration_hours']??null,FILTER_VALIDATE_FLOAT);
   if(strlen($name)<2||strlen($name)>120||strlen($desc)>2000||$price===false||$price<=0||$duration===false||$duration<=0||$duration>48)throw new RuntimeException('Enter valid package name, price and duration.');
   $q=db()->prepare('UPDATE packages SET name=?,description=?,price=?,duration_hours=? WHERE id=? AND photographer_id=?');$q->bind_param('ssddii',$name,$desc,$price,$duration,$id,$pid);$q->execute();
   flash('success','Package updated.');break;
  case 'delete_package':
   $id=(int)($_POST['id']??0);$q=db()->prepare('SELECT id FROM bookings WHERE package_id=? LIMIT 1');$q->bind_param('i',$id);$q->execute();if($q->get_result()->num_rows)throw new RuntimeException('Cannot delete a package used by bookings.');
   $q=db()->prepare('DELETE FROM packages WHERE id=? AND photographer_id=?');$q->bind_param('ii',$id,$pid);$q->execute();if(!$q->affected_rows)throw new RuntimeException('Package not found.');flash('success','Package removed.');break;
  default: throw new RuntimeException('Unknown action.');
 }
}catch(Throwable $e){error_log('Studio management: '.$e->getMessage());flash('error',$e instanceof RuntimeException?$e->getMessage():'Unable to complete this action.');}
redirect('dashboard.php');
