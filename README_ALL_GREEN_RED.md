LensCraft full-day calendar policy (September 2026)

- Future dates are green by default, without publishing each day. Default bookable time: 09:00–18:00.
- Photographer may publish alternative hours in Manage availability.
- A pending or confirmed customer booking makes the WHOLE day red and prevents any other booking for that photographer on that date.
- Photographer can block a whole date (red) and unblock it (green) from dashboard > Mark a whole day red / green. Cannot block a date with an active booking.
- Past dates are gray. Purple border means selected, not a booking status.
- Public profile calendar, photographer dashboard, and customer booking calendar use the same day-level red/green rule.
- Server checks blocked days and bookings under a photographer row lock, avoiding double bookings from simultaneous requests.
- IMPORTANT: pending bookings remain red until cancelled or their status is changed. Payment status is tracked separately.
- Existing partially booked dates are treated as FULLY booked under this policy.
- Preserve config.php, uploaded photos and your database. No schema migration required.
- Testing: test future green date; block/unblock; book one date; check all calendars red; verify second booking same day is rejected; verify payment status separately.
- PHP syntax lint does not prove database or PayHere integration works; test on your XAMPP instance.
