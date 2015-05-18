  <?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 6 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : Hcoutressource_html-response.php
  * GLE-1.1
  */

require_once('../../main.inc.php');
//require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
$project_id=$_REQUEST['projet_id'];


$project = new SynopsisProject($db);
$project->id = $project_id;
$project->costProjetRessource($conf);

$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_task WHERE fk_projet = ".$project_id;
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    $project->costTaskRessource($res->rowid);
}


print "<div style='width:743px; padding: 10px; margin: 10px; border: 1px Solid #68ACCF; -moz-border-radius: 8px;  -webkit-border-radius: 8px; max-height: 450px; '>";

print "<table  style='border-collapse:collapse; padding: 10px; padding-left: 20px; margin: 10px; width:730px; '>";
print "<tr><th colspan='3' style=' color: white; border: 1px Solid #68ACCF;' >R&eacute;capitulatif</th></tr>";
$totaLDur = 0;
$totaLCost = 0;
print "<tr style='border-top: 1px Solid #68ACCF; border-bottom: 1px Solid #68ACCF; border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF; font-weight:bold; font-family:Helvetica,Arial,sans-serif;font-size:1.1em; '>";
print "    <th align='left' style='border-right:1px Solid #68ACCF;border-left:1px Solid #68ACCF;'><span style='padding-left: 17px;'>Ressource</span></th>";
print "    <th align='right'  style='border-width:0 0 0 0;  padding-right: 17px;border-right:1px Solid #68ACCF;'>Dur&eacute;e totale</th>" ;
print "    <th align='right' style='border-width:0 0 0 0;  border-left:1px Solid #68ACCF;  border-right:1px Solid #68ACCF; padding-right: 17px;'>Co&ucirc;t total</th>" ;
print "</tr>";
$pairimpair = "pair";

foreach($project->CostByRessource as $key=>$val)
{
    if ($pairimpair == "pair") { $pairimpair = "impaire";} else
    if ($pairimpair == "impaire") { $pairimpair = "pair";}

    print "<tr class='".$pairimpair."' style='border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF;'>";
    print "    <td align='left'  style='border-right:1px Solid #68ACCF; width: 230px;padding-left: 16px;'>".utf8_encode($val['nom'])."</td>";
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px; border-right:1px Solid #68ACCF;'>". ($val['duration'] / 3600)." h</td>" ;
    $totaLDur += ($val['duration'] / 3600);
    print "    <td align='right' style='border-width:0 0 0 0; padding-right: 17px;'>". price($val['totalCostForRessources'])." &euro;</td>" ;
    $totaLCost += ($val['totalCostForRessources']);
    print "</td></tr>";
}

    print "<tr style='border-top: 1px Solid #68ACCF;border-bottom: 1px Solid #68ACCF; border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF; font-weight:bold; font-family:Helvetica,Arial,sans-serif;font-size:1.1em; '>";
    print "    <td align='left' style='border-right:1px Solid #68ACCF; width: 230px;'><span style='padding-left: 17px;'>Total</span></td>";
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px;border-right:1px Solid #68ACCF;'>".$totaLDur." h</td>" ;
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px;'>". price($totaLCost) ." &euro;</td>" ;
    print "</td></tr>";


print "</table></div><br/><br/>";

//table par tache
print "<div style=' height: 450px;'>";
print "<div style='width:350px; float: left; padding: 10px; margin: 10px; border: 1px Solid #68ACCF; -moz-border-radius: 8px;  -webkit-border-radius: 8px; max-height: 350px; '>";
print "<div style='width:348px; border: 1px Solid #68ACCF; height: 16px; font-size: 16px; padding-top: 3px;padding-bottom: 3px; background-color: #DDDDDD;'><center>Co&ucirc;t par t&acirc;che</center></div>";
print "<div style='max-height:300px;overflow-y: scroll; overflow-x:none; margin:0; padding: 0; width:350px;'><table style='width:335px; border-collapse:collapse; border: 1px Solid #68ACCF;'>";
$idx=0;
$pairimpair = "pair";
foreach($project->CostRessourceByTask as $taskId => $valArr)
{
    $idx=0;
    $soustot = 0;
    $soustotDur = 0;
    $iter = 0;
    foreach($valArr as $userId => $DescArr)
    {
        if ($idx==0)
        {
            $idx=1;
            print "<tr><th colspan='3' style='border: 1px Solid #68ACCF;'>".utf8_encode($DescArr['title']);
        }
        if ($pairimpair == "pair") { $pairimpair = "impaire";} else
        if ($pairimpair == "impaire") { $pairimpair = "pair";}
        $fuser = new User($db);
        $fuser->id = $userId;
        $fuser->fetch($fuser->id);
        print "<tr class='".$pairimpair."'>";
        print "<td>".utf8_encode($fuser->getNomUrl(1));
        print "</td>";
        print "<td align='right'>".$DescArr['duration'] / 3600 . "h";
        print "</td>";
        $soustot += $DescArr['totalCostForRessources'];
        $soustotDur += $DescArr['duration'];
        print "<td align='right'>".price($DescArr['totalCostForRessources'],0) . "&euro;";
        print "</td>";
        print "</tr>";
        $iter++;
    }
    if ($iter > 1)
    {
        print "<tr class='".$pairimpair."'>";
        print "<td  style='border-top: 3px #333333; border-top-style: double; '><i>Sous-total</i>";
        print "</td>";
        print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".$soustotDur / 3600 . "h</i>";
        print "</td>";
        print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".price($soustot,0) . "&euro;</i>";
        print "</td>";
        print "</tr>";
    }
}
print "</table>";
print "</div>";
print "</div>";
//table par user

print "<div style='width:350px; float: left;  padding: 10px; margin: 10px; border: 1px Solid #68ACCF; -moz-border-radius: 8px;  -webkit-border-radius: 8px; max-height: 350px;'>";
print "<div style='max-height:200px; width:348px; border: 1px Solid #68ACCF; height: 16px; font-size: 16px; padding-top: 3px;padding-bottom: 3px; background-color: #DDDDDD;'><center>Co&ucirc;t par acteur</center></div>";
print "<div style='max-height:300px;overflow-y: scroll; overflow-x:none; margin:0; padding: 0; width:350px;'><table style='width:335px; border-collapse:collapse; border: 1px Solid #68ACCF;'>";
$idx=0;
$pairimpair = "pair";


foreach($project->CostRessourceByUser as $userId=>$valArr)
{
    $fuser = new User($db);
    $fuser->id = $userId;
    $fuser->fetch($fuser->id);
    $idx=0;
    $soustot = 0;
    $soustotDur = 0;
    $iter = 0;
    foreach($valArr as $taskId => $DescArr)
    {
        if ($idx==0)
        {
            $idx=1;
            print "<tr><th colspan='3' style='border: 1px Solid #68ACCF;'>".utf8_encode($fuser->getNomUrl(1));
        }
        if ($pairimpair == "pair") { $pairimpair = "impaire";} else
        if ($pairimpair == "impaire") { $pairimpair = "pair";}
        print "<tr class='".$pairimpair."'>";
        print "<td>".utf8_encode($DescArr['title']);
        print "</td>";
        print "<td align='right'>".$DescArr['duration'] / 3600 . "h";
        print "</td>";
        print "<td align='right'>".price($DescArr['totalCostForRessources']) . "&euro;";
        print "</td>";
        print "</tr>";
        $soustot += $DescArr['totalCostForRessources'];
        $soustotDur += $DescArr['duration'];
        $iter++;
    }
    if ($iter > 1)
    {
        print "<tr class='".$pairimpair."'>";
        print "<td  style='border-top: 3px #333333; border-top-style: double; '><i>Sous-total</i>";
        print "</td>";
        print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".$soustotDur / 3600 . "h</i>";
        print "</td>";
        print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".price($soustot,0) . "&euro;</i>";
        print "</td>";
        print "</tr>";
    }
}
print "</table>";
print "</div></div></div>";



?>