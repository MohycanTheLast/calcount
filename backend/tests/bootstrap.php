<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Dočasný pár JWT klíčů jen pro tento běh testů – v paměti, nic se nezapisuje do config/jwt/.
// Skutečné proměnné prostředí mají přednost před .env soubory, proto se nastavují před bootEnv().
$jwtPassphrase = bin2hex(random_bytes(16));
$jwtKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($jwtKey, $jwtPrivateKey, $jwtPassphrase);
$jwtEnv = [
    'JWT_SECRET_KEY' => $jwtPrivateKey,
    'JWT_PUBLIC_KEY' => openssl_pkey_get_details($jwtKey)['key'],
    'JWT_PASSPHRASE' => $jwtPassphrase,
];
foreach ($jwtEnv as $name => $value) {
    $_SERVER[$name] = $_ENV[$name] = $value;
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
