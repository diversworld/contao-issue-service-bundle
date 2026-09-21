<?php
declare(strict_types=1);
use Contao\DC_Table;
$GLOBALS['TL_DCA']['tl_issue_category']=[
    'config'=>[
        'dataContainer'=>DC_Table::class,
        'enableVersioning'=>true,
        'sql'=>['keys'=>['id'=>'primary']
        ]
    ],
    'list'=>[
        'sorting'=>[
            'mode'=>1,
        'fields'=>[
            'id'],
            'flag'=>1],
            'label'=>[
                'fields'=>[
                    'id'],
                    'format'=>'%s'],
                    'operations'=>[
                        'edit'=>[
                            'href'=>'act=edit',
                            'icon'=>'edit.svg'
                        ],
                        'delete'=>[
                            'href'=>'act=delete',
                            'icon'=>'delete.svg'
                        ],
                        'show'=>[
                            'href'=>'act=show',
                            'icon'=>'show.svg'
                        ]
                    ]
                ],
                'palettes'=>[
                    'default'=>'{title_legend},service_id,title,alias,published'
                ],
                'fields'=>[
                    'id'=>['sql'=>"int(10) unsigned NOT NULL auto_increment"],
                    'tstamp'=>['sql'=>"int(10) unsigned NOT NULL default 0"],
                    'service_id'=>['inputType'=>'text','eval'=>['mandatory'=>true],'sql'=>"varchar(160) NOT NULL default ''"],
                    'title'=>['inputType'=>'text','eval'=>['mandatory'=>true],'sql'=>"varchar(160) NOT NULL default ''"],
                    'alias'=>['inputType'=>'text','eval'=>['mandatory'=>true],'sql'=>"varchar(160) NOT NULL default ''"],'published'=>['inputType'=>'checkbox','eval'=>['mandatory'=>false],'sql'=>"char(1) NOT NULL default ''"]]];
