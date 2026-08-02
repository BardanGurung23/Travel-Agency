<?php

function payment_csrf_token(): string
{
    if (empty($_SESSION['payment_csrf'])) {
        $_SESSION['payment_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['payment_csrf'];
}

function verify_payment_csrf(string $token): bool
{
    return isset($_SESSION['payment_csrf']) && hash_equals($_SESSION['payment_csrf'], $token);
}
