<?php

/*
 */
/**
 *
 * Name : listDetail.php.php
 * GLE-1.2
 */
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/chronoDetailList.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsischrono', $socid, '', '', 'Afficher');
//$user, $feature='societe', $objectid=0, $dbtablename='',$feature2='',$feature3=''


$id = $_REQUEST['id'];

$nomDiv = "gridChronoDet";
if (isset($_REQUEST['obj'])) {
    if ($_REQUEST['obj'] == "soc") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $filtre = "Société=" . urlencode($soc->nom);
        $head = societe_prepare_head($soc);
        $champ = array(1005 => $_REQUEST['id']);
    } else if ($_REQUEST['obj'] == "ctr") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $ctr = new Contrat($db);
        $ctr->fetch($_REQUEST['id']);
        $filtre = "Contrat=" . urlencode($ctr->ref);
        $head = contract_prepare_head($ctr);
        $champ = array(1005 => $ctr->socid, 1004 => $_REQUEST['id']);
    }
    if (isset($_REQUEST['create']) && $_REQUEST['create']) {
        $ch = new Chrono($db);
        $ch->model_refid = 1000;
        $id = $ch->create();
        $champ[1002] = date("d/m/Y");
        $ch->setDatas($id, $champ);
        header('location: ../Synopsis_Chrono/fiche.php?id=' . $id . '&action=Modify');
    }
}

if ($filtre != "")
    $filtre = "&_search2=true&" . $filtre;
$js .= tabChronoDetail(100, $nomDiv, $filtre);


llxHeader($js, "Appel Hotline");
dol_fiche_head($head, 'hotline', $langs->trans("Suivie hotline"));


print '<a href="' . $_SERVER['REQUEST_URI'] . '&create=true">Créer fiche hotline</a><br/><br/>  ';

print '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/js/wz_tooltip/wz_tooltip.js"></script>' . "\n";

if ($id > 0 && ($user->rights->synopsischrono->read || $user->rights->chrono_user->$tmp->voir)) {
    print "<br/>";

    print '<table id="' . $nomDiv . '" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
    print '<div id="' . $nomDiv . 'Pager" class="scroll" style="text-align:center;"></div>';
} else if ($id > 0) {
    print "<br/>";
    print "Vous ne disposez pas des droits pour voir ce chrono";
    print "<br/>";
}

//2 liste les details des chrono dans Grid
//    jQgrid Definition en fonction du type de Chrono
//     Alimentation Grid en fonction du type de Chrono
//3 Droits

llxFooter();

$db->close();
?>
