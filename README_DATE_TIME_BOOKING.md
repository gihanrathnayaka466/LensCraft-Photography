LensCraft Date + Start Time + End Time booking upgrade

- Photographer publishes an availability date and a start/end interval in dashboard.
- Customer selects a green date, selects a published interval, then selects start and end times within that interval in 30-minute increments.
- Customer sees the chosen date and exact times in the summary; payment button remains disabled until selection is valid.
- Server validates submitted date/time inside published availability and checks for overlapping pending/confirmed bookings under a photographer row lock.
- A booking creates a pending booking and pending payment. The public calendar shows the reservation; payment confirmation is still separate.
- Existing cart, reviews, photographer dashboard, checkout and PayHere code remain in the project.

Install: Backup current project and database. Extract files into C:\xampp\htdocs\photographsite, keeping your local config.php and uploads. No SQL migration for this update. Test using photographer-published dates, a customer account, and overlapping reservations.

Note: This project is not live-tested with your XAMPP database or PayHere credentials. A reserved partial interval may cause the full published interval to disappear from the available-date listing until the photographer publishes a nonoverlapping interval; overlapping reservations are rejected server-side. A full availability interval-splitting scheduler would require additional work.
