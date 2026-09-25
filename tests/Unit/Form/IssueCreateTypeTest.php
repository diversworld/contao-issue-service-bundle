<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Form;

use Diversworld\ContaoIssueServiceBundle\Form\IssueCreateType;
use Diversworld\ContaoIssueServiceBundle\Repository\ServiceRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class IssueCreateTypeTest extends TestCase
{
    public function testCategoryAndPriorityChoicesAreValidated(): void
    {
        $db = $this->createStub(Connection::class);
        $db->method('fetchOne')->willReturnCallback(static fn (string $sql, array $params): int|false => isset($params['c']) && $params['c'] === 3 && $params['s'] === 1 ? 1 : false);
        $db->method('fetchAllKeyValue')->willReturn([1 => 'Service A', 2 => 'Service B']);
        $db->method('fetchAllAssociative')->willReturn([
            ['id' => 3, 'title' => 'Kategorie A', 'service_id' => 1, 'service_title' => 'Service A'],
        ]);
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new IssueCreateType(new ServiceRepository($db), new \Diversworld\ContaoIssueServiceBundle\Application\AttachmentConstraints(new \Diversworld\ContaoIssueServiceBundle\Application\SettingsService(new \Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository($db)))))
            ->getFormFactory();

        foreach ([['1', '3', 'high', true], ['2', '3', 'normal', false], ['1', '999', 'normal', false], ['1', '', 'critical', true], ['1', '', 'invalid', false]] as [$service, $category, $priority, $valid]) {
            $form = $factory->create(IssueCreateType::class);
            self::assertSame('normal', $form->get('priority')->getData());
            $form->submit(['serviceId' => $service, 'categoryId' => $category, 'priority' => $priority, 'type' => 'bug', 'title' => 'Test issue', 'description' => 'Detailed description']);
            self::assertSame($valid, $form->isValid(), (string) $form->getErrors(true));
            if ($valid) {
                self::assertSame($priority, $form->getData()['priority']);
            }
        }
    }
}
