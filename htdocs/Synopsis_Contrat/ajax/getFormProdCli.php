<?php
require_once('../../main.inc.php');

require_once(DOL_DOCUMENT_ROOT."/Synopsis_Process/class/process.class.php");
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contrat.class.php");
    $_REQUEST['chrono_id'] = $_REQUEST['id'];
    $lien = new lien($db);
    $contratdet = new Synopsis_ContratLigne($db);
    $contratdet->fetch($_REQUEST['id']);
    $contrat = new Synopsis_Contrat($db);
    $contrat->fetch($contratdet->fk_contrat);
    $lien->socid = $contrat->socid;
    $lien->cssClassM = "type:contratdet";
    $lien->fetch(3);
    echo $lien->displayForm();
    ?>
