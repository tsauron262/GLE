<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : imputation_html-repsonse.php
  * GLE-1.1
  */


  //TODO : tooltip pour l'affichage des résumés

require_once('../../main.inc.php');
  //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");
$project_id = $_REQUEST['project_id'];
// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//
//if (! ($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa))
//{
//    accessforbidden();
//}


$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.core.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.slide.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.tooltip.js"></script>'."\n";

$header .= "<style>";
$header .= <<<EOF
th {
    color: #FFFFFF;
}
    .promoteZBig{
        z-index: 1000000;
        position: fixed;
        background-color: #EFEFEF;
        padding-left: 10pt;
        padding-right: 10pt;
        padding-top: 2pt;
        padding-bottom: 2pt;
        -moz-border-radius: 8px;
        -webkit-border-radius: 8px;
        -moz-outline-radius: 8px;
        -webkit-outline: 0px;
        border: 1px solid #0073EA;
        -moz-outline: 3px solid #EFEFEF;
        min-width: 200px;
    }
    .title{
        display:none;
    }
EOF;
$header .= "</style>";


$header .= "<script>";
$header .=<<<EOF
$(".tooltip").tooltip({
    bodyHandler: function() {
                var html = "<b>"+ $(this).find('#title').html() +"</b>";
                    html += '<hr>'+$(this).find('#title').html();
                    $(html).css('display',"block");
                return $(html).html();
    },
    showURL: false,
    track: true,
    delay: 100,
    showBody: " - ",
    fade: 250,
    extraClass: "promoteZBig",
    left: 40,
    top: -30,
});
EOF;
$header .= "</script>";
print $header;


//calcul du risque global en euro:
$requete = "SELECT SUM(".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk,
                   SUM(".MAIN_DB_PREFIX."Synopsis_projet_risk.cout) as totCout
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE fk_risk_group is null
              AND  fk_projet = ".$project_id;
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$globalCostRisk = $res->coutRisk;
$globalCost = $res->totCout;

//TODO mode avance ajoute le prix et le cout des task idem ci dessous

$exportCostList = 0;
$requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_task, ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.id as gid,
                   occurence,
                   gravite
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group, ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group, ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.id =  ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group.fk_group_risk
               AND ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.id = ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_risk_group
               AND ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.fk_projet = ".$project_id;
$sql = $db->query($requete);

require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
$project = new SynopsisProject($db);
$project->id = $project_id;
$costRiskSpe = array();
while ($res=$db->fetch_object($sql))
{
    $taskCost = $project->costTask($res->fk_task);
    $risqueTask = $taskCost * ($res->occurence + $res->gravite) / 200;
    $exportCostList += $risqueTask;
    $globalCost += $taskCost;
    $costRiskSpe[$res->gid]+=$taskCost;
}

$totalCostRisque = $globalCostRisk + $exportCostList;

$percRisk = round(100 * $totalCostRisque/$globalCost);


print '<table width=100%>';
print ' <tbody style="padding:0">';
print '  <tr>';
print '   <td style="width:40%; vertical-align: top;">';
print '    <div style="padding:30pt;">';

print "     <table id='grad' width=240 style='vertical-align: middle; background-color: #68ACCF ;  border:1px Solid #333333; -moz-border-radius:8px;  -webkit-border-radius:8px;'>";

print "     <thead><tr><td align=center><span style='color: #FFFFFF;font-family: Helvetica,Arial,sans-serif;font-size: 13px; font-weight: bold; line-height:30px;'>Risque total</span></td></tr>";
print "     </thead>";
print "      <tbody style='background-color: #EEEEEE;'>";
print "       <tr>";
print "        <td align=center><span style='font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:bold;color:#FF0084;  line-height:45px;'>".price(round($totalCostRisque*100)/100)." &euro;</span>";
print "        </td>";
print "       </tr>";
print "      </tbody>";

print "     <thead style=''><tr><td align=center><span style='color:#FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:13px; font-weight: bold; line-height:30px; '> Risques Globaux </span></td></tr>";
print "     </thead>";
print "      <tbody style=' background-color: #DDDDDD;' id=''>";
print "       <tr>";
print "        <td align=center class='tooltip'><span style='font-family: Helvetica,Arial,sans-serif;font-size:13px;font-weight:bold;color:#FF0084; line-height:20px;'>".price(round($globalCostRisk*100)/100)." &euro;</i></span>";
print "        <div id='title' class='title' style=''>";
print "         <table width=100%  style='border-collapse: collapse;'>";
print "            <tr><th style='border: 1px Solid #000000; color: #FFFFFF;'>D&eacute;signation</th><th style='border: 1px Solid #000000; color: #FFFFFF;'>Risque</th></tr>";


//SELECT SUM(".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk,
//                   SUM(".MAIN_DB_PREFIX."Synopsis_projet_risk.cout) as totCout
//              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
//             WHERE fk_task is null
//              AND  fk_projet =

$requete = "SELECT (".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk, ".MAIN_DB_PREFIX."Synopsis_projet_risk.nom
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE fk_risk_group is null
               AND ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_projet = ".$project_id;
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    print "<tr><td style='border: 1px Solid #000000; '>".$res->nom."</td><td  align=right  style='border: 1px Solid #000000;'>".price(round($res->coutRisk*100)/100)." &euro;</td></tr>";
}


print "         </table>";
print "        </div>"; //fin du title
print "        </td>";
print "       </tr>";
print "      </tbody>";

print "     <thead style=''><tr><td align=center><span style='color:#FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:13px; font-weight: bold; line-height:30px; '> Risques sp&eacute;cifiques </span></td></tr>";
print "     </thead>";
print "      <tbody style=' background-color: #DDDDDD;'>";
print "       <tr>";
print "        <td align=center class='tooltip'><span style='font-family: Helvetica,Arial,sans-serif;font-size:13px;font-weight:bold;color:#FF0084; line-height:20px;'>".price(round($exportCostList*100)/100)." &euro;</i></span>";
print "        <div id='title' class='title' style=''>";
print "         <table  width=100% style='border-collapse: collapse;'>";
print "            <tr><th style='border: 1px Solid #000000; color: #FFFFFF;'>D&eacute;signation</th><th style='border: 1px Solid #000000; color: #FFFFFF;'>Risque</th></tr>";

foreach($costRiskSpe as $grpId => $costGrp)
{
    $requete = "SELECT group_name
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
                 WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk_group.fk_projet = ".$project_id."
                 WHERE id =".$grpId;
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        print "<tr><td style='border: 1px Solid #000000;'>".$res->group_name."</td><td align=right style='border: 1px Solid #000000;'>".price(round($costGrp*100)/100)." &euro;</td></tr>";
    }
}

print "         </table>";
print "        </div>"; //fin du title
print "        </td>";
print "       </tr>";
print "      </tbody>";


print "     <thead style=''><tr><td align=center><span style='color:#FFFFFF;font-family:Helvetica,Arial,sans-serif;font-size:13px; font-weight: bold; line-height:30px; '> Pourcentage des co&ucirc;ts identifi&eacute;s</span></td></tr>";
print "     </thead>";
print "      <tbody style=' background-color: #DDDDDD;'>";
print "       <tr>";
print "        <td align=center><span style='font-family: Helvetica,Arial,sans-serif;font-size:13px;font-weight:bold;color:#FF0084; line-height:20px;'>".$percRisk."% (<i>Total : ".price(round($globalCost*100)/100)." &euro;</i>)</span>";
print "        </td>";
print "       </tr>";
print "      </tbody>";
print "      <tfoot style='max-height:3px;height:3px;'><tr style='max-height:3px;height:3px;'><td></td></tr></tfoot>";
print "     </table>";
print '    </div>';
print '   </td>';
print '   <td style="width:60%;  vertical-align: top;">';

print '    <table width=100%>';
print '     <tbody style="padding:0">';
print '     <tr>';
print '      <td>';
print '       <table width=100% style="border-collapse:collapse;">';
print '        <tr>';
print '         <th colspan=6 style="line-height:30px;">Les facteurs de risque les plus importants';
print '         </th>';
print '        </tr>';
$requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_risk.rowid,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.gravite,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.nom,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.description,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.cout,
                   (".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_projet = ".$project_id."
          ORDER BY gravite, cout, nom
             LIMIT 5";
$sql = $db->query($requete);
print '        <tr>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Nom</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Importance</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Risque (&euro;)</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Occurence</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Description</td>';
print '        </tr>';

while ($res = $db->fetch_object($sql))
{
    print '        <tr>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->nom.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->gravite.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.price(round($res->coutRisk*100)/100).'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->occurence.'</td>';
    print '         <td nowrap class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->description.'</td>';
    print '        </tr>';
}

print '       </tbody>';
print '       </table>';
print '      </td>';
print '    </tr>';
print '    <tr>';
print '     <td>';
print '       <table width=100% style="border-collapse:collapse;">';
print '       <tbody style="padding:0">';
print '        <tr>';
print '         <th colspan=6 style="line-height:30px;">Les facteurs de risque financier';
print '         </th>';
print '        </tr>';
$requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_risk.rowid,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.gravite,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.nom,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.description,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.cout,
                   (".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_projet = ".$project_id."
          ORDER BY cout, gravite , nom
             LIMIT 5";
$sql = $db->query($requete);
print '        <tr>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Nom</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Importance</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Risque (&euro;)</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Occurence</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Description</td>';
print '        </tr>';

while ($res = $db->fetch_object($sql))
{
    print '        <tr>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->nom.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->gravite.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.price(round($res->coutRisk*100)/100).'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->occurence.'</td>';
    print '         <td nowrap class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->description.'</td>';
    print '        </tr>';
}
print '       </tbody>';
print '       </table>';

print '    <tr>';
print '     <td>';
print '       <table width=100% style="border-collapse:collapse;">';
print '       <tbody style="padding:0">';
print '        <tr>';
print '         <th colspan=6 class="grad" style="line-height:30px;">Les facteurs de risque les plus probables';
print '         </th>';
print '        </tr>';
$requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_risk.rowid,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.gravite,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.nom,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.description,
                   ".MAIN_DB_PREFIX."Synopsis_projet_risk.cout,
                   (".MAIN_DB_PREFIX."Synopsis_projet_risk.cout * (".MAIN_DB_PREFIX."Synopsis_projet_risk.occurence + gravite) / (100 * 2)) as coutRisk
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE  ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_projet = ".$project_id."
          ORDER BY occurence, cout, nom
             LIMIT 5";
$sql = $db->query($requete);
print '        <tr>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Nom</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Importance</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Risque (&euro;)</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Occurence</td>';
print '         <td class="pair" style="border:1px solid #CCCCCC;" align=center>Description</td>';
print '        </tr>';

while ($res = $db->fetch_object($sql))
{
    print '        <tr>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->nom.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->gravite.'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.price(round($res->coutRisk*100)/100).'</td>';
    print '         <td class="impair" style="border:1px solid #EEEEEE;" align="center">'.$res->occurence.'</td>';
    print '         <td nowrap class="impair" style="border:1px solid #EEEEEE;" align="left">'.$res->description.'</td>';
    print '        </tr>';
}
print '       </tbody>';
print '       </table>';
print '     </td>';
print '    </tr>';
print '   </table>';
print '   </tbody>';
print '  </td>';
print ' </tr>';
print '</tbody>';
print '</table>';

?>
