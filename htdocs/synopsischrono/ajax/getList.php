<?php

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/synopsischrono/chronoFiche.lib.php");
    $tabT = explode("-", $_REQUEST['chrid-keyid']);
    if($_REQUEST['socid'] > 0)
    getValueForm($tabT[0], $tabT[1], $_REQUEST['socid'], false);