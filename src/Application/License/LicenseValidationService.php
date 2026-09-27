<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\License;

/** Ed25519 envelope: base64url(JSON claims).base64url(detached signature). */
final class LicenseValidationService
{
    public function __construct(private readonly string $publicKey, private readonly string $tenant, private readonly string $domain) {}

    /** @return array{enabled: bool, state: string, claims: array<string, mixed>} */
    public function validate(string $token, ?int $now = null): array
    {
        $now ??= time();
        try {
            $parts = explode('.', trim($token));
            if (count($parts) !== 2 || $this->tenant === '' || $this->domain === '') throw new \UnexpectedValueException();
            $key = base64_decode($this->publicKey, true);
            $signature = $this->decode($parts[1]);
            if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
                || !sodium_crypto_sign_verify_detached($signature, $parts[0], $key)) throw new \UnexpectedValueException();
            $claims = json_decode($this->decode($parts[0]), true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($claims) || ($claims['tenant'] ?? null) !== $this->tenant || ($claims['domain'] ?? null) !== strtolower($this->domain)
                || !is_int($claims['issued_at'] ?? null) || !is_int($claims['expires_at'] ?? null)
                || $claims['issued_at'] > $now || $claims['expires_at'] <= $now || $claims['expires_at'] <= $claims['issued_at']
                || !is_array($claims['features'] ?? null) || !in_array('sla', $claims['features'], true)
                || !in_array($claims['mode'] ?? null, ['online', 'offline'], true)
                || ($claims['status'] ?? null) !== 'valid') throw new \UnexpectedValueException();
            $state = 'valid';
            if ($claims['mode'] === 'online') {
                $refresh = $claims['refresh_after'] ?? null;
                if (!is_int($refresh) || $refresh < $claims['issued_at'] || $now >= $refresh + 30 * 86400) throw new \UnexpectedValueException();
                if ($now >= $refresh) $state = 'grace';
            }
            return ['enabled' => true, 'state' => $state, 'claims' => $claims];
        } catch (\Throwable) {
            return ['enabled' => false, 'state' => 'invalid', 'claims' => []];
        }
    }

    private function decode(string $value): string
    {
        if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/D', $value)) throw new \UnexpectedValueException();
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) throw new \UnexpectedValueException();
        return $decoded;
    }
}
