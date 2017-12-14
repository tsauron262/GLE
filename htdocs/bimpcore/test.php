<?php

require_once("../main.inc.php");





$modulepart = 'bimpcore';
$modulepart2 = 'bimpfinancement/demande';

$object = new stdClass();
$object->ref = 5;

$permission = $user->rights->societe->creer;
$permtoedit = $user->rights->societe->creer;

$object->id = 5;

$action = GETPOST("action");
$confirm = GETPOST("confirm");
$upload_dir = DOL_DATA_ROOT."/".$modulepart."/".$modulepart2."/".$object->ref;
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';

//ATTENTION pas de données envoyé avant cela
llxHeader();

$relativepathwithnofile = $modulepart2."/".$object->ref."/";
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';







llxFooter();