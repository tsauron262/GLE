<?php

/*
 */
/**
 *
 * Name : listDetail.php.php
 * GLE-1.2
 */
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/chronoDetailList.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");

// Security check
$socid = isset($_GET["socid"]) ? $_GET["socid"] : '';
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'synopsischrono', $socid, '', '', 'Afficher');
//$user, $feature='societe', $objectid=0, $dbtablename='',$feature2='',$feature3=''


$id = $_REQUEST['id'];
$modelT = $_REQUEST['model'];

$js = $html = $html2 = $titreGege = "";


//$tabModel = array(100, 101);

$tabModel = array();
$champ = array();
if (isset($_REQUEST['obj'])) {
    if ($_REQUEST['obj'] == "soc") {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $obj = $soc;
        $filtre = "fk_soc=" . urlencode($_REQUEST['id']);
        $head = societe_prepare_head($soc);
        $socid = $_REQUEST['id'];
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active= 1 AND `hasSociete` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));

        $titreGege = $soc->getNomUrl();
//            $tabModel[$result->id] = $result->titre;
    } else if ($_REQUEST['obj'] == "ctr") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
        $ctr = new Contrat($db);
        $ctr->fetch($_REQUEST['id']);
        $obj = $ctr;
//        $filtre = "Contrat=" . urlencode($ctr->ref);
        $filtre = "fk_contrat=" . $ctr->id;
        $head = contract_prepare_head($ctr);
        $socid = $ctr->socid;
        $ctrId = $_REQUEST['id'];
        $sql = $db->query("SELECT c.* FROM `" . MAIN_DB_PREFIX . "synopsischrono_key`, `" . MAIN_DB_PREFIX . "synopsischrono_conf` c WHERE `type_valeur` = 6 AND `type_subvaleur` IN (1000, 1007) AND model_refid = c.id GROUP by c.id " . (isset($modelT) ? " AND c.id=" . $modelT : ""));
    } else if ($_REQUEST['obj'] == "project") {
        $langs->load("project@projet");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/synopsis_project.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
        $projet = new Project($db);
        $projet->fetch($_REQUEST['id']);
        $obj = $projet;
        $filtre = "fk_projet=" . $projet->id;
        $champ['fk_projet'] = $projet->id;
        $head = synopsis_project_prepare_head($projet);
        $socid = $projet->socid;
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active= 1 AND `hasProjet` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));
    } else if ($_REQUEST['obj'] == "propal") {
        $langs->load("contracts");
        require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        $projet = new Propal($db);
        $projet->fetch($_REQUEST['id']);
        $obj = $projet;
        $filtre = "fk_propal=" . $projet->id;
        $champ['fk_propal'] = $projet->id;
        $head = propal_prepare_head($projet);
        $socid = $projet->socid;
        $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active= 1 AND `hasPropal` = 1" . (isset($modelT) ? " AND id=" . $modelT : ""));
    }
} else {
    $sql = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "synopsischrono_conf` WHERE active= 1 " . (isset($_REQUEST['chronoDet']) ? " AND id = " . $_REQUEST['chronoDet'] : ""));
}
while ($result = $db->fetch_object($sql)) {
    $nomI = $result->titre;
    $titre = $nomI;
    if (isset($result->picto) && $result->picto != '') {
        $result->picto = preg_replace('/\[KEY\|[0-9]*-[a-zA-Z0-9]*\]/', "$1", $result->picto);
        $titre = img_picto($nomI, "object_" . $result->picto) . "  " . $nomI;
    }
    $tabModel[$result->id] = array('nomModel' => $nomI, 'titre' => $titre);
}


if ($filtre != "")
    $filtre = "&_search2=true&" . $filtre;

foreach ($tabModel as $model => $data) {
    $nomModel = $data['nomModel'];
    $titre = $data['titre'];
    $nomDiv = "gridChronoDet" . $model;
    if ($model == 100) {
        if (isset($ctrId))
            $champ[1037] = $ctrId;
//        $champ[1001] = date("d/m/Y H:i");
//        $titre = "Appel Hotline";
        $nomOnglet = "hotline";

        if (isset($_REQUEST['Etat'])) {
            $titre .= " " . $_REQUEST['Etat'];
            $champ['1034'] = $_REQUEST['Etat'];
            $filtre .= "&Etat=" . $_REQUEST['Etat'];
        }
    } elseif ($model == 101) {
//        $titre = "Produit Client";
        $nomOnglet = "productCli";
    } elseif ($model == 105) {//SAV
        $nomOnglet = "SAV";
//        $titre = "SAV";
        if (isset($_REQUEST['FiltreCentre'])) {
            $champ['1060'] = $_REQUEST['FiltreCentre'];
            $filtre .= "&FiltreCentre=" . $_REQUEST['FiltreCentre'];
        }
        if (isset($_REQUEST['Etat'])) {
            $titre .= " " . $_REQUEST['Etat'];
            $champ['1056'] = $_REQUEST['Etat'];
            $filtre .= "&Etat=" . $_REQUEST['Etat'];
        }
    }




    $champJs = "tabChamp = new Array();";
    foreach ($champ as $id => $val) {
        $champJs .= "tabChamp[\"" . $id . "\"]=\"" . $val . "\";";
    }
    $js .= tabChronoDetail($model, $nomDiv, $filtre);





    $html2 .= "<li><a href='#pan" . $nomDiv . "'>" . $titre . "</a></li>";


    $html .= '<div id="pan' . $nomDiv . '">';

    if ($model == 105) {
//        $html .= "<a class='butAction' href='".DOL_URL_ROOT."/synopsistools/FicheRapide.php/'>Créer SAV</a><br/><br/>";
    } else {
        $html .= "<a class='butAction' onclick='" . $champJs . " "
                . "         ajaxAddChrono(" . $model . ", \"" . $socid . "\", tabChamp, function(id){"
                . "                                                                     dispatchePopObject(id, \"chrono\", function(){ "
                . "                                                                             $(\".ui-icon-refresh\").trigger(\"click\");"
                . "                                                                     }, \"New " . $nomModel . "\", 1); "
                . "                                                                  });'>Créer " . $titre . "</a><br/><br/>";
    }

    $html .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/js/wz_tooltip/wz_tooltip.js"></script>' . "\n";

    if (($user->rights->synopsischrono->read || $user->rights->chrono_user->$tmp->voir)) {

        $html .= '<table id="' . $nomDiv . '" class="scroll ui-widget " cellpadding="0" cellspacing="0"></table>';
        $html .= '<div id="' . $nomDiv . 'Pager" class="scroll" style="text-align:center;"></div>';
    } else {
        $html .= "<br/>";
        $html .= "Vous ne disposez pas des droits pour voir ce chrono";
        $html .= "<br/>";
    }
    $html .= "</div>";
}

if (count($tabModel) > 1) {
    $nomOnglet = "Chrono";
    $titre = "Chrono";



}
    $js .=<<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#tabs').tabs({
        cache: true,
        spinner: 'Chargement ...',
        fx: {opacity: 'toggle' },
        activate: function(event, ui) {
            hash = ui.newTab.find("a").attr("href");
            window.location.hash = hash;
            $.cookie("currentTab", hash, { expires: 7 });
            
            eval("init"+hash.replace("#", "")+"();");
        }
    });
    id = 0;
    if(typeof($.cookie("currentTab")) == 'string' && $($.cookie("currentTab")).size() > 0){
        id = $.cookie("currentTab").replace("#", "");
    }
    else{
        id = $(".ui-tabs-nav a").attr("href").replace("#", "");
    }
    eval("init"+id+"();");
    $("li[aria-controls='" + id + "'] a").click();
});
</script>
EOF;

llxHeader($js, $nomOnglet);
dol_fiche_head($head, 'chrono', $langs->trans($nomOnglet));

$form = new Form($db);
if ($obj) {
    print '<table class="border" width="100%">';
    print '<tr><td width="25%">' . $langs->trans('Nom élément') . '</td>';
    print '<td colspan="3">';
    $champ = 'ref';
    if (isset($obj->nom))
        $champ = 'nom';
    print $form->showrefnav($obj, 'obj=' . $_REQUEST['obj'] . '&id', '', ($user->societe_id ? 0 : 1), 'rowid', $champ);
    print '</td></tr>';
    if ($obj != $soc && $socid > 0) {
        $soc = new Societe($db);
        $soc->fetch($socid);
        print '<tr><td width="25%">' . $langs->trans('ThirdPartyName') . '</td>';
        print '<td colspan="3">';
        print $soc->getNomUrl(1);
        print '</td></tr>';
    }

    print '</table></br>';
}
    print "<div id='tabs'>";
if (count($tabModel) > 1) {
    print "<ul>";
} else {
    print "<ul style='display:none;'>";
}
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
