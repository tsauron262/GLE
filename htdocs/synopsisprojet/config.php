<?php
/*
  
  */
 /**
  *
  * Name : config.php
  * GLE-1.1
  */

  //tabs
  //onglet 1 => config ressource cost en lien avec hrm
  //onglet 2 => ???

require_once("./pre.inc.php");
$langs->load("synopsisproject@synopsisprojet");
if (!$user->rights->synopsisprojet->configure)
{
    accessforbidden();
}


if ("x".$_REQUEST['hourPerDay'] != "x")
{
//    require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "PROJECT_HOUR_PER_DAY",$_REQUEST["hourPerDay"]);
}

if ("x".$_REQUEST['DayStart'] != "x")
{
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "PROJECT_DAY_START",$_REQUEST["DayStart"]);
}
if ("x".$_REQUEST['DayEnd'] != "x")
{
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "PROJECT_DAY_END",$_REQUEST["DayEnd"]);
}


if ("x".$_REQUEST['DayPerWeek'] != "x")
{
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "PROJECT_DAY_PER_WEEK",$_REQUEST["DayPerWeek"]);
}


if ("x".$_REQUEST['allDay'] != "x")
{
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "ALLDAY",$_REQUEST["allDay"]);
}


if ("x".$_REQUEST['halfDay'] != "x")
{
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
    dolibarr_set_const($db, "HALFDAY",$_REQUEST["halfDay"]);
}

if ($_REQUEST['action'] == "updtEmailProj")
{
    if ( isset($_REQUEST['PJDoc']) )
    {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        dolibarr_set_const($db, "PROJECT_PJDOC_EMAIL",1);
    } else {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        dolibarr_set_const($db, "PROJECT_PJDOC_EMAIL","");
    }


    if ( isset($_REQUEST['linkDoc']) )
    {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        dolibarr_set_const($db, "PROJECT_LINKDOC_EMAIL",1);
    } else {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        dolibarr_set_const($db, "PROJECT_LINKDOC_EMAIL","");
    }
    if ("x".$_REQUEST['mailTemplate'] != "x")
    {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
        dolibarr_set_const($db, "PROJECT_TEMPLATE_EMAIL",($_REQUEST["mailTemplate"]));
    }
}



$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';




$sel=0;
if ($_REQUEST['selectedTabs'] > 0)
{
    $sel = $_REQUEST['selectedTabs'];
}

$header =  '<script language="javascript">'."\n";
$header .= '    var user_id = '.$user->id.";"."\n";
$header .= '    var DOL_URL_ROOT = "'.DOL_URL_ROOT.'";'."\n";
$header .= '  jQuery(document).ready(function(){
    jQuery("#tabs").tabs({
        remote:true,
        cache: true,
        spinner:"Chargement ...",fx: { opacity: "toggle" },
        selected: '.$sel.' });
  });
';
$header .= '</script>'."\n";


//Ressources matos only
// Charge la liste du materiel / salle / etc , permet de voir le calendrier de chaque ressource, et le responsable
// Possiblilité de voir le cot, reserver etc ...

llxHeader($header,"Projet - Configuration","",1);
//    $head=synopsis_project_prepare_head($projet);
//    dol_fiche_head($head, 'Ressources', $langs->trans("Project"));

//tabs
//1 :> affiche les ressrouces du projet
//2 :> ressource global + resa
//3 :> cout des resources


//tabs
print <<<EOF

<div id="tabs">
     <ul>
         <li><a href="ajax/configHressources_html-response.php"><span>Co&ucirc;t des ressources</span></a></li>
         <li><a href="ajax/configGroup_html-response.php"><span>Co&ucirc;t par groupe</span></a></li>
         <li><a href="ajax/configJour_html-response.php"><span>Journ&eacute;e de travail</span></a></li>
         <li><a href="ajax/configDoc_html-response.php"><span>Gestion documentaire</span></a></li>
     </ul>
</div>


EOF;


llxFooter('$Date: 2008/04/28 22:34:41 $ - $Revision: 1.11 $');

?>
