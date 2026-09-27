<?php

declare(strict_types=1);

// Run only on the issuer's machine. Never deploy a signing secret with the bundle.
if (PHP_SAPI !== 'cli' || count($argv) !== 3) {
    fwrite(STDERR, "Usage: php tools/sign-license.php claims.json private-key.base64\n");
    exit(1);
}
try {
    $claimsFile = file_get_contents($argv[1]);
    $keyFile = file_get_contents($argv[2]);
    if ($claimsFile === false || $keyFile === false) throw new RuntimeException('Input file unreadable.');
    $claims = json_decode($claimsFile, true, 32, JSON_THROW_ON_ERROR);
    $secret = base64_decode(trim($keyFile), true);
    if (!is_array($claims) || $secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) throw new RuntimeException('Invalid claims or signing key.');
    foreach (['tenant','domain','mode','status'] as $field) if (!is_string($claims[$field] ?? null) || $claims[$field] === '') throw new RuntimeException('Missing license identity.');
    if (!is_int($claims['issued_at'] ?? null) || !is_int($claims['expires_at'] ?? null) || $claims['expires_at'] <= $claims['issued_at'] || !is_array($claims['features'] ?? null)) throw new RuntimeException('Invalid validity period or features.');
    if (!in_array($claims['mode'], ['online','offline'], true) || $claims['status'] !== 'valid') throw new RuntimeException('Invalid license mode or status.');
    if ($claims['mode'] === 'online' && (!is_int($claims['refresh_after'] ?? null) || $claims['refresh_after'] < $claims['issued_at'])) throw new RuntimeException('Invalid online refresh date.');
    $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $payload = $encode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    $signature = sodium_crypto_sign_detached($payload, $secret);
    sodium_memzero($secret);
    fwrite(STDOUT, $payload.'.'.$encode($signature)."\n");
} catch (Throwable) {
    fwrite(STDERR, "License generation failed. Check input files, claims and Ed25519 key format.\n");
    exit(1);
}
