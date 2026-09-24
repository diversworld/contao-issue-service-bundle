<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\IssueDcaCallbacks;

$GLOBALS['TL_DCA']['tl_issue'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'notCopyable' => true,
        'notDeletable' => true,
        'enableVersioning' => true,
        'onsubmit_callback' => [[IssueDcaCallbacks::class, 'updateGeneratedFields']],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'uuid' => 'unique',
                'ticket_number' => 'unique',
                'member_id,last_public_activity_at' => 'index',
                'service_id,status_id' => 'index',
                'assigned_user_id,status_id' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['last_public_activity_at DESC'],
            'flag' => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['ticket_number', 'title', 'status_id', 'priority'],
            'format' => '%s - %s [%s/%s]',
        ],
        'operations' => [
            'edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => [
        'default' => '{issue_legend},ticket_number,title,description,issue_type,service_id,category_id;{processing_legend},status_id,priority,assigned_user_id,resolution;{time_legend},created_at,updated_at,last_public_activity_at,resolved_at,closed_at',
    ],
    'fields' => [
        'id' => ['sql' => 'bigint unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
        'uuid' => ['sql' => 'binary(16) NULL'],
        'ticket_number' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true],
            'search' => true,
            'sql' => 'varchar(64) NULL',
        ],
        'member_id' => ['sql' => 'int unsigned NULL'],
        'guest_access_hash' => ['sql' => 'char(64) NULL'],
        'service_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_service.title',
            'eval' => ['mandatory' => true, 'chosen' => true],
            'filter' => true,
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'category_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_category.title',
            'eval' => ['includeBlankOption' => true, 'chosen' => true],
            'filter' => true,
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
            'sql' => 'int unsigned NULL',
        ],
        'status_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_issue_status.title',
            'eval' => ['mandatory' => true, 'chosen' => true],
            'filter' => true,
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'issue_type' => [
            'inputType' => 'select',
            'options' => ['incident', 'bug', 'improvement', 'request', 'question'],
            'filter' => true,
            'sql' => "varchar(32) NOT NULL default 'request'",
        ],
        'title' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
            'search' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'description' => [
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rte' => 'tinyMCE'],
            'sql' => 'mediumtext NULL',
        ],
        'priority' => [
            'inputType' => 'select',
            'options' => ['low', 'normal', 'high', 'critical'],
            'filter' => true,
            'sql' => "varchar(16) NOT NULL default 'normal'",
        ],
        'assigned_user_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_user.name',
            'eval' => ['includeBlankOption' => true, 'chosen' => true],
            'filter' => true,
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
            'sql' => 'int unsigned NULL',
        ],
        'resolution' => [
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE'],
            'sql' => 'mediumtext NULL',
        ],
        'created_at' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'sql' => 'datetime NULL'],
        'updated_at' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'sql' => 'datetime NULL'],
        'last_public_activity_at' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'sql' => 'datetime NULL'],
        'resolved_at' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'sql' => 'datetime NULL'],
        'closed_at' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'sql' => 'datetime NULL'],
        'deleted_at' => ['sql' => 'datetime NULL'],
        'version' => ['sql' => "int unsigned NOT NULL default 1"],
    ],
];