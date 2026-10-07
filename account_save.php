<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/upload_helpers.php';
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('account.php');
verify_csrf();
$uid=(int)user_id();$user=current_user();
try {
 if(($_POST['action']??'')==='studio'){
  if($user['role']!=='photographer'){http_response_code(403);exit('Access denied');}
  $s=db()->prepare('SELECT * FROM photographers WHERE user_id=? LIMIT 1');$s->bind_param('i',$uid);$s->execute();$p=$s->get_result()->fetch_assoc();
  if(!$p) throw new RuntimeException('Photographer profile not found.');
  $name=trim((string)($_POST['studio_name']??''));$cat=trim((string)($_POST['category']??''));$loc=trim((string)($_POST['location']??''));$bio=trim((string)($_POST['bio']??''));
  if(strlen($name)<2 || strlen($name)>150 || $cat==='' || strlen($cat)>80 || $loc==='' || strlen($loc)>100 || strlen($bio)>4000) throw new RuntimeException('Please enter valid studio details.');
  $avatar=$p['avatar_url'];$cover=$p['cover_url'];$created=[];
  try {
   if(($_FILES['avatar']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$avatar=store_photo($_FILES['avatar'],'avatar');$created[]=$avatar;}
   if(($_FILES['cover']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$cover=store_photo($_FILES['cover'],'cover');$created[]=$cover;}
   $q=db()->prepare('UPDATE photographers SET studio_name=?,category=?,location=?,bio=?,avatar_url=?,cover_url=? WHERE id=? AND user_id=?');
   $pid=(int)$p['id'];$q->bind_param('ssssssii',$name,$cat,$loc,$bio,$avatar,$cover,$pid,$uid);$q->execute();
  }catch(Throwable $e){foreach($created as $file)delete_local_photo($file);throw $e;}
  if($avatar!==$p['avatar_url']) delete_local_photo($p['avatar_url']);
  if($cover!==$p['cover_url']) delete_local_photo($p['cover_url']);
  flash('success','Studio profile updated.');
 }else{
  $name=trim((string)($_POST['fullname']??''));$phone=trim((string)($_POST['phone']??''));$address=trim((string)($_POST['address']??''));
  if(strlen($name)<2||strlen($name)>120||strlen($phone)<7||strlen($phone)>30||strlen($address)<3||strlen($address)>255) throw new RuntimeException('Please enter valid personal details.');
  $q=db()->prepare('UPDATE users SET fullname=?,phone=?,address=? WHERE id=?');$q->bind_param('sssi',$name,$phone,$address,$uid);$q->execute();
  flash('success','Personal details updated.');
 }
}catch(Throwable $e){error_log('Profile update: '.$e->getMessage());flash('error',$e instanceof RuntimeException?$e->getMessage():'Unable to save changes.');}
redirect('account.php');
