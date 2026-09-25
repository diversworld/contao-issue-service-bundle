<?php 

declare(strict_types=1); 

namespace Diversworld\ContaoIssueServiceBundle\Tests\Static; 
use PHPUnit\Framework\TestCase; 

final class DcaShapeTest extends TestCase 
{ 
    public function testSettingsBackendUsesCompleteConfigurationRecords(): void
    {
        require dirname(__DIR__, 2).'/contao/config/config.php';
        self::assertSame('tl_issue_profile', $GLOBALS['BE_MOD']['issue_service_management']['issue_service_settings']['tables'][0]);
    }

    public function testProfilesExposeAllSettingsTogether(): void
    {
        require dirname(__DIR__, 2).'/contao/dca/tl_issue_profile.php';
        $dca = $GLOBALS['TL_DCA']['tl_issue_profile'];
        foreach (['title', 'ticket_pattern', 'require_login', 'allowed_extensions', 'max_file_size', 'max_files_per_issue', 'mail_recipients', 'retention_days', 'reopen_roles', 'service_scoped_permissions', 'attachment_storage'] as $field) {
            self::assertArrayHasKey($field, $dca['fields']);
            self::assertStringContainsString($field, $dca['palettes']['default']);
        }
        self::assertStringContainsString('attachment_directory,attachment_folder', $dca['palettes']['default']);
        self::assertFalse($dca['fields']['attachment_storage']['eval']['submitOnChange'] ?? false);
        self::assertFalse($dca['fields']['attachment_folder']['eval']['mandatory'] ?? false);
        self::assertContains('copy', $dca['list']['operations']);
    }

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
