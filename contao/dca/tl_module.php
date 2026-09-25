<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_module']['palettes']['issue_service_list'] = '{title_legend},name,headline,type;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['issue_service_create'] = '{title_legend},name,headline,type;{issue_attachment_legend},issue_attachment_folder;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['issue_service_detail'] = '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['issue_attachment_directory'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 160, 'tl_class' => 'w50', 'decodeEntities' => true],
    'save_callback' => [[\Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\AttachmentDirectoryCallback::class, 'validate']],
    'sql' => "varchar(160) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['issue_attachment_folder'] = [
    'inputType' => 'fileTree',
    'eval' => ['fieldType' => 'radio', 'files' => false, 'mandatory' => true, 'tl_class' => 'clr'],
    'save_callback' => [[\Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\AttachmentDirectoryCallback::class, 'validateFolder']],
    'sql' => 'binary(16) NULL',
];
