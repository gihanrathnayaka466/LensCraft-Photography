LensCraft Booking Colors + Time Fix

Backup your project and database first. Copy files to C:\xampp\htdocs\photographsite and keep your local config.php and uploaded photos. No SQL migration required.

Customer: green = at least one free interval, red = booked or photographer-blocked and no free interval, gray = not published. Purple = selected. Click green date, choose available interval, then adjust start/end inside that free interval. Reserved intervals appear as disabled red time buttons. Remaining time is shown when an earlier booking only occupies part of a published interval.

Photographer: Dashboard > Manage availability. Publish start/end; each slot now has Mark blocked / Mark available control. You cannot toggle or delete a slot containing an active booking. Booked reservations are still displayed on the dashboard calendar and booking table.

Security: create_booking.php retains its server-side published-time, active-reservation and transaction-lock checks. PayHere credentials/notify endpoint still require separate configuration.

IMPORTANT: customer booking status pending is treated as reserved, including before payment. Cancelled bookings are released. Testing with a real XAMPP MySQL database is still needed.
