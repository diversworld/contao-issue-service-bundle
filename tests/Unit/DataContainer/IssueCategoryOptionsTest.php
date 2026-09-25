<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\DataContainer;

use Contao\DataContainer;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueCategoryOptions;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};

final class IssueCategoryOptionsTest extends TestCase
{
    public function testRepeatedServiceChangesWithEmptyAndStaleCategories(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE tl_issue (id INTEGER, service_id INTEGER, category_id INTEGER)');
        $db->executeStatement('CREATE TABLE tl_issue_category (id INTEGER, service_id INTEGER, title TEXT)');
        $db->insert('tl_issue', ['id' => 1, 'service_id' => 10, 'category_id' => null]);
        $db->insert('tl_issue_category', ['id' => 1, 'service_id' => 10, 'title' => 'A']);
        $db->insert('tl_issue_category', ['id' => 2, 'service_id' => 20, 'title' => 'B']);
        $requests = new RequestStack();
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->set('request_stack', $requests);
        $previous = \Contao\System::getContainer();
        $post = $_POST;
        \Contao\System::setContainer($container);
        $callback = new IssueCategoryOptions($db, $requests);
        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['id', 1]]);
        try {
            foreach ([[20, '', ''], [10, '', ''], [20, '1', ''], [10, '2', ''], [20, '2', '2']] as [$service, $category, $expected]) {
                $request = Request::create('/', 'POST', ['FORM_SUBMIT' => 'tl_issue', 'service_id' => (string) $service, 'category_id' => $category]);
                $requests->push($request);
                $callback->resetAfterServiceChange($dc);
                self::assertSame($expected, $request->request->get('category_id'));
                $value = $callback->validate($request->request->get('category_id'), $dc);
                $db->update('tl_issue', ['service_id' => $service, 'category_id' => $value], ['id' => 1]);
                $requests->pop();
            }
        } finally {
            (new \ReflectionProperty(\Contao\System::class, 'objContainer'))->setValue(null, $previous);
            $_POST = $post;
        }
    }

    public function testOptionsAndValidationUseSelectedService(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE tl_issue_category (id INTEGER, service_id INTEGER, title TEXT)');
        $db->insert('tl_issue_category', ['id' => 1, 'service_id' => 10, 'title' => 'A']);
        $db->insert('tl_issue_category', ['id' => 2, 'service_id' => 20, 'title' => 'B']);
        $requests = new RequestStack();
        $callback = new IssueCategoryOptions($db, $requests);
        self::assertSame([], $callback->options());
        $request = Request::create('/', 'POST', ['FORM_SUBMIT' => 'tl_issue', 'service_id' => '10']);
        $requests->push($request);
        self::assertSame([1 => 'A'], $callback->options());
        $dc = $this->createStub(DataContainer::class);
        self::assertSame(1, $callback->validate('1', $dc));
        self::assertNull($callback->validate('', $dc));
        $request->request->set('service_id', '20');
        self::assertSame([2 => 'B'], $callback->options());
        $this->expectException(\InvalidArgumentException::class);
        $callback->validate('1', $dc);
    }
}
