<?php 

declare(strict_types=1); 

namespace Diversworld\ContaoIssueServiceBundle\Tests\Static; 
use PHPUnit\Framework\TestCase; 

final class DcaShapeTest extends TestCase 
{ 
    public function testIssueDcaHasRequiredSections():void
    {
        $GLOBALS['TL_DCA']=[];
        require dirname(__DIR__,2).'/contao/dca/tl_issue.php';
        $d=$GLOBALS['TL_DCA']['tl_issue'];
        foreach(['config','list','palettes','fields'] as $k)
            self::assertArrayHasKey($k,$d);
        self::assertArrayHasKey('ticket_number',$d['fields']);
        foreach ($d['list']['sorting']['fields'] as $field) {
            self::assertIsString($field, 'Sorting fields must be strings for DC_Table::listView().');
        }
        self::assertArrayHasKey('journal', $d['fields']);
        self::assertSame(
            [\Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueJournalCallbacks::class, 'render'],
            $d['fields']['journal']['input_field_callback'],
        );
    }
}
