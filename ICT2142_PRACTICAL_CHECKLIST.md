# ICT2142 E-Business Systems — Practical 01–05 implementation notes

## Practical 01: Project initiation
Business: LensCraft, a marketplace for photography session bookings. Target users: customers and photographers. Core functions: search/filter packages, cart, account creation/login/logout, session booking and PayHere checkout. Stack: PHP, MySQL, HTML, CSS, JavaScript, XAMPP. `schema.sql` contains the database definition. This is a booking service, not a stock-based retail store.

## Practical 02: Architecture and wireframes
Pages: index.php (homepage), browse.php (listing), profile.php (photographer detail), cart.php (cart), booking.php (session choice), checkout.php (payment), account.php (profile). Data: users, photographers, packages, availability, bookings, payments. Create and attach actual wireframe drawings and an ERD to your submission; these are not included here.

## Practical 03: Navigation / responsive UI
Shared navbar/footer: partials_header.php and partials_footer.php; styling: style.css. Browse includes category/location/date/budget filters. Check mobile layout in your own browser. Git repository and commits must be created by you; this ZIP is not a Git repository.

## Practical 04 (file named Practical 04, slide says Week 05): Cart
- cart.php implements add, remove, clear and subtotal from server-side PHP session state. PHP session persists on refresh while the session cookie is valid; it is **not** browser localStorage.
- The handout requires +/- quantity controls and price * quantity. These are **not implemented** here because one photography package corresponds to one dated booking, and the existing booking/payment tables process one session per booking. Displaying qty > 1 without separately reserving dates and collecting correct payment would be misleading. Discuss this service-specific adaptation with your lecturer. For literal compliance, redesign booking to support multiple independently scheduled session lines before adding quantity.
- Add/remove use CSRF tokens and database-backed package pricing.

## Practical 05 (file named Practical 05, slide says Week 06): Authentication
- register.php creates hashed-password accounts and photographer profiles; login.php verifies hash and rotates session ID; logout.php ends session.
- account.php and account_save.php support profile editing, studio images and **new password change** with old-password verification. Registration now checks password complexity.
- `require_login()` protects profile, booking and checkout routes; HTTP-only SameSite session cookie used.
- Never store plaintext passwords or commit production credentials. Check error messages and role permissions in your own test environment.

## Payment and availability (outside 01–05 scope)
PayHere integration requires a real sandbox merchant ID/secret, correct callback signature validation and a publicly reachable HTTPS notify URL. `localhost` is not publicly reachable by PayHere. Do not claim payment success until server-side notification is verified. Concurrent booking prevention must be tested with simultaneous requests; visual calendar changes alone do not prove race-free booking.

## Testing diary (fill with actual evidence, not invented results)
| Test | Expected | Actual result / screenshot |
|---|---|---|
| Register customer | Account created; password stored as hash | TODO |
| Register photographer | Studio profile created | TODO |
| Login wrong password | Generic error, no session | TODO |
| Login right password | Dashboard and updated navbar | TODO |
| Logout | Session invalidated | TODO |
| Change password | Old password fails, new works | TODO |
| Add/remove cart item | Count and subtotal update | TODO |
| Refresh cart | Session cart persists | TODO |
| Photographer publishes slot | Slot appears in booking UI | TODO |
| Book same slot twice | Second request blocked | TODO |
| PayHere sandbox payment | Verified callback updates status | TODO |

## Submission items still to prepare
Actual wireframe screenshots, ER diagram, GitHub commit history, dated diary/debugging notes, mobile screenshots, test outcomes, architecture flow chart, report introduction/conclusion. Do not mark untested flows as passed.
