<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\License;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LicenseService
{
    public function __construct(private readonly Connection $db, private readonly LicenseValidationService $validator,
        private readonly HttpClientInterface $http, private readonly string $endpoint, private readonly string $tenant, private readonly string $domain) {}

    /** @return array{enabled: bool, state: string, claims: array<string, mixed>} */
    public function status(): array
    {
        // Never trust editable status/expiry columns or a cached boolean: verify the signed claims each time.
        try {
            $token = $this->db->fetchOne('SELECT token FROM tl_issue_license WHERE id=1');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException) {
            return ['enabled' => false, 'state' => 'not_installed', 'claims' => []];
        }
        return $this->validator->validate(is_string($token) ? $token : '');
    }

    public function import(string $token): void
    {
        if (!$this->validator->validate($token)['enabled']) throw new \DomainException('Ungültige Lizenz, Signatur oder Mandanten-/Domainbindung.');
        $this->store(trim($token), 'imported');
    }

    /** @return array{enabled: bool, state: string, claims: array<string, mixed>} */
    public function refresh(): array
    {
        if (!str_starts_with($this->endpoint, 'https://') || parse_url($this->endpoint, PHP_URL_USER) !== null) {
            throw new \DomainException('Eine HTTPS-Lizenzserver-Adresse muss konfiguriert sein.');
        }
        $token = (string) $this->db->fetchOne('SELECT token FROM tl_issue_license WHERE id=1');
        try {
            $response = $this->http->request('POST', $this->endpoint, ['json' => ['token' => $token, 'tenant' => $this->tenant, 'domain' => $this->domain],
                'timeout' => 10, 'max_duration' => 15, 'max_redirects' => 0]);
            $code = $response->getStatusCode();
            if (in_array($code, [401, 403, 410], true)) {
                $this->store('', 'rejected');
                return $this->status();
            }
            if ($code !== 200) throw new \RuntimeException('License server unavailable.');
            $body = $response->toArray(false);
            $renewed = $body['token'] ?? null;
            if (!is_string($renewed) || !$this->validator->validate($renewed)['enabled']) {
                // An invalid response cannot extend the signed grace window.
                throw new \RuntimeException('Invalid signed server response.');
            }
            $this->store($renewed, 'validated');
        } catch (\Throwable) {
            $this->audit('unavailable');
        }
        return $this->status();
    }

    private function store(string $token, string $result): void
    {
        $this->db->transactional(function () use ($token, $result): void {
            if ($this->db->fetchOne('SELECT id FROM tl_issue_license WHERE id=1')) {
                $this->db->update('tl_issue_license', ['token' => $token, 'tstamp' => time()], ['id' => 1]);
            } else {
                $this->db->insert('tl_issue_license', ['id' => 1, 'token' => $token, 'tstamp' => time()]);
            }
            $this->audit($result);
        });
    }

    private function audit(string $result): void
    {
        $this->db->insert('tl_issue_license_validation', ['license_id' => 1, 'result' => $result, 'created_at' => time()]);
    }
}
