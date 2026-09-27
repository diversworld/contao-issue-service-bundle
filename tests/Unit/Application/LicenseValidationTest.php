<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;
use Diversworld\ContaoIssueServiceBundle\Application\License\LicenseValidationService;
use PHPUnit\Framework\TestCase;

final class LicenseValidationTest extends TestCase
{
    /** @param array<string, mixed> $claims */
    public static function token(array $claims, string $secret): string
    {
        if ($secret === '') throw new \InvalidArgumentException('Missing signing key.');
        $encode = static fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $payload = $encode(json_encode($claims, JSON_THROW_ON_ERROR));
        return $payload.'.'.$encode(sodium_crypto_sign_detached($payload, $secret));
    }
    public function testBindingSignatureExpiryAndGraceBoundaries(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $validator = new LicenseValidationService(base64_encode(sodium_crypto_sign_publickey($pair)), 'tenant-a', 'example.org');
        $claims = ['tenant' => 'tenant-a', 'domain' => 'example.org', 'issued_at' => 1000, 'expires_at' => 10000000,
            'refresh_after' => 2000, 'mode' => 'online', 'status' => 'valid', 'features' => ['sla']];
        $token = self::token($claims, $secret);
        self::assertSame('valid', $validator->validate($token, 1999)['state']);
        self::assertSame('grace', $validator->validate($token, 2000)['state']);
        self::assertTrue($validator->validate($token, 2000 + 30 * 86400 - 1)['enabled']);
        self::assertFalse($validator->validate($token, 2000 + 30 * 86400)['enabled']);
        foreach ([['tenant' => 'tenant-b'], ['domain' => 'other.org'], ['features' => []], ['status' => 'revoked'], ['issued_at' => 3000], ['expires_at' => 1500], ['mode' => 'unknown']] as $patch) {
            self::assertFalse($validator->validate(self::token(array_replace($claims, $patch), $secret), 1999)['enabled']);
        }
        self::assertFalse($validator->validate('x'.substr($token,1), 1999)['enabled']);
        self::assertFalse($validator->validate(self::token($claims, sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())), 1999)['enabled']);
        $claims['mode'] = 'offline';
        self::assertTrue($validator->validate(self::token($claims, $secret), 9000000)['enabled']);
        self::assertFalse($validator->validate(self::token($claims, $secret), 10000000)['enabled']);
    }
}
