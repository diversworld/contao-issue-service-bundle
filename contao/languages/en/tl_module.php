<?php

$GLOBALS['TL_LANG']['tl_module']['issue_attachment_legend'] = 'Attachments';
$GLOBALS['TL_LANG']['tl_module']['issue_attachment_directory'] = ['Attachment directory', 'Subdirectory within var/issue-attachments, e.g. support/documents. Leave empty for the default location. Applies to new uploads; existing attachments remain accessible.'];

$GLOBALS['TL_LANG']['tl_module']['issue_attachment_folder'] = ['Attachment folder', 'Select a folder under files. Public folders allow direct access without ticket permissions. New attachments are stored there. Existing attachments remain accessible in their previous location.'];

$GLOBALS['TL_LANG']['tl_module']['issue_attachment_storage'] = ['Attachment storage', 'var: private storage, downloads require ticket permissions. files: access protection depends on whether the selected folder is public. Changes apply to new uploads only.'];
$GLOBALS['TL_LANG']['tl_module']['issue_attachment_storage_options'] = ['var' => 'Private storage in var (default)', 'files' => 'Folder in files'];
