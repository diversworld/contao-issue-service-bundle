<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_issue_comment'] = [
    'config' => ['dataContainer' => DC_Table::class, 'notCopyable' => true, 'sql' => ['keys' => ['id' => 'primary', 'issue_id,created_at' => 'index', 'issue_id,visibility' => 'index']]],
    'list' => ['sorting' => ['fields' => ['created_at DESC']], 'label' => ['fields' => ['created_at', 'author_type', 'body'], 'format' => '%s [%s] %s'], 'operations' => ['show' => ['href' => 'act=show', 'icon' => 'show.svg']]],
    'palettes' => ['default' => '{comment_legend},visibility,author_type,author_id,body,created_at,edited_at'],
    'fields' => [
        'id' => ['sql' => 'bigint unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => "int unsigned NOT NULL default 0"],
        'issue_id' => ['sql' => "bigint unsigned NOT NULL default 0"],
        'visibility' => ['inputType' => 'select', 'options' => ['public', 'internal'], 'sql' => "varchar(16) NOT NULL default 'public'"],
        'author_type' => ['inputType' => 'select', 'options' => ['member', 'guest', 'user', 'system'], 'sql' => "varchar(16) NOT NULL default 'system'"],
        'author_id' => ['sql' => 'int unsigned NULL'],
        'body' => ['inputType' => 'textarea', 'sql' => 'mediumtext NOT NULL'],
        'created_at' => ['sql' => 'datetime NOT NULL'],
        'edited_at' => ['sql' => 'datetime NULL'],
    ],
];