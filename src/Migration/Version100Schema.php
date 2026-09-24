<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;use Contao\CoreBundle\Migration\MigrationResult;use Doctrine\DBAL\Connection;

final class Version100Schema extends AbstractMigration 
{
    public function __construct(private readonly Connection $db)
    {} 
    
    public function shouldRun():bool
    {
        return !$this->db->createSchemaManager()->tablesExist(['tl_issue']);
    } 
    
    public function run():MigrationResult
    {
        foreach($this->sql() as $sql)$this->db->executeStatement($sql);
        
        return $this->createResult(true,'Created issue service schema and seed data.');
    } 
    
    /** @return list<string> */ 
    private function sql():array
    {
        return [
        "CREATE TABLE tl_issue_service (id INT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,title VARCHAR(160) NOT NULL,alias VARCHAR(160) NOT NULL,description LONGTEXT DEFAULT NULL,default_priority VARCHAR(16) DEFAULT 'normal' NOT NULL,default_assignee_id INT UNSIGNED DEFAULT NULL,notification_recipients LONGTEXT DEFAULT NULL,published TINYINT(1) DEFAULT 1 NOT NULL,UNIQUE INDEX uq_service_alias (alias),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_category (id INT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,service_id INT UNSIGNED NOT NULL,title VARCHAR(160) NOT NULL,alias VARCHAR(160) NOT NULL,published TINYINT(1) DEFAULT 1 NOT NULL,UNIQUE INDEX uq_category_service_alias (service_id,alias),INDEX idx_category_service (service_id),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_status (id INT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,status_key VARCHAR(32) NOT NULL,title VARCHAR(100) NOT NULL,sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL,is_initial TINYINT(1) DEFAULT 0 NOT NULL,is_resolved TINYINT(1) DEFAULT 0 NOT NULL,is_closed TINYINT(1) DEFAULT 0 NOT NULL,color VARCHAR(7) DEFAULT NULL,published TINYINT(1) DEFAULT 1 NOT NULL,UNIQUE INDEX uq_status_key (status_key),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_transition (id INT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,from_status_id INT UNSIGNED NOT NULL,to_status_id INT UNSIGNED NOT NULL,role_key VARCHAR(40) NOT NULL,require_public_comment TINYINT(1) DEFAULT 0 NOT NULL,published TINYINT(1) DEFAULT 1 NOT NULL,UNIQUE INDEX uq_transition (from_status_id,to_status_id,role_key),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_sequence (sequence_key VARCHAR(100) NOT NULL,current_value BIGINT UNSIGNED DEFAULT 0 NOT NULL,PRIMARY KEY(sequence_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,uuid BINARY(16) NOT NULL,ticket_number VARCHAR(64) NOT NULL,member_id INT UNSIGNED DEFAULT NULL,guest_access_hash CHAR(64) DEFAULT NULL,service_id INT UNSIGNED NOT NULL,category_id INT UNSIGNED DEFAULT NULL,status_id INT UNSIGNED NOT NULL,issue_type VARCHAR(32) NOT NULL,title VARCHAR(255) NOT NULL,description MEDIUMTEXT NOT NULL,priority VARCHAR(16) DEFAULT 'normal' NOT NULL,assigned_user_id INT UNSIGNED DEFAULT NULL,resolution MEDIUMTEXT DEFAULT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,last_public_activity_at DATETIME NOT NULL,resolved_at DATETIME DEFAULT NULL,closed_at DATETIME DEFAULT NULL,deleted_at DATETIME DEFAULT NULL,version INT UNSIGNED DEFAULT 1 NOT NULL,UNIQUE INDEX uq_issue_uuid (uuid),UNIQUE INDEX uq_ticket (ticket_number),INDEX idx_member_activity (member_id,last_public_activity_at),INDEX idx_service_status (service_id,status_id),INDEX idx_assignee_status (assigned_user_id,status_id),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_comment (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,issue_id BIGINT UNSIGNED NOT NULL,visibility VARCHAR(16) NOT NULL,author_type VARCHAR(16) NOT NULL,author_id INT UNSIGNED DEFAULT NULL,body MEDIUMTEXT NOT NULL,created_at DATETIME NOT NULL,edited_at DATETIME DEFAULT NULL,INDEX idx_comment_issue_created (issue_id,created_at),INDEX idx_comment_visibility (issue_id,visibility),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_attachment (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,issue_id BIGINT UNSIGNED NOT NULL,comment_id BIGINT UNSIGNED DEFAULT NULL,storage_key VARCHAR(255) NOT NULL,original_name VARCHAR(255) NOT NULL,mime_type VARCHAR(120) NOT NULL,file_size BIGINT UNSIGNED NOT NULL,sha256 CHAR(64) NOT NULL,uploaded_by_type VARCHAR(16) NOT NULL,uploaded_by_id INT UNSIGNED DEFAULT NULL,scan_status VARCHAR(20) DEFAULT 'not_scanned' NOT NULL,created_at DATETIME NOT NULL,UNIQUE INDEX uq_storage_key (storage_key),INDEX idx_attachment_issue (issue_id),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_history (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,issue_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(40) NOT NULL,actor_type VARCHAR(16) NOT NULL,actor_id INT UNSIGNED DEFAULT NULL,old_value JSON DEFAULT NULL,new_value JSON DEFAULT NULL,correlation_uuid BINARY(16) DEFAULT NULL,created_at DATETIME NOT NULL,INDEX idx_history_issue_created (issue_id,created_at),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_settings (id INT UNSIGNED AUTO_INCREMENT NOT NULL,tstamp INT UNSIGNED DEFAULT 0 NOT NULL,setting_key VARCHAR(100) NOT NULL,value_type VARCHAR(20) NOT NULL,setting_value LONGTEXT DEFAULT NULL,updated_by INT UNSIGNED DEFAULT NULL,updated_at DATETIME NOT NULL,version INT UNSIGNED DEFAULT 1 NOT NULL,UNIQUE INDEX uq_setting_key (setting_key),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_service_group (service_id INT UNSIGNED NOT NULL,user_group_id INT UNSIGNED NOT NULL,role_key VARCHAR(40) DEFAULT 'agent' NOT NULL,PRIMARY KEY(service_id,user_group_id,role_key)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "CREATE TABLE tl_issue_notification (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,event_uuid BINARY(16) NOT NULL,issue_id BIGINT UNSIGNED NOT NULL,recipient VARCHAR(254) NOT NULL,template_key VARCHAR(80) NOT NULL,status VARCHAR(20) DEFAULT 'pending' NOT NULL,attempt_count SMALLINT UNSIGNED DEFAULT 0 NOT NULL,last_error LONGTEXT DEFAULT NULL,next_attempt_at DATETIME DEFAULT NULL,sent_at DATETIME DEFAULT NULL,created_at DATETIME NOT NULL,UNIQUE INDEX uq_event_recipient (event_uuid,recipient),INDEX idx_notification_retry (status,next_attempt_at),PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE=InnoDB",
        "INSERT INTO tl_issue_status (tstamp,status_key,title,sort_order,is_initial,is_resolved,is_closed,published) VALUES (UNIX_TIMESTAMP(),'new','Neu',10,1,0,0,1),(UNIX_TIMESTAMP(),'triage','In Prüfung',20,0,0,0,1),(UNIX_TIMESTAMP(),'in_progress','In Bearbeitung',30,0,0,0,1),(UNIX_TIMESTAMP(),'waiting_user','Rückfrage',40,0,0,0,1),(UNIX_TIMESTAMP(),'resolved','Gelöst',50,0,1,0,1),(UNIX_TIMESTAMP(),'closed','Geschlossen',60,0,1,1,1),(UNIX_TIMESTAMP(),'rejected','Abgelehnt',70,0,1,1,1)",
        "INSERT INTO tl_issue_settings (tstamp,setting_key,value_type,setting_value,updated_at,version) VALUES (UNIX_TIMESTAMP(),'require_login','bool','1',NOW(),1),(UNIX_TIMESTAMP(),'ticket_pattern','string','{SERVICE}-{YEAR}-{SEQ}',NOW(),1),(UNIX_TIMESTAMP(),'allowed_extensions','json','[\\\"pdf\\\",\\\"png\\\",\\\"jpg\\\",\\\"jpeg\\\"]',NOW(),1),(UNIX_TIMESTAMP(),'max_file_size','int','10485760',NOW(),1),(UNIX_TIMESTAMP(),'max_files_per_issue','int','5',NOW(),1),(UNIX_TIMESTAMP(),'retention_policy','json','{\\\"closed_days\\\":730}',NOW(),1),(UNIX_TIMESTAMP(),'reopen_roles','json','[\\\"member\\\",\\\"agent\\\",\\\"manager\\\"]',NOW(),1),(UNIX_TIMESTAMP(),'mail_recipients','json','[]',NOW(),1),(UNIX_TIMESTAMP(),'service_scoped_permissions','bool','1',NOW(),1)"
        ];
    } 
}
