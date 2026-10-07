# LensCraft – XAMPP Profile Upgrade

1. Back up `C:\xampp\htdocs\photographsite` and export `lenscraft_db` in phpMyAdmin.
2. Copy all files from this ZIP into `C:\xampp\htdocs\photographsite\` (not a nested folder). Do not mix files from previous ZIP versions.
3. Keep the existing `lenscraft_db`; this upgrade uses the existing `users`, `photographers`, `packages`, `availability`, `bookings`, `payments`, and `portfolio_images` tables from the provided schema.sql. If `portfolio_images` does not exist, import only `portfolio_images_migration.sql`. Do not re-import all demo data or drop the database.
4. In XAMPP start Apache + MySQL; open http://localhost/photographsite/register.php and create customer or photographer. After registering, you are signed in and redirected to `account.php`. Sign in redirects to `dashboard.php`.
5. Photographer: `account.php` to change avatar/cover/studio, `dashboard.php` to upload/remove sample photos, add/delete dates, add/delete packages and see bookings. Customer: `account.php` to edit personal info, `dashboard.php` to see bookings and payment state.
6. Uploaded images go to `uploads/`. Make sure PHP file uploads are enabled and this folder is writable by Apache. Max image size 5MB.
7. PayHere requires real sandbox merchant credentials. Localhost cannot receive PayHere notify callbacks; use a publicly reachable HTTPS callback URL for end-to-end gateway testing. Never mark a booking paid based on return_url alone.
8. Deleting a package with any booking is blocked. Deleting availability overlapping pending/confirmed bookings is blocked. These are development safeguards; real concurrent booking prevention needs transactional locking and payment timeout rules before production.

## Changed/new files
`account.php`, `account_save.php`, `studio_manage.php`, `upload_helpers.php`, `dashboard.php`, `profile.php`, `partials_header.php`, `login.php`, `register.php`, `checkout.php`, `style.css`, plus existing project files.

## Notes
- All user uploads are validated by MIME type and size and stored under randomized filenames. Do not grant executable permissions to uploads.
- This is not a claim of live payment certification or full production security testing.
