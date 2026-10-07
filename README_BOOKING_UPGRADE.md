LensCraft Premium Booking Page Upgrade

Updated files: booking.php, create_booking.php, style.css.

What changed:
- Premium responsive booking layout with 3-step progress, calendar, time slot buttons, booking summary and event notes.
- Calendar shows only published, future, unreserved availability as green selectable dates.
- Changing packages updates available dates, photographer and price.
- Selected date/time is submitted to the existing secure booking processor.
- Server rechecks cart membership, photographer availability and overlapping reservations inside a transaction.
- Booking processor now rejects availability rows marked is_booked=1.
- Existing payment checkout, cart, customer reviews and photographer dashboards remain unchanged.

Installation: Back up your project and MySQL database. Extract ZIP into C:\xampp\htdocs\photographsite, preserving your existing config.php and your uploads. No database migration needed.

Test: Sign in as customer, add a photographer package to cart, open booking.php, pick green date and time, submit and verify it appears in photographer calendar. Verify same time cannot be double-booked.

Important: Checkout is not a completed payment; real PayHere verification requires valid credentials and public HTTPS callback URL.
