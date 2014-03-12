<?php

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_Chrono/chronoFiche.lib.php");
    $tabT = explode("-", $_REQUEST['chrid-keyid']);
    getValueForm($tabT[0], $tabT[1], $_REQUEST['socid'], false);