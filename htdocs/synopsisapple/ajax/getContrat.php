<?php

require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php";

$reqProcc = new requete($db);
$reqProcc->fetch(1006);
$reqProcc->getValues();