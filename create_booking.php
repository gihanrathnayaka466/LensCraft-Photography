
<?php
require_once __DIR__ . '/bootstrap.php';

require_login();



// Allow POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

// CSRF protection
verify_csrf();

// Get form data
$package = (int)($_POST['package_id'] ?? 0);

$date = trim((string)(
    $_POST['booking_date'] ?? ''
));

$start = trim((string)(
    $_POST['start_time'] ?? ''
));

$end = trim((string)(
    $_POST['end_time'] ?? ''
));

$notes = trim((string)(
    $_POST['notes'] ?? ''
));

// ---------------------------------
// 1. Validate booking date and time
// ---------------------------------

$d = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $date
);

$validStart = preg_match(
    '/^([01]\d|2[0-3]):[0-5]\d$/',
    $start
);

$validEnd = preg_match(
    '/^([01]\d|2[0-3]):[0-5]\d$/',
    $end
);

if (
    !$d ||
    $d->format('Y-m-d') !== $date ||
    $date < date('Y-m-d') ||
    !$validStart ||
    !$validEnd ||
    $start >= $end ||
    strlen($notes) > 2000
) {
    flash(
        'error',
        'Please select a valid booking date and time.'
    );

    redirect('booking.php');
}

// ---------------------------------
// 2. Check package in cart
// ---------------------------------

$inCart = false;

foreach (cart() as $item) {

    if (
        (int)($item['package_id'] ?? 0)
        === $package
    ) {
        $inCart = true;
        break;
    }
}

if (!$inCart) {

    flash(
        'error',
        'Please add the package to your cart first.'
    );

    redirect('cart.php');
}

// Database connection
$db = db();

$transactionStarted = false;

try {

    // ---------------------------------
    // 3. Start database transaction
    // ---------------------------------

    $db->begin_transaction();

    $transactionStarted = true;

    // ---------------------------------
    // 4. Get selected package
    // ---------------------------------

    $stmt = $db->prepare(
        "SELECT
            pa.*,
            p.studio_name
         FROM packages pa
         INNER JOIN photographers p
            ON p.id = pa.photographer_id
         WHERE pa.id = ?
           AND p.is_active = 1
           AND pa.is_active = 1
           AND pa.stock_qty > 0
         LIMIT 1"
    );

    $stmt->bind_param(
        'i',
        $package
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $pack = $result->fetch_assoc();

    $result->free();
    $stmt->close();

    if (!$pack) {
        throw new RuntimeException(
            'Selected package is unavailable.'
        );
    }

    $photographerId = (int)(
        $pack['photographer_id']
    );

    // ---------------------------------
    // 5. Lock photographer
    // Prevent simultaneous bookings
    // ---------------------------------

    $stmt = $db->prepare(
        "SELECT id
         FROM photographers
         WHERE id = ?
         FOR UPDATE"
    );

    $stmt->bind_param(
        'i',
        $photographerId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $photographer = $result->fetch_assoc();

    $result->free();
    $stmt->close();

    if (!$photographer) {
        throw new RuntimeException(
            'Photographer is unavailable.'
        );
    }

    // ---------------------------------
    // 6. Check photographer blocked date
    // RED = Blocked
    // ---------------------------------

    $stmt = $db->prepare(
        "SELECT id
         FROM availability
         WHERE photographer_id = ?
           AND available_date = ?
           AND is_booked = 1
         LIMIT 1"
    );

    $stmt->bind_param(
        'is',
        $photographerId,
        $date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $blocked = (bool)$result->fetch_assoc();

    $result->free();
    $stmt->close();

    if ($blocked) {
        throw new RuntimeException(
            'This date is blocked by the photographer.'
        );
    }

    // ---------------------------------
    // 7. Get photographer available time
    // ---------------------------------

    $stmt = $db->prepare(
        "SELECT
            start_time,
            end_time
         FROM availability
         WHERE photographer_id = ?
           AND available_date = ?
           AND is_booked = 0"
    );

    $stmt->bind_param(
        'is',
        $photographerId,
        $date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $availableTimes = $result->fetch_all(
        MYSQLI_ASSOC
    );

    $result->free();
    $stmt->close();

    // ---------------------------------
    // 8. Validate selected time
    // ---------------------------------

    $allowed = false;

    // Default available time
    if (empty($availableTimes)) {

        if (
            $start >= '09:00' &&
            $end <= '18:00'
        ) {
            $allowed = true;
        }

    } else {

        foreach ($availableTimes as $time) {

            $availableStart = substr(
                $time['start_time'],
                0,
                5
            );

            $availableEnd = substr(
                $time['end_time'],
                0,
                5
            );

            if (
                $start >= $availableStart &&
                $end <= $availableEnd
            ) {
                $allowed = true;
                break;
            }
        }
    }

    if (!$allowed) {

        throw new RuntimeException(
            'Please choose a time within the photographer available hours.'
        );
    }

    // ---------------------------------
    // 9. Check existing bookings
    // RED = Already booked
    // ---------------------------------

    $stmt = $db->prepare(
        "SELECT id
         FROM bookings
         WHERE photographer_id = ?
           AND booking_date = ?
           AND status IN (
               'pending',
               'confirmed'
           )
         LIMIT 1"
    );

    $stmt->bind_param(
        'is',
        $photographerId,
        $date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $alreadyBooked = (bool)(
        $result->fetch_assoc()
    );

    $result->free();
    $stmt->close();

    if ($alreadyBooked) {

        throw new RuntimeException(
            'This date is already booked. Please choose another date.'
        );
    }

    // ---------------------------------
    // 10. Generate booking code
    // ---------------------------------

    $bookingCode =
        'LC-' .
        date('Ymd') .
        '-' .
        strtoupper(
            bin2hex(random_bytes(5))
        );

    $userId = (int)user_id();

    $amount = (float)(
        $pack['price']
    );

    // ---------------------------------
    // 11. Save booking
    // ---------------------------------

    $stmt = $db->prepare(
        "INSERT INTO bookings
        (
            booking_code,
            user_id,
            photographer_id,
            package_id,
            booking_date,
            start_time,
            end_time,
            notes,
            amount,
            status
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            'pending'
        )"
    );

    $stmt->bind_param(
        'siiissssd',
        $bookingCode,
        $userId,
        $photographerId,
        $package,
        $date,
        $start,
        $end,
        $notes,
        $amount
    );

    $stmt->execute();

    $bookingId = (int)(
        $db->insert_id
    );

    $stmt->close();

    // ---------------------------------
    // 12. Generate payment order
    // ---------------------------------

    $orderId =
        'LCORD-' .
        $bookingId .
        '-' .
        bin2hex(random_bytes(5));

    // ---------------------------------
    // 13. Save payment
    // ---------------------------------

    $stmt = $db->prepare(
        "INSERT INTO payments
        (
            booking_id,
            order_id,
            amount,
            currency,
            status
        )
        VALUES
        (
            ?, ?, ?,
            'LKR',
            'pending'
        )"
    );

    $stmt->bind_param(
        'isd',
        $bookingId,
        $orderId,
        $amount
    );

    $stmt->execute();

    $stmt->close();

    // ---------------------------------
    // 14. Commit transaction
    // ---------------------------------

    $db->commit();

    $transactionStarted = false;

    // ---------------------------------
    // 15. Remove package from cart
    // ---------------------------------

    $_SESSION['cart'] = array_values(
        array_filter(
            cart(),
            static fn(array $entry): bool =>
                (int)(
                    $entry['package_id'] ?? 0
                ) !== $package
        )
    );

    // ---------------------------------
    // 16. Redirect to checkout
    // ---------------------------------

    redirect(
        'checkout.php?booking=' .
        $bookingId
    );

} catch (Throwable $e) {

    // Log original error
    error_log(
        'Booking Error: ' .
        $e->getMessage()
    );

    // Safe rollback
    if ($transactionStarted) {

        try {
            $db->rollback();

        } catch (Throwable $rollbackError) {

            error_log(
                'Rollback Error: ' .
                $rollbackError->getMessage()
            );
        }
    }

    // Show error
    flash(
        'error',
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Booking could not be saved. Please try again.'
    );

    redirect('booking.php');
}
