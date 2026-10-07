<?php
declare(strict_types=1);
// Include this file only after bootstrap.php.
function store_photo(array $file, string $prefix): string {
 if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a valid image to upload.');
 if (($file['size'] ?? 0) > 5*1024*1024 || ($file['size'] ?? 0) < 1) throw new RuntimeException('Image must be under 5 MB.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
 $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 if(!isset($allowed[$mime]) || @getimagesize($file['tmp_name'])===false) throw new RuntimeException('Only valid JPG, PNG or WEBP images are allowed.');
 $dir=__DIR__.'/uploads';
 if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Unable to create uploads directory.');
 $filename=$prefix.'-'.bin2hex(random_bytes(16)).'.'.$allowed[$mime];
 if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$filename)) throw new RuntimeException('Image upload failed.');
 return 'uploads/'.$filename;
}
function delete_local_photo(?string $path): void {
 if(!$path || !preg_match('#^uploads/[a-zA-Z0-9_-]+\.(jpg|png|webp)$#',$path)) return;
 $file=__DIR__.'/'.$path;
 if(is_file($file)) @unlink($file);
}
