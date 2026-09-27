<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\License\{LicenseService, LicenseValidationService, PremiumFeatureResolver};
use Diversworld\ContaoIssueServiceBundle\Application\Sla\{BusinessCalendar, SlaCalculationService, SlaEscalationService, SlaHistory, SlaReportingService};
use Diversworld\ContaoIssueServiceBundle\Application\{NotificationService, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Doctrine\DBAL\{Connection, DriverManager};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Diversworld\ContaoIssueServiceBundle\Command\{SlaReportCommand, LicenseValidateCommand};

final class SlaIntegrationTest extends TestCase
{
    private Connection $db;
    private Connection $real;
    private LicenseService $license;
    private PremiumFeatureResolver $premium;
    private SlaCalculationService $sla;
    private SlaHistory $history;
    private NotificationService $notifications;
    private int $start;
    private string $signed;
    private LicenseValidationService $validator;

    protected function setUp(): void
    {
        $this->real = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->db = $this->createStub(Connection::class);
        foreach (['fetchAssociative', 'fetchAllAssociative', 'fetchOne', 'fetchFirstColumn', 'fetchAllKeyValue', 'executeStatement'] as $method) $this->db->method($method)->willReturnCallback(fn ($sql, $params = [], $types = []) => $this->real->$method(str_replace(' FOR UPDATE', '', $sql), $params, $types));
        foreach (['insert','update','lastInsertId','isTransactionActive'] as $method) $this->db->method($method)->willReturnCallback(fn (...$args) => $this->real->$method(...$args));
        $this->db->method('getDatabasePlatform')->willReturn($this->real->getDatabasePlatform());
        $this->db->method('transactional')->willReturnCallback(fn (\Closure $callback) => $this->real->transactional(static fn (Connection $connection): mixed => $callback($connection)));
        // Build the test database from the actual DCA, avoiding a parallel hand-written SLA schema.
        foreach (['tl_issue_sla_calendar','tl_issue_sla_contract','tl_issue_sla_priority','tl_issue_sla_webhook','tl_issue_history','tl_issue_transition','tl_issue','tl_issue_service','tl_issue_status','tl_issue_comment','tl_issue_notification','tl_issue_sla','tl_issue_sla_level','tl_issue_sla_escalation','tl_issue_sla_history','tl_issue_license','tl_issue_license_validation'] as $table) {
            require dirname(__DIR__, 3).'/contao/dca/'.$table.'.php';
            $columns = [];
            foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $name => $field) {
                if (!isset($field['sql'])) continue;
                $sql = $name === 'id' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : str_ireplace(['unsigned', 'auto_increment'], '', $field['sql']);
                $columns[] = $name.' '.$sql;
            }
            $this->real->executeStatement('CREATE TABLE '.$table.' ('.implode(',', $columns).')');
        }
        $pair = sodium_crypto_sign_keypair();
        $this->validator = new LicenseValidationService(base64_encode(sodium_crypto_sign_publickey($pair)), 'test', 'example.org');
        $this->signed = LicenseValidationTest::token(['tenant' => 'test', 'domain' => 'example.org', 'issued_at' => time() - 60, 'expires_at' => time() + 86400, 'features' => ['sla'], 'mode' => 'offline', 'status' => 'valid'], sodium_crypto_sign_secretkey($pair));
        $this->license = new LicenseService($this->db, $this->validator, new MockHttpClient(), 'https://license.example.org/validate', 'test', 'example.org');
        $this->license->import($this->signed);
        $this->premium = new PremiumFeatureResolver($this->license);
        $this->history = new SlaHistory($this->db, 'test-audit-key');
        $this->sla = new SlaCalculationService($this->db, new BusinessCalendar(), $this->premium, $this->history);
        $this->notifications = new NotificationService($this->db, new SettingsService(new SettingsRepository($this->db)), $this->createStub(MailerInterface::class), 'test@example.org', null, $this->premium);
        $this->start = (new \DateTimeImmutable('2026-04-06 09:00:00'))->getTimestamp();
        $this->real->insert('tl_issue_status', ['id' => 1, 'status_key' => 'new', 'title' => 'Neu']);
        $this->real->insert('tl_issue_status', ['id' => 2, 'status_key' => 'resolved', 'title' => 'Gelöst', 'is_resolved' => 1]);
        $hours = json_encode(array_fill_keys(range(1, 7), [['00:00','24:00']]), JSON_THROW_ON_ERROR);
        $this->real->insert('tl_issue_sla', ['id' => 1,'title' => 'Test','business_hours' => $hours,'holidays' => '[]','timezone' => date_default_timezone_get(),'response_minutes' => 60,'resolve_minutes' => 480,'published' => 1]);
        $this->real->insert('tl_issue_service', ['id' => 1,'title' => 'Service','sla_id' => 1]);
        $this->real->insert('tl_issue', ['id' => 1,'status_id' => 1,'service_id' => 1,'ticket_number' => 'TEST-1','title' => 'Test','created_at' => date('Y-m-d H:i:s',$this->start)]);
    }

    /** @return array<string, mixed> */
    private function view(int $id, ?int $now = null): array
    {
        $view = $this->sla->view($id, $now);
        self::assertIsArray($view);
        return $view;
    }

    public function testCalculateAndEscalateCommands(): void
    {
        $calculate = new CommandTester(new \Diversworld\ContaoIssueServiceBundle\Command\SlaCalculateCommand($this->sla));
        self::assertSame(0, $calculate->execute([]));
        $escalate = new CommandTester(new \Diversworld\ContaoIssueServiceBundle\Command\SlaEscalateCommand(new SlaEscalationService($this->db, $this->premium, $this->sla, $this->history, $this->notifications)));
        self::assertSame(0, $escalate->execute([]));
    }

    public function testActualCommentAndWorkflowServicesDriveSlaLifecycle(): void
    {
        $this->real->executeStatement('CREATE TABLE tl_issue_profile (id INTEGER PRIMARY KEY, reopen_roles TEXT, mail_recipients TEXT)');
        $this->real->insert('tl_issue_profile', ['id'=>1,'reopen_roles'=>serialize(['agent','manager']),'mail_recipients'=>'']);
        $this->real->update('tl_issue', ['created_at'=>date('Y-m-d H:i:s',time()-600),'profile_id'=>1], ['id'=>1]);
        $this->real->insert('tl_issue_transition', ['from_status_id'=>1,'to_status_id'=>2,'role_key'=>'agent']);
        $this->real->insert('tl_issue_transition', ['from_status_id'=>2,'to_status_id'=>1,'role_key'=>'manager']);
        $settings = new SettingsService(new SettingsRepository($this->db));
        $issues = new \Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository($this->db);
        $comments = new \Diversworld\ContaoIssueServiceBundle\Application\CommentService(new \Diversworld\ContaoIssueServiceBundle\Repository\CommentRepository($this->db), $issues, $this->notifications, $this->sla);
        $tokens = new \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage();
        $user = $this->createStub(\Contao\BackendUser::class);
        $user->method('__get')->willReturnMap([['isAdmin',true],['id',9]]);
        $tokens->setToken(new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user,'contao_backend',[]));
        $workflow = new \Diversworld\ContaoIssueServiceBundle\Application\IssueWorkflowService($issues, $this->notifications, new \Diversworld\ContaoIssueServiceBundle\Security\WorkflowRoles($this->db,$settings), $settings, $tokens, $this->sla);
        $comments->addInternal(1,9,'Investigating');
        self::assertFalse($this->view(1)['responded']);
        $workflow->transition(1,2,'Resolved',1);
        self::assertTrue($this->view(1)['responded']);
        self::assertSame('paused',$this->view(1)['state']);
        $workflow->transition(1,1,null,2);
        self::assertSame('active',$this->view(1)['state']);
        self::assertTrue($this->history->verify(1));
        $this->real->update('tl_issue_license',['token'=>'invalid'],['id'=>1]);
        $comments->addPublic(1,'user',9,'Core functionality remains available');
        self::assertSame(3,(int)$this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_comment'));
    }

    public function testFrontendRenderingHidesUnlicensedSlaAndEscapesHistory(): void
    {
        $twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 3).'/templates'), ['strict_variables' => true, 'autoescape' => 'html']);
        self::assertSame('', trim($twig->render('issue/sla.html.twig', ['sla' => null])));
        $this->sla->synchronize(1, $this->start);
        $view = $this->view(1, $this->start);
        $view['history'][] = ['event_type' => '<script>alert(1)</script>', 'created_at' => $this->start];
        $html = $twig->render('issue/sla.html.twig', ['sla' => $view]);
        self::assertStringContainsString('60 Geschäftsminuten', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('SLA-Verlauf', $html);
    }

    public function testBackendRequiresAdminAndLicenseEvenForDirectCalls(): void
    {
        $settings = new SettingsService(new SettingsRepository($this->db));
        $callbacks = new \Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\SlaCallbacks($this->db, $this->premium, $this->sla, $this->license, new BusinessCalendar(), new \Diversworld\ContaoIssueServiceBundle\Security\WorkflowRoles($this->db, $settings), $this->history);
        $property = new \ReflectionProperty(\Contao\BackendUser::class, 'objInstance');
        $original = $property->getValue();
        try {
            $user = $this->createStub(\Contao\BackendUser::class);
            $user->method('__get')->willReturnMap([['isAdmin', false], ['id', 7]]);
            $property->setValue(null, $user);
            try { $callbacks->licenseAccess(); self::fail('Non-admin must be denied'); }
            catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $error) { self::assertSame(403, $error->getStatusCode()); }
            $admin = $this->createStub(\Contao\BackendUser::class);
            $admin->method('__get')->willReturnMap([['isAdmin', true], ['id', 1]]);
            $property->setValue(null, $admin);
            $callbacks->administration();
            $this->real->update('tl_issue_license', ['token'=>'invalid'], ['id'=>1]);
            $callbacks->licenseAccess(); // License import remains reachable after expiry.
            $this->expectException(\DomainException::class);
            $callbacks->administration();
        } finally {
            $property->setValue(null, $original);
        }
    }

    public function testOfflineImportAndSignedOnlineRenewal(): void
    {
        $this->real->update('tl_issue_license', ['token'=>''], ['id'=>1]);
        $file = tempnam(sys_get_temp_dir(), 'issue-license-test-');
        if ($file === false) throw new \RuntimeException('Temporary file unavailable.');
        try {
            file_put_contents($file, $this->signed);
            $command = new CommandTester(new LicenseValidateCommand($this->license));
            self::assertSame(0, $command->execute(['--offline-file'=>$file]));
        } finally { unlink($file); }
        $online = new LicenseService($this->db, $this->validator, new MockHttpClient(new MockResponse(json_encode(['token'=>$this->signed], JSON_THROW_ON_ERROR), ['http_code'=>200])), 'https://license.example.org/validate', 'test', 'example.org');
        self::assertTrue($online->refresh()['enabled']);
        self::assertSame('validated', $this->real->fetchOne('SELECT result FROM tl_issue_license_validation ORDER BY id DESC LIMIT 1'));
    }

    public function testAssignmentStaffResponseAndFrozenCalendar(): void
    {
        $this->sla->synchronize(1, $this->start);
        $view = $this->view(1, $this->start);
        self::assertSame($this->start + 3600, (int) $view['response_due_at']);
        self::assertSame(28800, $view['resolve_remaining']);
        $this->real->insert('tl_issue_comment', ['issue_id'=>1,'author_type'=>'member','visibility'=>'public','body'=>'Customer','created_at'=>date('Y-m-d H:i:s',$this->start+60)]);
        $this->real->insert('tl_issue_comment', ['issue_id'=>1,'author_type'=>'user','visibility'=>'internal','body'=>'Note','created_at'=>date('Y-m-d H:i:s',$this->start+120)]);
        $this->sla->synchronize(1, $this->start+300);
        self::assertFalse($this->view(1, $this->start+300)['responded']);
        $this->real->insert('tl_issue_comment', ['issue_id'=>1,'author_type'=>'user','visibility'=>'public','body'=>'Response','created_at'=>date('Y-m-d H:i:s',$this->start+600)]);
        $this->real->update('tl_issue_sla',['response_minutes'=>5],['id'=>1]);
        $this->sla->synchronize(1, $this->start+7200);
        self::assertTrue($this->view(1)['responded']);
        self::assertFalse($this->view(1)['breached']);
        self::assertTrue($this->history->verify(1));
    }
    public function testResolveAndReopenPreserveRemainingBudget(): void
    {
        $this->sla->synchronize(1, $this->start);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+1800)],['id'=>1]);
        $this->sla->synchronize(1, $this->start+1800);
        self::assertSame('paused',$this->view(1)['state']);
        self::assertFalse($this->view(1)['breached']);
        $this->sla->synchronize(1, $this->start+99999);
        self::assertFalse($this->view(1)['breached']);
        $this->real->update('tl_issue',['status_id'=>1,'resolved_at'=>null],['id'=>1]);
        $this->sla->synchronize(1, $this->start+86400);
        $view=$this->view(1,$this->start+86400);
        self::assertSame(27000,$view['resolve_remaining']);
        self::assertSame(1800,$view['response_remaining']);
    }
    public function testBreachesAreIdempotentAndTamperingIsDetected(): void
    {
        $this->sla->synchronize(1,$this->start+3601);
        $this->sla->synchronize(1,$this->start+3602);
        self::assertSame(1,(int)$this->real->fetchOne("SELECT COUNT(*) FROM tl_issue_sla_history WHERE event_type='response_breached'"));
        self::assertTrue($this->view(1)['breached']);
        self::assertTrue($this->history->verify(1));
        $this->real->executeStatement("UPDATE tl_issue_sla_history SET details='{}' WHERE id=1");
        self::assertFalse($this->history->verify(1));
    }
    public function testOverrideUsesCreationTimeAndDeletionExcludesProcessing(): void
    {
        $this->sla->synchronize(1,$this->start);
        $definition=$this->real->fetchAssociative('SELECT * FROM tl_issue_sla WHERE id=1');
        self::assertIsArray($definition);
        $definition['id']=2;$definition['response_minutes']=30;
        $this->real->insert('tl_issue_sla',$definition);
        $this->real->update('tl_issue',['sla_override_id'=>2],['id'=>1]);
        $this->sla->synchronize(1,$this->start+1200);
        self::assertSame($this->start+1800,(int)$this->view(1)['response_due_at']);
        $this->real->update('tl_issue',['deleted_at'=>date('Y-m-d H:i:s')],['id'=>1]);
        self::assertNull($this->sla->view(1));
    }
    public function testEscalationStagesQueueOnceAndLicenseBlocksSending(): void
    {
        // Use an old ticket so the real-time calculateAll call also detects the violation.
        $this->real->update('tl_issue',['created_at'=>date('Y-m-d H:i:s',time()-86400)],['id'=>1]);
        foreach ([1,2,3] as $stage) $this->real->insert('tl_issue_sla_escalation',['sla_id'=>1,'stage'=>$stage,'delay_minutes'=>($stage-1)*60,'recipients'=>'lead@example.org','published'=>1]);
        $service=new SlaEscalationService($this->db,$this->premium,$this->sla,$this->history,$this->notifications);
        self::assertSame(6,$service->escalate());
        self::assertSame(0,$service->escalate());
        self::assertSame(6,(int)$this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_notification'));
        $this->real->update('tl_issue_license',['token'=>'tampered'],['id'=>1]);
        self::assertSame(0,$this->notifications->dispatchPending());
        self::assertNull($this->sla->view(1));
        $this->expectException(\DomainException::class);
        $service->escalate();
    }
    public function testReportingScopeMetricsAndCommand(): void
    {
        $this->sla->synchronize(1,$this->start+3601);
        $reports=new SlaReportingService($this->db,$this->premium);
        self::assertSame(0,(int)$reports->report([])['metrics']['total']);
        self::assertSame(1,(int)$reports->report([1])['metrics']['breached']);
        self::assertNull($reports->report()['metrics']['compliance_percent']);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+7200)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+7200);
        self::assertSame(0.0,$reports->report()['metrics']['compliance_percent']);
        $command=new CommandTester(new SlaReportCommand($reports));
        self::assertSame(0,$command->execute(['--from'=>'2026-04-01','--until'=>'2026-05-01']));
        self::assertSame(1,(int)json_decode($command->getDisplay(),true)['metrics']['total']);
    }
    public function testOnlineRejectionDisablesLicenseWhileOutageDoesNotDestroyValidToken(): void
    {
        $outage=new LicenseService($this->db,$this->validator,new MockHttpClient(new MockResponse('', ['http_code'=>503])),'https://license.example.org/validate','test','example.org');
        self::assertTrue($outage->refresh()['enabled']);
        $rejected=new LicenseService($this->db,$this->validator,new MockHttpClient(new MockResponse('', ['http_code'=>403])),'https://license.example.org/validate','test','example.org');
        self::assertFalse($rejected->refresh()['enabled']);
        $command=new CommandTester(new LicenseValidateCommand($rejected));
        self::assertSame(1,$command->execute([]));
    }
    private function premiumCalculator(): SlaCalculationService
    {
        return new SlaCalculationService($this->db, new BusinessCalendar(), $this->premium, $this->history, new \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaPolicyResolver($this->db, new BusinessCalendar()));
    }

    public function testCustomerContractCalendarPriorityAndFrozenPolicy(): void
    {
        $start = (new \DateTimeImmutable('2026-10-02 17:00:00', new \DateTimeZone('Europe/Berlin')))->getTimestamp();
        $hours = json_encode(array_fill_keys(range(1, 5), [['09:00', '18:00']]), JSON_THROW_ON_ERROR);
        $this->real->insert('tl_issue_sla_calendar', ['id'=>1,'title'=>'Kunde DE-BY','country'=>'DE','region'=>'BY','timezone'=>'Europe/Berlin','business_hours'=>$hours,'holidays'=>'[]','maintenance'=>'[]','published'=>1]);
        $this->real->insert('tl_issue_sla_contract', ['id'=>1,'contract_number'=>'BUSINESS-1','member_id'=>7,'sla_id'=>1,'calendar_id'=>1,'starts_at'=>$start-86400,'ends_at'=>$start+86400,'published'=>1]);
        $this->real->insert('tl_issue_sla_priority', ['sla_id'=>1,'priority'=>'normal','response_minutes'=>480,'resolve_minutes'=>1080,'published'=>1]);
        $this->real->update('tl_issue', ['member_id'=>7,'created_at'=>date('Y-m-d H:i:s',$start)], ['id'=>1]);
        $calculator = $this->premiumCalculator();
        $calculator->synchronize(1, $start);
        $expected = (new \DateTimeImmutable('2026-10-05 16:00:00', new \DateTimeZone('Europe/Berlin')))->getTimestamp();
        self::assertSame($expected, (int) $this->view(1, $start)['response_due_at']);
        $snapshot = json_decode($this->real->fetchOne('SELECT sla_snapshot FROM tl_issue WHERE id=1'), true);
        self::assertSame('BUSINESS-1', $snapshot['contract_number']);
        $this->real->update('tl_issue_sla_contract', ['published'=>0], ['id'=>1]);
        $this->real->update('tl_issue_sla_calendar', ['holidays'=>'["2026-10-05"]'], ['id'=>1]);
        $calculator->synchronize(1, $start+300);
        self::assertSame($expected, (int) $this->view(1, $start)['response_due_at']);
        // Explicit priority change recalculates from creation, never grants a new start.
        $this->real->insert('tl_issue_sla_priority', ['sla_id'=>1,'priority'=>'critical','response_minutes'=>15,'resolve_minutes'=>60,'published'=>1]);
        $this->real->update('tl_issue', ['priority'=>'critical'], ['id'=>1]);
        $calculator->synchronize(1, $start+600);
        self::assertSame($start+900, (int) $this->view(1, $start)['response_due_at']);
    }

    public function testContractPrecedenceExpiryAndCustomerIsolation(): void
    {
        $this->real->insert('tl_issue_sla_contract', ['id'=>1,'contract_number'=>'GLOBAL','member_id'=>7,'sla_id'=>1,'starts_at'=>$this->start-100,'published'=>1]);
        $this->real->insert('tl_issue_sla_contract', ['id'=>2,'contract_number'=>'SERVICE','member_id'=>7,'service_id'=>1,'sla_id'=>1,'starts_at'=>$this->start-100,'published'=>1]);
        $resolver = new \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaPolicyResolver($this->db, new BusinessCalendar());
        $issue = $this->real->fetchAssociative('SELECT * FROM tl_issue WHERE id=1');
        self::assertIsArray($issue);
        $issue['member_id']=7;
        $policy = $resolver->resolve($issue,$this->start);
        self::assertIsArray($policy);
        self::assertSame('SERVICE', $policy['contract_number']);
        $this->real->update('tl_issue_sla_contract', ['ends_at'=>$this->start], ['id'=>2]);
        $policy = $resolver->resolve($issue,$this->start);
        self::assertIsArray($policy);
        self::assertSame('GLOBAL', $policy['contract_number']);
        $issue['member_id']=8;
        $policy = $resolver->resolve($issue,$this->start);
        self::assertIsArray($policy);
        self::assertSame(0, $policy['contract_id']);
        $issue['member_id']=7; $issue['sla_override_id']=1;
        $policy = $resolver->resolve($issue,$this->start);
        self::assertIsArray($policy);
        self::assertSame(0, $policy['contract_id']);
    }

    public function testWebhookAndStatusOnlyEscalationsAreIdempotent(): void
    {
        $this->real->insert('tl_issue_status', ['id'=>3,'status_key'=>'escalated','title'=>'Eskaliert','published'=>1]);
        $this->real->insert('tl_issue_sla_escalation', ['id'=>1,'sla_id'=>1,'stage'=>1,'recipients'=>'','webhook_url'=>'https://example.org/hook','target_status_id'=>3,'published'=>1]);
        $service = new SlaEscalationService($this->db,$this->premium,$this->sla,$this->history,$this->notifications);
        self::assertSame(1,$service->escalate($this->start+3601));
        self::assertSame(0,$service->escalate($this->start+3602));
        self::assertSame(3,(int)$this->real->fetchOne('SELECT status_id FROM tl_issue WHERE id=1'));
        self::assertSame(1,(int)$this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_sla_webhook'));
        self::assertSame(0,(int)$this->real->fetchOne('SELECT COUNT(*) FROM tl_issue_notification'));
        self::assertTrue($this->history->verify(1));
        $client = new MockHttpClient([new MockResponse('', ['http_code'=>503]),new MockResponse('', ['http_code'=>204])]);
        $dispatcher = new \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaWebhookDispatcher($this->db,$this->premium,$client);
        self::assertSame(0,$dispatcher->dispatch());
        self::assertSame('retry',$this->real->fetchOne('SELECT status FROM tl_issue_sla_webhook'));
        $this->real->executeStatement('UPDATE tl_issue_sla_webhook SET next_attempt_at=0');
        self::assertSame(1,$dispatcher->dispatch());
        self::assertSame(0,$dispatcher->dispatch());
    }

    public function testReportDurationsAndExports(): void
    {
        $this->sla->synchronize(1,$this->start);
        $this->real->insert('tl_issue_comment', ['issue_id'=>1,'author_type'=>'user','visibility'=>'public','body'=>'Response','created_at'=>date('Y-m-d H:i:s',$this->start+600)]);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+7200)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+7200);
        $report = (new SlaReportingService($this->db,$this->premium))->report(null,'2026-04-01','2026-05-01');
        self::assertSame(600.0,$report['metrics']['average_response_seconds']);
        self::assertSame(7200.0,$report['metrics']['average_resolve_seconds']);
        self::assertSame(100.0,$report['metrics']['compliance_percent']);
        self::assertSame(0,$report['metrics']['open_escalations']);
        $exporter = new \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaReportExporter($this->premium);
        self::assertStringContainsString('Ticketvolumen;1',$exporter->export($report,'csv'));
        $file = tempnam(sys_get_temp_dir(),'sla-test-');
        self::assertIsString($file);
        try {
            file_put_contents($file,$exporter->export($report,'xlsx'));
            $zip = new \ZipArchive(); self::assertTrue($zip->open($file));
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml'); self::assertIsString($xml);
            self::assertNotFalse(simplexml_load_string($xml)); $zip->close();
        } finally { unlink($file); }
        self::assertStringStartsWith('%PDF-', $exporter->export($report,'pdf'));
        $this->real->update('tl_issue_license',['token'=>'invalid'],['id'=>1]);
        $this->expectException(\DomainException::class);
        $exporter->export($report,'csv');
    }

    public function testOpenEscalationsDoNotIncludeOlderCycles(): void
    {
        $this->sla->synchronize(1,$this->start);
        $this->history->append(1,'escalated_resolve_10_1',[], $this->start);
        $this->history->append(1,'escalated_resolve_1_1',[], $this->start);
        $report = (new SlaReportingService($this->db,$this->premium))->report();
        self::assertSame(1,$report['metrics']['open_escalations']);
        self::assertSame('escalated_resolve_1_1',$report['open_escalations'][0]['event_type']);
    }

    public function testContinuousCalendarWithMaintenanceAndLicenseGatedWebhook(): void
    {
        $maintenance = json_encode([[$this->start+1800,$this->start+5400]], JSON_THROW_ON_ERROR);
        $this->real->insert('tl_issue_sla_calendar', ['id'=>1,'title'=>'24/7','timezone'=>'UTC','always_open'=>1,'business_hours'=>'{}','holidays'=>'[]','maintenance'=>$maintenance,'published'=>1]);
        $this->real->update('tl_issue_sla',['calendar_id'=>1],['id'=>1]);
        $this->premiumCalculator()->synchronize(1,$this->start);
        self::assertSame($this->start+7200,(int)$this->view(1,$this->start)['response_due_at']);
        $this->real->insert('tl_issue_sla_escalation', ['id'=>1,'sla_id'=>1,'stage'=>1,'webhook_url'=>'https://example.org/hook','published'=>1]);
        $this->real->insert('tl_issue_sla_webhook', ['issue_id'=>1,'rule_id'=>1,'event_key'=>'test','payload'=>'{}','status'=>'pending']);
        $this->real->update('tl_issue_license',['token'=>'invalid'],['id'=>1]);
        $client = new MockHttpClient(static function (): never { throw new \LogicException('Must not send without license'); });
        self::assertSame(0,(new \Diversworld\ContaoIssueServiceBundle\Application\Sla\SlaWebhookDispatcher($this->db,$this->premium,$client))->dispatch());
        self::assertSame('pending',$this->real->fetchOne('SELECT status FROM tl_issue_sla_webhook'));
    }

    public function testLaterLowerStageDoesNotDowngradeStatus(): void
    {
        foreach ([3=>'lead',4=>'management'] as $id=>$key) $this->real->insert('tl_issue_status',['id'=>$id,'status_key'=>$key,'title'=>$key,'published'=>1]);
        $this->real->insert('tl_issue_sla_escalation',['sla_id'=>1,'stage'=>1,'target_status_id'=>3,'published'=>1]);
        $this->real->insert('tl_issue_sla_escalation',['sla_id'=>1,'stage'=>3,'delay_minutes'=>60,'target_status_id'=>4,'published'=>1]);
        $service = new SlaEscalationService($this->db,$this->premium,$this->sla,$this->history,$this->notifications);
        self::assertSame(2,$service->escalate($this->start+7201));
        self::assertSame(4,(int)$this->real->fetchOne('SELECT status_id FROM tl_issue WHERE id=1'));
        self::assertSame(1,$service->escalate($this->start+28801));
        self::assertSame(4,(int)$this->real->fetchOne('SELECT status_id FROM tl_issue WHERE id=1'));
    }

    public function testCalendarEditorsRoundTripAndRejectInvalidMaintenance(): void
    {
        $callbacks = new \Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\SlaPremiumCallbacks($this->db);
        $value = serialize([['from'=>'2026-10-05T10:00:00+02:00','to'=>'2026-10-05T12:00:00+02:00']]);
        $stored = $callbacks->maintenance($value);
        self::assertSame('2026-10-05T08:00:00Z',$callbacks->loadMaintenance($stored)[0]['from']);
        self::assertSame('["2026-12-25"]',$callbacks->holidays(serialize([['date'=>'2026-12-25']])));
        self::assertSame([['date'=>'2026-12-25']],$callbacks->loadHolidays('["2026-12-25"]'));
        $this->expectException(\DomainException::class);
        $callbacks->maintenance(serialize([['from'=>'2026-02-30T10:00:00Z','to'=>'2026-03-01T12:00:00Z']]));
    }

    public function testLegacyPausedSnapshotUpgradePreservesBudgetAndCycle(): void
    {
        $this->sla->synchronize(1,$this->start);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+1800)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+1800);
        $before = $this->real->fetchAssociative('SELECT * FROM tl_issue WHERE id=1');
        self::assertIsArray($before);
        $snapshot = json_decode($before['sla_snapshot'],true);
        unset($snapshot['identity'],$snapshot['sla_id']);
        $this->real->update('tl_issue',['sla_snapshot'=>json_encode($snapshot,JSON_THROW_ON_ERROR)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+86400);
        $after = $this->real->fetchAssociative('SELECT * FROM tl_issue WHERE id=1');
        self::assertIsArray($after);
        foreach (['response_due_at','resolve_due_at','sla_remaining_seconds','sla_cycle','sla_paused_at','sla_state'] as $field) self::assertSame($before[$field],$after[$field]);
        self::assertArrayHasKey('identity',json_decode($after['sla_snapshot'],true));
    }

    public function testOverdueReopeningKeepsActualConsumedSupportTime(): void
    {
        $this->sla->synchronize(1,$this->start);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+36000)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+36000);
        $this->real->update('tl_issue',['status_id'=>1,'resolved_at'=>null],['id'=>1]);
        $this->sla->synchronize(1,$this->start+86400);
        $this->real->update('tl_issue',['status_id'=>2,'resolved_at'=>date('Y-m-d H:i:s',$this->start+90000)],['id'=>1]);
        $this->sla->synchronize(1,$this->start+90000);
        $report = (new SlaReportingService($this->db,$this->premium))->report();
        self::assertSame(39600.0,$report['metrics']['average_resolve_seconds']);
    }

}
