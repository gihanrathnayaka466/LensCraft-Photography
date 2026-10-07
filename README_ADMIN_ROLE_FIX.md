# LensCraft Admin Role Fix

This update keeps marketplace responsibilities separate:

- Admin: monitor users, photographers, all bookings and payment records; update booking status.
- Photographer: manage own profile, portfolio, packages/prices and availability from `dashboard.php`.
- Customer: browse photographers, choose packages, book, pay and review.

The previous Admin Package CRUD / Inventory section was removed because package ownership belongs to each photographer.
