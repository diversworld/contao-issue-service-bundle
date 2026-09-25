<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

final class IssueCategoryOptions
{
    public function __construct(private readonly Connection $db, private readonly RequestStack $requests) {}

    private function serviceId(?DataContainer $dc): int
    {
        $request = $this->requests->getCurrentRequest();
        if ($request?->request->get('FORM_SUBMIT') === 'tl_issue' && $request->request->has('service_id')) {
            return (int) $request->request->get('service_id', 0);
        }
        return $dc?->id ? (int) $this->db->fetchOne('SELECT service_id FROM tl_issue WHERE id=:id', ['id' => $dc->id]) : 0;
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.category_id.options')]
    public function options(?DataContainer $dc = null): array
    {
        $service = $this->serviceId($dc);
        if (!$service) {
            return [];
        }
        return $this->db->fetchAllKeyValue('SELECT id, title FROM tl_issue_category WHERE service_id=:service ORDER BY title, id', ['service' => $service]);
    }

    #[AsCallback(table: 'tl_issue', target: 'config.onload')]
    public function resetAfterServiceChange(DataContainer $dc): void
    {
        $request = $this->requests->getCurrentRequest();
        if (!$dc->id || $request?->request->get('FORM_SUBMIT') !== 'tl_issue' || !$request->request->has('service_id')) {
            return;
        }
        $old = $this->db->fetchAssociative('SELECT service_id, category_id FROM tl_issue WHERE id=:id', ['id' => $dc->id]);
        $category = (int) $request->request->get('category_id', 0);
        if ($old && (int) $old['service_id'] !== $this->serviceId($dc)
            && ($category === 0 || !$this->db->fetchOne('SELECT id FROM tl_issue_category WHERE id=:id AND service_id=:service', ['id' => $category, 'service' => $this->serviceId($dc)]))) {
            $request->request->set('category_id', '');
            Input::setPost('category_id', '');
        }
    }

    #[AsCallback(table: 'tl_issue', target: 'fields.category_id.save')]
    public function validate(mixed $value, DataContainer $dc): ?int
    {
        if (!$value) {
            return null;
        }
        if (!$this->db->fetchOne('SELECT id FROM tl_issue_category WHERE id=:id AND service_id=:service', ['id' => (int) $value, 'service' => $this->serviceId($dc)])) {
            throw new \InvalidArgumentException('Bitte eine Kategorie des ausgewählten Services wählen.');
        }
        return (int) $value;
    }
}
