# LensCraft Advanced – XAMPP setup

This is an upgraded version of the supplied `payhere_notify.zip` project. It includes the **existing PHP site** plus a new studio dashboard, portfolio gallery, package editing, availability selection, a refreshed public profile, and a customer bookings dashboard. It is a development build, **not a certified production payment platform**.

## Installation
1. **Backup first**: export the existing `lenscraft_db` database in phpMyAdmin and make a copy of `C:\xampp\htdocs\photographsite` (especially `uploads/`).
2. Extract the contents of this ZIP directly into `C:\xampp\htdocs\photographsite\`. Do not place them in a second nested folder and do not mix them with earlier ZIPs. Keep any user-uploaded files from your old `uploads/` backup that are not present in this ZIP.
3. Keep your existing `lenscraft_db` database. The upgrade uses the existing schema; it does not require dropping or recreating your database. If `portfolio_images` is missing, import `portfolio_images_migration.sql` **only**.
4. In `config.php`, check `APP_URL = 'http://localhost/photographsite'` and the database connection (`root` with empty password is the common local XAMPP default).
5. Start Apache and MySQL in XAMPP. Open `http://localhost/photographsite/`.
6. Photographer login -> `dashboard.php`: upload sample photos, edit packages, publish/delete slots, inspect bookings; `account.php`: change studio details, avatar and cover. Customer login -> `dashboard.php`: bookings and payment statuses. `profile.php?id=...` shows a public photographer portfolio and package selection.
7. Booking now requires an available slot published by the photographer; choose one from the dropdown. This also checks overlapping pending/confirmed bookings with a transactional photographer row lock.

## Payments: important
- Placeholder `YOUR_MERCHANT_ID` and `YOUR_MERCHANT_SECRET` do **not** work. The PayHere button is disabled until credentials are configured.
- Use your own **sandbox** Merchant ID and Merchant Secret for sandbox testing. Never publish the secret or commit it to a public repository.
- PayHere cannot deliver server-to-server notifications to `localhost`. To test the actual verified callback, use a public HTTPS domain and configure `APP_URL` accordingly. Browser return is not payment proof. Never manually mark a booking paid.
- Payment callbacks are signature/amount/currency checked in `payhere_notify.php`. This is not a substitute for full integration, security and reconciliation testing before accepting real payments.
- Pending reservations currently hold a slot until manually resolved; add an expiration/retry policy before going live.

## Technical notes
- PHP 8.1+ with mysqli/mysqlnd, fileinfo and mbstring recommended.
- All PHP files were syntax checked with `php -l`; a live MySQL/browser/PayHere end-to-end test was **not** performed here.
- Photos are validated and randomized. Keep PHP execution disabled in `uploads/` (included `.htaccess`).
- Demo content in `schema.sql` is optional. **Do not re-import schema.sql into an existing production-like database without a backup.**
