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
$modelT = $_REQUEST['model'];

$js = $html = $html2 = "";


//$tabModel = array(100, 101);

$tabModel = array();
if (isset($_REQUEST['obj'])) {
    if ($_REQUEST['obj'] == "soc") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $filtre = "fk_societe=" . urlencode($_REQUEST['id']);
        $head = societe_prepare_head($soc);
        $champ = array();
        $socid = $_REQUEST['id'];
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Chrono_conf` WHERE active= 1 AND `hasSociete` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));
        while ($result = $db->fetch_object($sql))
            $tabModel[$result->id] = $result->titre;
    } else if ($_REQUEST['obj'] == "ctr") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $ctr = new Contrat($db);
        $ctr->fetch($_REQUEST['id']);
        $filtre = "Contrat=" . urlencode($ctr->ref);
        $head = contract_prepare_head($ctr);
        $socid = $ctr->socid;
        $ctrId = $_REQUEST['id'];
    } else if ($_REQUEST['obj'] == "project") {
        $langs->load("project@projet");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/synopsis_project.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $projet = new Project($db);
        $projet->fetch($_REQUEST['id']);
        $filtre = "fk_projet=" . $projet->id;
        $head = synopsis_project_prepare_head($projet);
        $socid = $projet->socid;
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Chrono_conf` WHERE active= 1 AND `hasProjet` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));
        while ($result = $db->fetch_object($sql))
            $tabModel[$result->id] = $result->titre;
    } else if ($_REQUEST['obj'] == "propal") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $projet = new Propal($db);
        $projet->fetch($_REQUEST['id']);
        $filtre = "fk_propal=" . $projet->id;
        $head = propal_prepare_head($projet);
        $socid = $projet->socid;
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Chrono_conf` WHERE active= 1 AND `hasPropal` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));
        while ($result = $db->fetch_object($sql))
            $tabModel[$result->id] = $result->titre;
    }
}


if ($filtre != "")
    $filtre = "&_search2=true&" . $filtre;

foreach ($tabModel as $model => $nomModel) {
    $nomDiv = "gridChronoDet" . $model;
    $champ = array();
    if ($model == 100) {
        if (isset($ctrId))
            $champ[1004] = $ctrId;
        $champ[1001] = date("d/m/Y");
//        $titre = "Appel Hotline";
        $nomOnglet = "hotline";
    }
    elseif ($model == 101) {
//        $titre = "Produit Client";
        $nomOnglet = "productCli";
    }

    $titre = $nomModel;


    $champJs = "tabChamp = new Array();";
    foreach ($champ as $id => $val) {
        $champJs .= "tabChamp[" . $id . "]=\"" . $val . "\";";
    }

    $js .= tabChronoDetail($model, $nomDiv, $filtre);





    $html2 .= "<li><a href='#pan" . $nomDiv . "'>" . $titre . "</a></li>";


    $html .= '<div id="pan' . $nomDiv . '">';
    $html .= "<input type='button'onclick='" . $champJs . " ajaxAddChrono(" . $model . ", " . $socid . ", tabChamp, function(id){popChrono(id, function(){ $(\".ui-icon-refresh\").trigger(\"click\");}); });' class='butAction' value = 'Créer " . $titre . "' /><br/><br/>";

    $html .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/js/wz_tooltip/wz_tooltip.js"></script>' . "\n";

    if ($id > 0 && ($user->rights->synopsischrono->read || $user->rights->chrono_user->$tmp->voir)) {

        $html .= '<table id="' . $nomDiv . '" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
        $html .= '<div id="' . $nomDiv . 'Pager" class="scroll" style="text-align:center;"></div>';
        $html .= "<br/>";
        $html .= "<br/>";
    } else if ($id > 0) {
        $html .= "<br/>";
        $html .= "Vous ne disposez pas des droits pour voir ce chrono";
        $html .= "<br/>";
    }
    $html .= "</div>";
}

if (count($tabModel) > 1) {
    $nomOnglet = "chrono";
    $titre = "Chrono";
}



$js .=<<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        cache: true,
        spinner: 'Chargement ...',
        fx: {opacity: 'toggle' }
    })
});
</script>
EOF;


llxHeader($js, $titre);
dol_fiche_head($head, $nomOnglet, $langs->trans($titre));


print "<div id='tabs'>";
print "<ul>";
echo $html2;

print "</ul>";

echo $html;

echo "</div>";
//2 liste les details des chrono dans Grid
//    jQgrid Definition en fonction du type de Chrono
//     Alimentation Grid en fonction du type de Chrono
//3 Droits

llxFooter();

$db->close();
?>
