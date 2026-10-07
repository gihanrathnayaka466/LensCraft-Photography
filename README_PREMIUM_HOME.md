# LensCraft Premium Homepage Upgrade

This version is based on LensCraft_Auto_Booking_Calendar_XAMPP.zip. It preserves the existing dashboard, booking, portfolio, login, and checkout files. The homepage (`index.php`) is rebuilt, and all new styling is appended to `style.css` under the `.lc-home` namespace.

## Install on XAMPP

1. Back up `C:\xampp\htdocs\photographsite` and export your `lenscraft_db` database in phpMyAdmin.
2. Extract this archive. Copy the contents **inside** `LensCraft_Advanced_XAMPP` into `C:\xampp\htdocs\photographsite`, preserving your existing `config.php` with your own database settings.
3. Do not delete your database. No new SQL migration is required for the homepage.
4. Open `http://localhost/photographsite/index.php` and hard-refresh (Ctrl+F5).

## Notes

- Featured photographer cards use live data from `photographers` and `packages`.
- The portfolio mosaic displays images from `portfolio_images`, only when the table has photos. If you haven't imported the portfolio migration, run `portfolio_images_migration.sql` in phpMyAdmin.
- Category links use `browse.php?category=...`, and the search form submits to the existing `browse.php` page.
- The preferred date field is passed to `browse.php`; **the current browse.php does not filter by date**. Do not represent this field as a verified availability search until that query is implemented.
- Category photography images and the decorative hero photos are external Unsplash images, not claims about your photographers' work. Real portfolio images are from the database.
- The PayHere setup and payment confirmation requirements are unchanged. Localhost cannot receive public PayHere callbacks.
- No demo reviews, booking counts or ratings were fabricated for the homepage.
