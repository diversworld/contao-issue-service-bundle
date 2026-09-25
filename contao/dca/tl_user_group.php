<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_user_group']['palettes']['default'] .= ';{issue_workflow_legend},issue_workflow_role,issue_workflow_services';
$GLOBALS['TL_DCA']['tl_user_group']['fields']['issue_workflow_role'] = [
    'inputType' => 'select', 'options' => ['agent', 'manager'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user_group']['issue_workflow_role_options'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_user_group']['fields']['issue_workflow_services'] = [
    'inputType' => 'checkbox', 'foreignKey' => 'tl_issue_service.title',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'], 'sql' => 'blob NULL',
];
