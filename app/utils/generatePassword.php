<?php
function generateSecurePassword(int $length = 12): string
{
    if ($length < 8) {
        throw new Exception("Password length must be at least 8");
    }

    $lower   = "abcdefghjkmnpqrstuvwxyz";
    $upper   = "ABCDEFGHJKMNPQRSTUVWXYZ";
    $digits  = "23456789";
    $symbols = "!@#$%^&*()-_=+";
    $all     = $lower . $upper . $digits . $symbols;

    $password = [];
    $password[] = $lower[random_int(0, strlen($lower) - 1)];
    $password[] = $upper[random_int(0, strlen($upper) - 1)];
    $password[] = $digits[random_int(0, strlen($digits) - 1)];
    $password[] = $symbols[random_int(0, strlen($symbols) - 1)];

    while (count($password) < $length) {
        $password[] = $all[random_int(0, strlen($all) - 1)];
    }

    for ($i = count($password) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
    }

    return implode('', $password);
}
