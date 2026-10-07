# ICT2142 E-Business Systems — Week 07 (PayHere Sandbox)

## Implemented in this project
- `checkout.php`: editable customer billing details (first/last name, email, phone, address, city), booking/package summary and total LKR amount.
- `bootstrap.php`: server-side PayHere request MD5 hash, using 2-decimal amount, uppercase LKR and merchant secret kept in PHP config.
- `checkout.php`: Pay Now form POST to PayHere sandbox with merchant ID, order ID, item name, billing details, hash, return/cancel/notify URLs.
- `payhere_notify.php`: verifies PayHere callback MD5 signature, merchant ID, amount, currency and order ID before marking payment paid and booking confirmed.
- `payment_return.php` / `payment_cancel.php`: browser return pages. A browser redirect alone **never** confirms a payment.
- Existing profile, cart, calendar, photographer dashboard and feedback remain included.

## You must configure yourself
1. Back up your XAMPP project and database. Extract these files to `C:\xampp\htdocs\photographsite` and keep your own database credentials.
2. Register at https://sandbox.payhere.lk, open **Integrations**, register `localhost` as a **Domain** (not an App), then copy your real sandbox Merchant ID and Merchant Secret into `config.php`. Never use the example merchant ID or publish the merchant secret.
3. `PAYHERE_MODE` must remain `sandbox` for practical testing; use uppercase `LKR` and a numeric amount with exactly two decimal places.
4. Visit `http://localhost/photographsite` using XAMPP Apache and MySQL. A `file:///` URL is not supported.
5. **Important localhost limitation:** PayHere cannot call `http://localhost/.../payhere_notify.php` from the public internet. To verify end-to-end payment, deploy the project to a publicly reachable HTTPS test domain (with a test database), set `APP_URL` to that domain, register it with PayHere sandbox, and use its merchant secret. Alternatively use a securely configured HTTPS tunnel if permitted by your lecturer and PayHere; confirm the registered domain matches. Without a working callback, a sandbox payment can return to a pending page and the booking should remain unconfirmed.
6. Test only with sandbox test cards. Never enter a real card into a test project.
7. Keep `config.php` secrets private. Before any real payment go-live, perform security, payment retry, concurrency, and gateway callback tests. No live-payment guarantee is made here.

## Test evidence checklist (fill with your own results)
| Test | Expected | Actual / screenshot |
|---|---|---|
| Checkout billing details | Editable fields save to session | TODO |
| Order total | Correct package price, LKR and two decimals in payment POST | TODO |
| Pay Now | PayHere Sandbox opens with correct merchant | TODO |
| Approved sandbox card | PayHere reports approval | TODO |
| Verified notify callback | `payments.status=paid`; `bookings.status=confirmed` | TODO |
| Cancel / declined card | Booking not marked paid | TODO |
| Incorrect callback signature | Rejected with HTTP 400 | TODO |
| Refresh return page without callback | Payment still pending | TODO |

## Project Diary — Week 07 (template, edit after real testing)
**Date:** __________
**Practical:** Payment Gateway Integration
**Objective:** Integrate PayHere Sandbox with LensCraft photography booking checkout.
**Work performed:** Added an editable billing form; included booking and LKR total; configured a PayHere sandbox POST request and server-side MD5 request hash; verified the server notification signature before confirming payments.
**Problems encountered:** __________________________________________
**How resolved:** _________________________________________________
**Actual sandbox test result:** _____________________________________
**Screenshots attached:** ___________________________________________
**Next steps:** Complete sandbox test on a publicly reachable HTTPS endpoint and record observed outcomes.

The PDF's test card examples (sandbox only): Visa 4916 2175 0161 1292; Mastercard 5307 7321 2553 1191; any future expiry and three-digit CVV as directed in the practical. Confirm accepted test cards with the sandbox portal.
