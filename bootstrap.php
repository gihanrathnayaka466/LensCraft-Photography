<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/* =========================================================
   SESSION SETTINGS
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_set_cookie_params([
        'httponly' => true,
        'secure' => (
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        ),
        'samesite' => 'Lax',
        'path' => '/photographsite'
    ]);

    session_start();
}


/* =========================================================
   CSRF TOKEN
   ========================================================= */

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


/* =========================================================
   HTML ESCAPE
   ========================================================= */

function e(?string $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   REDIRECT
   ========================================================= */

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}


/* =========================================================
   CSRF FIELD
   ========================================================= */

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' .
        e($_SESSION['csrf']) .
        '">';
}


/* =========================================================
   VERIFY CSRF
   ========================================================= */

function verify_csrf(): void
{
    $sessionToken = $_SESSION['csrf'] ?? '';
    $postedToken  = $_POST['csrf'] ?? '';

    if (
        $sessionToken === '' ||
        $postedToken === '' ||
        !hash_equals($sessionToken, $postedToken)
    ) {
        http_response_code(419);

        exit(
            'Invalid security token. Please go back and try again.'
        );
    }
}


/* =========================================================
   LOGIN CHECK
   ========================================================= */

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}


/* =========================================================
   CURRENT USER ID
   ========================================================= */

function user_id(): ?int
{
    return isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : null;
}


/* =========================================================
   REQUIRE LOGIN
   ========================================================= */

function require_login(): void
{
    if (!is_logged_in()) {

        $next = $_SERVER['REQUEST_URI'] ?? 'index.php';

        redirect(
            'login.php?next=' .
            urlencode($next)
        );
    }
}


/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

function db(): mysqli
{
    global $conn;

    return $conn;
}


/* =========================================================
   CURRENT USER
   FIXED VERSION
   ========================================================= */

function current_user(): ?array
{
    /*
     * Cache current user during this request.
     *
     * null  = user checked but not found
     * array = user found
     * false = not checked yet
     */

    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (!is_logged_in()) {
        return null;
    }


    $userId = (int)$_SESSION['user_id'];

    $stmt = db()->prepare(
        'SELECT
            id,
            fullname,
            email,
            phone,
            address,
            role
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare current user query: ' .
            db()->error
        );
    }


    $stmt->bind_param(
        'i',
        $userId
    );


    $stmt->execute();


    /*
     * IMPORTANT:
     * Fully consume/free result before another
     * MySQL command is executed.
     */

    $result = $stmt->get_result();

    if ($result) {

        $row = $result->fetch_assoc();

        $user = $row ?: null;

        // Free MySQL result
        $result->free();

    } else {

        $user = null;
    }


    // Close prepared statement
    $stmt->close();


    return $user;
}


/* =========================================================
   REQUIRE ADMIN
   ========================================================= */

function require_admin(): void
{
    require_login();

    $u = current_user();

    if (
        !$u ||
        ($u['role'] ?? '') !== 'admin'
    ) {

        http_response_code(403);

        exit(
            'Access denied. Administrator account required.'
        );
    }
}


/* =========================================================
   FLASH MESSAGE
   ========================================================= */

function flash(
    string $type,
    string $message
): void {

    $_SESSION['flash'][] = [
        $type,
        $message
    ];
}


/* =========================================================
   GET FLASH MESSAGES
   ========================================================= */

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];

    unset($_SESSION['flash']);

    return $messages;
}


/* =========================================================
   MONEY FORMAT
   ========================================================= */

function money(float $n): string
{
    return 'Rs. ' .
        number_format(
            $n,
            2
        );
}


/* =========================================================
   SHOPPING CART
   ========================================================= */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}


/* =========================================================
   CART ITEM COUNT
   ========================================================= */

function cart_count(): int
{
    return count(
        $_SESSION['cart'] ?? []
    );
}


/* =========================================================
   CART TOTAL
   ========================================================= */

function cart_total(): float
{
    $total = 0.0;

    foreach (
        $_SESSION['cart'] ?? []
        as $item
    ) {

        $total +=
            (float)($item['price'] ?? 0);
    }

    return $total;
}


/* =========================================================
   PAYHERE ACTION URL
   ========================================================= */

function payhere_action(): string
{
    return PAYHERE_MODE === 'live'
        ? 'https://www.payhere.lk/pay/checkout'
        : 'https://sandbox.payhere.lk/pay/checkout';
}


/* =========================================================
   PAYHERE HASH
   ========================================================= */

function payhere_hash(
    string $merchantId,
    string $orderId,
    float $amount,
    string $currency,
    string $secret
): string {

    $amountFormatted = number_format(
        $amount,
        2,
        '.',
        ''
    );


    $hashedSecret = strtoupper(
        md5($secret)
    );


    return strtoupper(
        md5(
            $merchantId .
            $orderId .
            $amountFormatted .
            $currency .
            $hashedSecret
        )
    );
}


/* =========================================================
   SPLIT CUSTOMER NAME
   ========================================================= */

function split_name(string $full): array
{
    $parts = preg_split(
        '/\s+/',
        trim($full),
        2
    );


    return [
        $parts[0] ?? 'Customer',
        $parts[1] ?? ''
    ];
}