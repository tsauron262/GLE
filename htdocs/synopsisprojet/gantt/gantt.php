<?php

require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/core/lib/synopsis_project.lib.php');
require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/core/lib/synopsis_project.lib.php");


//TODO + arr trancheHoraire selon add / mod +  save ajax
//TODO group ds le reste ds php
//TODO :> prob si equipe selecte, puis cancel puis modif d'une autre task
//TODO tooltip horaire specique
//TODO deco trche table usr

$project_id = $_REQUEST['id'];


$projet = new SynopsisProject($db);
$projet->fetch($project_id);
$projet->societe->fetch($projet->societe->id);



if ($project_id . "x" == "x") {
    $project_id = -1;
}
$csspath = DOL_URL_ROOT . '/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT . '/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/';
//
$header = '<link rel="stylesheet" type="text/css" href="css/jsgantt.css" />' . "\n";
$header .= '<link rel="stylesheet" type="text/css" href="css/GLEgantt.css" />' . "\n";
$header .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/includes/jquery/css/smoothness/jquery-ui-latest.custom.css" />' . "\n";
//
$header .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-latest.min.js"></script>' . "\n";
$header .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-ui-latest.custom.min.js"></script>' . "\n";
$header .= '<script language="javascript"  src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js"></script>' . "\n";
$header .= '<script language="javascript"  src="' . $jspath . 'farbtastic.js"></script>' . "\n";
$header .= '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.datetimepicker.js" type="text/javascript"></script>';
$header .= '<link rel="stylesheet" href="' . $csspath . 'farbtastic.css" type="text/css" />' . "\n";

//
$header .= '<script language="javascript" src="js/jsgantt.js"></script>' . "\n";
//$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.resizable.js"></script>'."\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . '../jquery.grid.columnSizing.pack.js"></script>' . "\n";
//$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.draggable-patched.js"></script>'."\n";
$header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/tiptip/jquery.tipTip.js"></script>' . "\n";

//$header .= '<script language="javascript"  src="'.$jspath.'jquery.dimensions.js"></script>'."\n";
$header .= '<script language="javascript"  src="' . $jspath . 'jquery.cookie.js"></script>' . "\n";
$header .= '<script language="javascript"  src="' . $jspath . 'jquery.iutil.pack.js"></script>' . "\n";
$header .= '<script language="javascript"  src="' . $jspath . 'jquery.idrag.js"/>' . "\n";
$header .= '<script language="javascript"  src="' . $jspath . 'jquery.grid.columnSizing.pack.js"></script>' . "\n";

$header .= '<script language="javascript"  src="' . $jspath . 'jquery.tooltip.js"></script>' . "\n";

$header .= '<script language="javascript" src="' . $jspath . 'jquery.context-Menu.js"></script>' . "\n";
$header .= '<link rel="stylesheet" href="' . $csspath . 'jquery.contextMenu.css" type="text/css" />' . "\n";

$header .= "<style>body { min-width: 1200px; position: static; } .fiche { min-width:1100px;  }</style>";

$header .= '<script language="javascript" src="' . $jspath . 'jquery.treeview-manualopen.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.core.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.slide.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.bounce.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.shake.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.highlight.js"></script>' . "\n";
$header .= '<script language="javascript" src="' . $jqueryuipath . 'effects.scale.js"></script>' . "\n";


$header .= '<link rel="stylesheet" href="' . $csspath . 'jquery.treeview.css" type="text/css" />' . "\n";
$header .= "<style>.contextMenu{ width: 200px; } table { border-collapse: collapse} .promoteZZ{ z-index: 2006; position: absolute; } .delFromDepTableMod{ cursor: pointer; } .delFromDepTableadd{ cursor: pointer; }  .addDep{ cursor: pointer; } #divTitle{ fonsize: 120%; } .horaire { font-size:75%; }.horaire td { text-align: center; } .treeview li {  cursor: pointer; } #AddToTable { cursor: pointer; }  .notSelectable { font-style: italic; color: #CCCCCC; cursor: no-drop;  } .delFromTable { cursor: pointer; } #SubAccordion{ overflow: hidden; max-height: 300px min-height: 250px; font-size:75%; } #fragmentadd-2{ font-size:75%; } .treeview span { font-weight: 500; padding-left: 3px; } .treeview li { margin-top: 1px; font-size: 90%; padding-top: 3px;  }</style>";


$header .= '<script language="javascript" src="' . DOL_URL_ROOT . '/synopsisprojet/gantt/js/GLEgantt.js"></script>' . "\n";

$header .= '<script language="javascript">' . "\n";
$header .= '    var project_id = ' . $project_id . ";" . "\n";
$header .= '    var user_id = ' . $user->id . ";" . "\n";
$header .= '</script>' . "\n";


//get Tranche Horaire par type => test.js
$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire
          ORDER BY day,
                    abs(SUBSTRING(debut,1,2)) ASC ,
                    abs(SUBSTRING(debut,-2)) ASC ";
$sql = $db->query($requete);
$header .= "<script>";
$header .= " var facteurDefault = 100;";
$header .= " var DOL_DOCUMENT_ROOT = '" . DOL_DOCUMENT_ROOT . "';";
$header .= " var DOL_URL_ROOT = '" . DOL_URL_ROOT . "';";
$header .= " var trancheHoraire = new Array();";
$header .= "     trancheHoraire[1] = new Array();";
$header .= "     trancheHoraire[6] = new Array();";
$header .= "     trancheHoraire[7] = new Array();";
$header .= "     trancheHoraire[8] = new Array();";
while ($res = $db->fetch_object($sql)) {
    $header .= "\n";
    if ($res->day . "x" == "x") {
        $header .= 'jour = 1;';
    } else {
        $header .= 'jour = ' . $res->day . ';';
    }


    $debut = 0;
    if (preg_match('/([0-9]{2}):([0-9]{2})/', $res->debut, $arr)) {
        $debut = intval($arr[1]) * 3600 + intval($arr[2]) * 60;
    }
    $fin = 0;
    if (preg_match('/([0-9]{2}):([0-9]{2})/', $res->fin, $arr)) {
        $fin = intval($arr[1]) * 3600 + intval($arr[2]) * 60;
    }
    $header .= "  trancheHoraire[jour][" . $res->id . "] = new Array();";
    $header .= "  trancheHoraire[jour][" . $res->id . "]['debut'] = " . $debut . ";";
    $header .= "  trancheHoraire[jour][" . $res->id . "]['fin'] = " . $fin . ";";
    $header .= "  trancheHoraire[jour][" . $res->id . "]['facteur'] = " . $res->facteur . ";";
}
$header .= "</script>";



llxHeader($header, "GLE-Gantt");
$head = synopsis_project_prepare_head($projet);
dol_fiche_head($head, 'Gantt', $langs->trans("Project"));


print '<div style="position:relative" class="gantt" id="GanttChartDIV"></div>';
print "<br/><center >";
//print '<button id="ajouter" class="ui-widget-header ui-corner-all ui-state-default" style="padding: 3px 5px;">Ajouter une t&acirc;che</button>';
print "</center>";

//print $header;

$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
             WHERE fk_projet = " . $project_id;
$sql = $db->query($requete);
$optDependStr = "";
$optGrpStr = "";
while ($res = $db->fetch_object($sql)) {
    $optDependStr .= "<option value='" . $res->rowid . "'>" . $res->title . "</option>";
    //si c'est un group
    if ($res->fk_task_type == 3) {
        $optGrpStr .= "<option value='" . $res->rowid . "'>" . $res->title . "</option>";
    }
}
print "<script>";
print 'var optDependStr = "' . $optDependStr . '";';
print "</script>";

$requete = "SELECT *
              FROM " . MAIN_DB_PREFIX . "user
             WHERE statut = 1
          ORDER BY firstname, lastname";
$sql = $db->query($requete);
$optUsrStr = "";
while ($res = $db->fetch_object($sql)) {
    $optUsrStr .= "<option value='" . $res->rowid . "'>" . $res->firstname . " " . $res->lastname . "</option>";
}
print "<script>";
print 'var optUsrStr = "<option value=\'-1\'>S&eacute;lectionn&eacute;-></option>' . $optUsrStr . '";';
print "</script>";


foreach (array("0" => array("id" => 'dialog', "legend" => 'Modifier une t&acirc;che', "mode" => "Mod"),
 "1" => array("id" => 'ajouterPanel', "legend" => 'Ajouter une t&acirc;che', "mode" => "add")
)
as $key => $val) {
    print '<div style="display:none;" id="' . $val["id"] . '" title="' . $val['legend'] . '" style="background-color:#FFFFFF;   width: 870px; border: 1px Solid #CCCCCC;">';
    print ' <div><form onSubmit="return false" id="' . $val['mode'] . 'Form">';
    print '    <fieldset style="padding :10px; margin: 10px;">';
    print '        <legend>' . $val['legend'] . '</legend>';
    print displayHTMLTable_tpl($val['mode'], $optDependStr, $optGrpStr, $optUsrStr, $db);
    print '    </fieldset>';
    print ' </form></div>';
    print '</div>';
}

print "<div id=debug></div>";
print '<ul id="myMenu" class="contextMenu">
    <li class="FI">
        <a href="#FI">Nouvelle fiche interv.</a>
    </li>
    <li class="DI">
        <a href="#DI">Nouvelle demande interv.</a>
    </li>

    <li class="edit">
        <a href="#edit">Editer</a>
    </li>
    <li class="delete">
        <a href="#delete">Supprimer</a>
    </li>
</ul>';

print "<div id='deldialog' style='display:none'>";
print "<p>" . $langs->trans('ConfirmDelTask');
print '</div>';

print "</body>";
print "</html>";

function displayHTMLTable_tpl($mode, $optDependStr, $optGrpStr, $optUsrStr, $db) { //keep source space, display form
    global $conf;
    $htmlTable = "";
    //accordion
    $htmlTable .= '<div id="accordion' . $mode . '">';
//part1

    $htmlTable.="<ul>";
    $htmlTable.='   <li><a href="#fragment' . $mode . '-1"><span>T&acirc;che</span></a></li>';
    $htmlTable.='   <li><a href="#fragment' . $mode . '-2"><span>RH associ&eacute;es</span></a></li>';
    $htmlTable.='   <li><a href="#fragment' . $mode . '-3"><span>D&eacute;pendance</span></a></li>';
    $htmlTable.='</ul>';
    $htmlTable .= '    <div id="fragment' . $mode . '-1">';

    $htmlTable .= '<table class="' . $mode . 'table" border=0 cellspacing=10 style="padding: 5pt; border-collapse : separate; ">
                <tbody>
                    <tr>
                        <td style="min-width: 120px;">
                            <label for="' . $mode . 'name"><em>*</em>&nbsp;&nbsp;Nom de la t&acirc;che</label>
                        </td>
                        <td>
                            <input type="text" id="' . $mode . 'name" name="' . $mode . 'name">
                        </td>
                        <td rowspan=6>
                            <label style="padding-left: 20pt;" for="color">Couleur</label>
                        </td>
                        <td  rowspan=6 style="vertical-align: center; text-align: center;">';
    if ($mode == "add") {
        $htmlTable .= '

                            <input  id="color" name="color"  type="hidden" >
                            <div style="margin: auto;" id="colorpicker"></div>';
    } else {
        $htmlTable .= '

                            <input  id="color1" name="color1"  type="hidden" >
                            <div style="margin: auto;" id="colorpicker1"></div>';
    }
    $htmlTable .= '
                        </td>

                    </tr>
                    <tr>
                        <td>';
    if ($mode == "add") {
        $htmlTable .= '

                            <label for="adddatedeb"><em>*</em>&nbsp;&nbsp;Date de d&eacute;but</label>
                        </td>
                        <td>
                            <input type="text" class="AdatePick" style="width: 177px;" id="adddatedeb" name="adddatedeb">
';
    } else {
        $htmlTable .= '
                            <label for="Moddatedeb"><em>*</em>&nbsp;&nbsp;Date de d&eacute;but</label>
                        </td>
                        <td>
                            <input type="text" class="UdatePick" style="width: 177px;"  id="Moddatedeb"  name="Moddatedeb">
';
    }
    if ($mode == "add") {
        $htmlTable .= '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="adddatefin"><em>*</em>&nbsp;&nbsp;Date de fin</label>
                        </td>
                        <td>
                            <input type="text" class="AdatePickEnd" style="width: 177px;" id="adddatefin" name="adddatefin">
';
    } else {
        $htmlTable .= '
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="Moddatefin"><em>*</em>&nbsp;&nbsp;Date de fin</label>
                        </td>
                        <td>
                            <input type="text" class="UdatePickEnd" style="width: 177px;"  id="Moddatefin" name="Moddatefin">
';
    }

    $htmlTable .= '
                    <tr>
                        <td>
                            <label for="' . $mode . 'parent"><em>*</em>&nbsp;&nbsp;Parent</label>
                        </td>
                        <td>
                            <SELECT id="' . $mode . 'parent"  name="' . $mode . 'parent">
                                <option value="-2">S&eacute;lection-></option>
                                <option value="-1">Racine du projet</option>';
    $htmlTable .= $optGrpStr;
    $htmlTable .= '
                            </SELECT>
                        </td>
                    </tr>
                    <tr>
                        <td rowspan=1>
                            <label style="padding-left: 20pt;" for="' . $mode . 'Desc">Description</label>
                        </td>
                        <td rowspan=1 >
                            <textarea type="text" id="' . $mode . 'Desc"></textarea>
                        </td>
<td colspan=2>
                    </tr>

                    <tr>
                        <td>
                            <label for="' . $mode . 'type"><em>*</em>&nbsp;&nbsp;Type</label>
                        </td>
                        <td>
                            <SELECT id="' . $mode . 'type"  name="' . $mode . 'type">
                                <option value="1">Etape</option>
                                <option value="2">T&acirc;che</option>
                                <option value="3">Groupe</option>
                            </SELECT>
                        </td>
                    </tr>

                   <tr>
                        <td>
                            <label for="' . $mode . 'complet">% Compl&eacute;tion</label>
                        </td>
                        <td>
                            <input type="text" id="' . $mode . 'complet">
                        </td>
                   </tr>
                   <tr>
                        <td>
                            <label for="' . $mode . 'shortDesc">Description courte</label>
                        </td>
                        <td>
                            <input type="text" id="' . $mode . 'shortDesc">
                        </td>
                        <td>
                            <label for="' . $mode . 'Url">Url de la t&acirc;che</label>
                        </td>
                        <td>
                            <input type="text" id="' . $mode . 'Url">
                        </td>

                   </tr>
                </tbody>
            </table>';

    $htmlTable .= '    </div>';

    $htmlTable .= '    <div id="fragment' . $mode . '-2">';

    $htmlTable .= <<<EOF


<table width=700>
    <tbody>
        <tr>
            <td width=300 valign=top>
EOF;
    $htmlTable .= "<div id='SubAccordion" . $mode . "' class='ui-accordion ui-widget ui-helper-reset'>";
    $htmlTable .= <<<EOF
                    <h3><a href="#">&Eacute;quipe de travail</a></h3>
                    <div style="max-width: 235px; min-width: 190px; max-height: 390px;  height: 270px; min-height: 270px;">
EOF;
    $htmlTable .= "<div id='tree" . $mode . "'>";
    $htmlTable .= <<<EOF
                          <ul class="treeview">
EOF;
    if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
        $hrm = new Hrm($db);
        $html = $hrm->getOrgTree();
        $htmlTable .= $html;
    }
    $htmlTable .= <<<EOF
                            </ul>
                        </div>
                    </div>
                    <h3><a href="#">Personne</a></h3>
                    <div style="max-width: 235px; min-width: 190px; max-height: 390px;   height: 270px; min-height: 270px;">
                        <table width=100%>
                            <tbody>
                                <tr>
                                    <td>
EOF;
    $htmlTable .= '                            <SELECT id="SelUser' . $mode . '" style="max-width: 200px;width: 200px;" disabled=false>';
    $htmlTable .= '                              <OPTION SELECTED value="-1">S&eacute;lectionn&eacute;-></OPTION>';
    $htmlTable .= $optUsrStr;
    $htmlTable .= <<<EOF
                                        </SELECT>
                                    </td>
EOF;
    $htmlTable .= "                       <td width=30px><button id='SelUserBut" . $mode . "'>&gt;&gt;</button></td>";
    $htmlTable .= <<<EOF
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
            <td valign=top>
                <table  width='100%'>
                    <tbody>
                        <tr>
EOF;
    $htmlTable .= "             <td valign=top><div id='toChange" . $mode . "'>NewForm</div></td>";
    $htmlTable .= <<<EOF
                        </tr>
                    </tbody>
                </table>
            </td>
            <td width=250 align=right valign=top>
EOF;
    $htmlTable .= " <div id='AddToTable" . $mode . "'><img height=16 width=16 src='" . DOL_URL_ROOT . "/theme/common/treemenu/plus.gif',1) ?>'></div>";
    $htmlTable .= <<<EOF
            </td>
        </tr>
    </tbody>
</table>

<table width=100% style='border-collapse: collapse;'>
<thead><tr><th class='ui-state-default ui-th-column'>Type</th>
           <th class='ui-state-default ui-th-column'>Nom</th>
           <th class='ui-state-default ui-th-column'>Occupation</th>
           <th class='ui-state-default ui-th-column'>R&ocirc;le</th>
           <th class='ui-state-default ui-th-column'>Action</th>
        </tr></thead>
EOF;
    $htmlTable .= "<tbody id='result" . $mode . "'>";
    $htmlTable .= <<<EOF
</tbody>
</table>
EOF;


    $htmlTable .= '    </div>';

    $htmlTable .= '    <div id="fragment' . $mode . '-3">';

    $htmlTable .= '       <table class="' . $mode . 'table" border=0 cellspacing=5 style="padding: 5pt; ">';
    $htmlTable .= <<<EOF
                    <tbody>
                     <tr>
                        <td>
EOF;
    $htmlTable .= '            <label  for="' . $mode . 'depend">D&eacute;pendance</label>';
    $htmlTable .= <<<EOF
                        </td>
                        <td>
EOF;
    $htmlTable.= '               <SELECT size=1 id="' . $mode . 'depend">';
    $htmlTable.= <<<EOF
                                <option value="-1">S&eacute;lection-></option>
EOF;
    $htmlTable.= $optDependStr;
    $htmlTable.= <<<EOF
                            </SELECT>
                        </td>
                        <td>
                            <div class="dependChange"></div>
                        </td>

                   </tr>
EOF;
    $htmlTable .= <<<EOF


<table width=100% style='border-collapse: collapse;'>
<thead><tr><th width=10% class='ui-state-default ui-th-column'>Id</th>
           <th class='ui-state-default ui-th-column'>Nom</th>
           <th class='ui-state-default ui-th-column'>% accomp.</th>
           <th class='ui-state-default ui-th-column'>Action</th>
        </tr></thead>
EOF;
    $htmlTable .= "<tbody id='Depresult" . $mode . "'>";
    $htmlTable .= <<<EOF
</tbody>
</table>

</tbody>
</table>
EOF;

    $htmlTable .= '    </div>';
    $htmlTable .= '    </div>';

    return($htmlTable);
}

llxFooter("<em>Derni&egrave;re modification $Date: 2008/06/18 20:01:02 $ r&eacute;vision $Revision: 1.41 $</em>");
?>