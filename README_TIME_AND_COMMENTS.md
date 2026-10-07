LensCraft booking time + customer comments update

1. Back up your existing project and MySQL database.
2. Extract into C:\xampp\htdocs\photographsite and keep your original config.php and uploaded photos.
3. Import feedback_migration.sql into lenscraft_db only if you have not imported it before. Do not drop existing tables.
4. Customer: Cart > Booking > click a green date. Start Time and End Time fields now appear automatically, and the chosen time is displayed in Booking Summary. Change either time in 30-minute steps. Red dates remain blocked.
5. Photographer public profile: Customer comments and ratings are displayed to all visitors. Sign in and complete a real booking to submit a verified review; one review per completed booking. If not eligible, the profile explains why the comment form is not shown.
6. Date availability and reservation checks are enforced on the server before checkout. PayHere is not tested by this update.
7. Refresh browser with Ctrl+Shift+R after replacing files.
