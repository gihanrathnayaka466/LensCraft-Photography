# LensCraft Premium Dashboard (XAMPP)

This version upgrades the **actual dashboard.php** to a premium, responsive studio workspace inspired by the screenshot. It keeps existing MySQL-backed portfolio, packages, availability, bookings, and customer account behavior. No fake bookings, ratings, or sample reviews are inserted.

## Installation
1. BACK UP `C:\xampp\htdocs\photographsite` and export `lenscraft_db` from phpMyAdmin first.
2. Extract the **contents** of `LensCraft_Advanced_XAMPP` into `C:\xampp\htdocs\photographsite` (not into an extra nested folder).
3. Keep your own existing `config.php` with your correct DB connection and PayHere credentials; do not overwrite it if you have already configured it. Do not share secrets.
4. If this is a fresh install, import `schema.sql`; if upgrading, **do not reimport schema.sql**. If `portfolio_images` is missing, import `portfolio_images_migration.sql` only.
5. Visit `http://localhost/photographsite/dashboard.php` after logging in.
6. Photographer: add photos, packages, dates, and cover/avatar via the dashboard / My profile. Customer: browse, cart, booking list, and payment status.

## Design vs screenshot
This is an implemented responsive interpretation of the supplied reference. The screenshot's sample photographs, ratings, reviews, and example booking counts are NOT hardcoded; real user-uploaded photos and database records display instead. Calendar month navigation works locally; booking status is based on real records.

## Payments
The package contains the existing PayHere integration. It is NOT live-payment-ready until valid credentials and a public HTTPS notification URL are configured and payment callbacks tested. Never mark a payment paid from return_url alone. Do not expose merchant secrets.

## Test status
PHP syntax checked using php -l. Browser, MySQL, and real PayHere end-to-end flows have NOT been tested in your Windows XAMPP environment.
