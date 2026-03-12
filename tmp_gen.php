<?php
require 'vendor/autoload.php';

use phpseclib3\Crypt\RSA;

// Generate 4096-bit RSA key
$private = RSA::createKey(4096);
$public = $private->getPublicKey();

// Save Private Key
if (!file_put_contents(__DIR__ . '/config/jwt/private.pem', $private->toString('PKCS1'))) {
    die("Failed to write private.pem");
}

// Save Public Key
if (!file_put_contents(__DIR__ . '/config/jwt/public.pem', $public->toString('PKCS8'))) {
    die("Failed to write public.pem");
}

echo "Keys generated successfully via phpseclib3.\n";
