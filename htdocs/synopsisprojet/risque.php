<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 5 aou. 09
  *
  * Infos on http://www.finapro.fr
  *
  */

//Affiche tabs:
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
  $projId = $_REQUEST['id'];
  $projet = new SynopsisProject($db);
  $projet->id = $projId;
  $projet->fetch($projet->id);
  $project_id = $projId;

//tabs 0 : résumé : pondération par les coefficient + cout u risque lié au projet+ liste des tache et des risques (starratting ?) + cout associé à la tache
//tabs 2 : impacte de la tâche % à ces dépendances ( input type percent manuel ) % occurence et % gravite + cout de la tache
$error=false;
if ($_REQUEST['action'] =="addRisk")
{
    $taskId=$_REQUEST['selectedTask'];
    require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
    $task = new SynopsisProjectTask($db);
    $task->fetch($taskId);
    $nom = preg_replace('/\'/','\\\'',$_REQUEST['nom']);
    $desc = preg_replace('/\'/','\\\'',$_REQUEST['desc']);
    $occurence = $_REQUEST['value1'];
    $gravite = $_REQUEST['value2'];
    $fk_projet = $task->fk_projet;
    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_risk
                            (occurence,gravite,nom,description,fk_projet,fk_task)
                     VALUES (".$occurence.",".$gravite.",'".$nom."','".$desc."',".$fk_projet.",".$taskId.")";
    $sql = $db->query($requete);
    if (!$sql)
    $error = "Ajout impossible!<br/>".$db->lasterrno;

}
if ($_REQUEST['action'] =="updtRisk")
{
    $taskId=$_REQUEST['selectedTask'];
    require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
    $task = new SynopsisProjectTask($db);
    $task->fetch($taskId);
    $nom = preg_replace('/\'/','\\\'',$_REQUEST['nom']);
    $desc = preg_replace('/\'/','\\\'',$_REQUEST['desc']);
    $occurence = $_REQUEST['value1'];
    $gravite = $_REQUEST['value2'];
    $fk_projet = $task->fk_projet;
    $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_risk
                   SET occurence=".$occurence.",
                       gravite=".$gravite.",
                       nom='".$nom."',
                       description='".$desc."'
                 WHERE fk_task=".$taskId;
    $sql = $db->query($requete);
    if (!$sql)
    $error = "Mise &agrave; jour impossible!<br/>".$db->lasterrno."<br/>".$requete;

}
if ($project_id ."x"== "x")
{
    $project_id = -1;
}
$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<script language="javascript" src="'.$jspath.'jquery.treeview.js"></script>'."\n";

$header .= '<script language="javascript" src="'.$jspath.'jquery.tooltip.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n";
$header .= '<script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/jquery.ui.potato.menu.js"></script>'."\n";
$header .= '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.ui.potato.menu.css">'."\n";

$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
$header .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';
$header .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
$header .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';



$header .=  '<script language="javascript">'."\n";
$header .= '    var project_id = '.$project_id.";"."\n";
$header .= '    var user_id = '.$user->id.";"."\n";
$sel=0;
if ($_REQUEST['selectedTabs'] > 0)
{
    $sel = $_REQUEST['selectedTabs'];
}
$header .= '  $(document).ready(function(){
    $("#tabs").tabs({
        cache: false,fx: { opacity: "toggle" },
        spinner:"Chargement ...",
        selected: '.$sel.'
    });
  });
';
$header .= '</script>'."\n";

llxHeader($header,"Projet - Risque","",1);
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Risque', $langs->trans("Risque"));
if ($error)
{
    print "<div style='color: #CC0000; border:1px Solid #CC0000; text-align:center;'>".$error."</div><br/><br/>";
}

//tabs
$selectedTask = $_REQUEST['selectedTask'];
print <<<EOF

<div id="tabs" style="min-height: 650px;">
     <ul>
         <li><a href="ajax/riskSumup_html-response.php?project_id=$project_id"><span>R&eacute;sum&eacute;</span></a></li>
         <li><a href="ajax/riskDetail_html-response.php?project_id=$project_id"><span>D&eacute;tails</span></a></li>
         <li><a href="ajax/riskDetail-expert_html-response.php?project_id=$project_id&selectedTask=$selectedTask"><span>Mode expert</span></a></li>
     </ul>
</div>


EOF;

?>