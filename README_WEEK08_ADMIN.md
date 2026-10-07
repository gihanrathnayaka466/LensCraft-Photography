# ICT2142 Week 08 - Database Integration & Admin Dashboard

## Install / upgrade existing LensCraft database
1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin -> lenscraft_db -> SQL.
3. Run `week08_migration.sql`.
4. Promote one existing account to admin by running:
   `UPDATE users SET role='admin' WHERE email='YOUR_EMAIL';`
5. Sign out and sign in again.
6. Open `http://localhost/photographsite/admin.php`.

## Week 08 features
- Persistent MySQL data remains the source of truth.
- Admin-only server-side route protection (`require_admin()`).
- Dashboard summary counts.
- Package/product Create, Read, Update and safe deactivate.
- Inventory/stock quantity management.
- Customer booking/order list and status updates.
- Customer-facing package queries hide inactive/out-of-stock packages.
- Successful verified PayHere callback decrements stock once.

## Evidence screenshots for the report
Capture: phpMyAdmin schema/packages/bookings, admin dashboard summary, package CRUD/stock update, booking status update, and a customer page showing the changed data.
