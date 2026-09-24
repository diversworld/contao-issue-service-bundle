<?php 

declare(strict_types=1); 

namespace Diversworld\ContaoIssueServiceBundle\Tests\Static; 

use PHPUnit\Framework\TestCase; 

final class BundleStructureTest extends TestCase 
{ 
    public function testRequiredFilesExist():void
    {
        foreach([
            'composer.json',
            'config/routes.yaml',
            'config/services.yaml',
            'src/ContaoManager/Plugin.php',
            'contao/dca/tl_issue.php'
            ] as $f
        ) {
            self::assertFileExists(dirname(__DIR__,2).'/'.$f);
        }
    }

    public function testComposerTypeAndConstraint():void
    {
        $json=file_get_contents(dirname(__DIR__,2).'/composer.json');
        self::assertIsString($json);
        $c=json_decode($json,true,512,JSON_THROW_ON_ERROR);
        self::assertSame('contao-bundle',$c['type']);
        self::assertSame('^5.7 || ^6.0',$c['require']['contao/core-bundle']);
        self::assertArrayHasKey('contao/manager-plugin',$c['conflict']);
    }
}
