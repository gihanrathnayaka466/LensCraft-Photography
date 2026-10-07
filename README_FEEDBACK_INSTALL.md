LensCraft customer feedback update
1. Back up existing XAMPP project and MySQL database.
2. Import feedback_migration.sql into your existing lenscraft_db using phpMyAdmin > Import. DO NOT drop your existing tables.
3. Copy updated project files to C:\xampp\htdocs\photographsite. Preserve your own config.php.
4. On a public photographer profile, completed-booking customers can select their booking, give a 1–5 rating and post 10–1500 characters. One review per booking.
5. All visitors can read published reviews. Reviewer's name is displayed. No private booking details are shown.
6. If there are no completed bookings, review form is intentionally hidden. To test, mark a genuine test booking as completed through your existing booking workflow.
7. Rating displayed on the public profile is computed from actual submitted reviews, not the seed/demo rating. No reviews = 'No reviews yet'.
8. Feedback submissions use login, CSRF, booking ownership checks, prepared statements and HTML escaping.
Note: This feature does not include moderation, edit/delete, or email notifications. The booking system and payment integration are unchanged.
