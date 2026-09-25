<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\AttachmentDirectoryCallback;

$GLOBALS['TL_DCA']['tl_issue_profile'] = [
    'config' => ['dataContainer' => DC_Table::class, 'enableVersioning' => true, 'notDeletable' => true, 'sql' => ['keys' => ['id' => 'primary']]],
    'list' => [
        'sorting' => ['mode' => DataContainer::MODE_SORTED, 'fields' => ['title'], 'flag' => DataContainer::SORT_INITIAL_LETTER_ASC, 'panelLayout' => 'search,limit'],
        'label' => ['fields' => ['title'], 'format' => '%s'],
        'operations' => ['edit', 'copy', 'show'],
    ],
    'palettes' => [
        'default' => '{title_legend},title,description;{ticket_legend},ticket_pattern,require_login;{upload_legend},allowed_extensions,max_file_size,max_files_per_issue,attachment_storage,attachment_directory,attachment_folder;{notification_legend},mail_recipients;{policy_legend},retention_days,reopen_roles,service_scoped_permissions',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int unsigned NOT NULL auto_increment'
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text', 
            'eval' => ['mandatory' => true, 'maxlength' => 160, 'tl_class' => 'w50'], 
            'search' => true, 
            'sql' => "varchar(160) NOT NULL default ''"
        ],
        'description' => [
            'inputType' => 'textarea', 
            'eval' => ['tl_class' => 'clr'], 'sql' => 'text NULL'
        ],
        'ticket_pattern' => [
            'inputType' => 'text', 
            'default' => '{SERVICE}-{YEAR}-{SEQ}', 
            'eval' => ['mandatory' => true, 'maxlength' => 160, 'tl_class' => 'w50'],
            'sql' => "varchar(160) NOT NULL default '{SERVICE}-{YEAR}-{SEQ}'"
        ],
        'require_login' => [
            'inputType' => 'checkbox', 
            'default' => '1', 'eval' => ['tl_class' => 'w50 m12'], 
            'sql' => "char(1) NOT NULL default '1'"
        ],
        'allowed_extensions' => [
            'inputType' => 'checkbox', 
            'options' => ['pdf','png','jpg','jpeg','gif','webp','doc','docx','xls','xlsx'], 
            'default' => ['pdf','png','jpg','jpeg'], 
            'eval' => ['multiple' => true, 'tl_class' => 'clr'], 
            'sql' => 'blob NULL'
        ],
        'max_file_size' => [
            'inputType' => 'text', 
            'default' => 10485760, 
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'w50'], 
            'sql' => 'int unsigned NOT NULL default 10485760'
        ],
        'max_files_per_issue' => [
            'inputType' => 'text', 
            'default' => 5, 
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'w50'], 
            'sql' => 'int unsigned NOT NULL default 5'
        ],
        'attachment_storage' => [
            'inputType' => 'select', 
            'options' => ['var','files'], 
            'reference' => &$GLOBALS['TL_LANG']['tl_issue_profile']['attachment_storage_options'], 
            'default' => 'var', 
            'eval' => ['tl_class' => 'clr w50'], 
            'sql' => "varchar(8) NOT NULL default 'var'"
        ],
        'attachment_directory' => [
            'inputType' => 'text', 
            'eval' => ['maxlength' => 160, 'tl_class' => 'w50'], 
            'save_callback' => [[AttachmentDirectoryCallback::class, 'validate']], 
            'sql' => "varchar(160) NOT NULL default ''"
        ],
        'attachment_folder' => [
            'inputType' => 'fileTree', 
            'eval' => ['fieldType' => 'radio', 'files' => false, 'tl_class' => 'clr'], 
            'save_callback' => [[AttachmentDirectoryCallback::class, 'validateFolder']], 
            'sql' => 'binary(16) NULL'
        ],
        'mail_recipients' => [
            'inputType' => 'textarea', 
            'eval' => ['tl_class' => 'clr'], 
            'sql' => 'text NULL'
        ],
        'retention_days' => [
            'inputType' => 'text', 
            'default' => 730, 
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'tl_class' => 'w50'], 
            'sql' => 'int unsigned NOT NULL default 730'
        ],
        'reopen_roles' => [
            'inputType' => 'checkbox', 
            'options' => ['member','agent','manager'], 
            'default' => ['member','agent','manager'], 
            'eval' => ['multiple' => true, 'tl_class' => 'clr'], 
            'sql' => 'blob NULL'
        ],
        'service_scoped_permissions' => [
            'inputType' => 'checkbox', 
            'default' => '1', 
            'eval' => ['tl_class' => 'clr'], 
            'sql' => "char(1) NOT NULL default '1'"
        ],
    ],
];
