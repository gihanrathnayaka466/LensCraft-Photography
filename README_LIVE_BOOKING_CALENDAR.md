# Automatic photographer booking calendar

## Installation
Back up your project and database. Copy the complete project folder contents to C:\xampp\htdocs\photographsite\, preserving your own config.php credentials. No database migration is needed: this uses the existing bookings and availability tables.

## How it works
Customer selects a package and published time slot, submits Review & Pay, and create_booking.php writes a pending booking to MySQL before redirecting to checkout. The photographer dashboard calendar highlights dates with pending or confirmed bookings in red; available dates without active bookings are green. Click a date to view customer, package, time, booking code and pending/confirmed status. The dashboard refreshes its calendar every 20 seconds and when the tab becomes visible. A pending booking is a reservation, NOT a verified payment.

Bookings are read only for the signed-in photographer. Cancelled and completed bookings do not appear as active red reservations. Payment confirmation is handled separately by the existing verified gateway notification flow.

## Test
1. Sign in as photographer, publish an availability date and time.
2. In a different browser session sign in as a customer, add the photographer package to cart, choose the available time and click Review & Pay.
3. Return to the photographer dashboard. The day turns red within 20 seconds. Click it to view details and pending status.
4. Test overlapping time requests: the second should be rejected.
5. Payment must still be verified via PayHere; pending does not mean paid.

Note: The booking flow requires an existing published availability slot. Existing PayHere sandbox credentials in config.php are placeholders unless you replace them with real credentials.
