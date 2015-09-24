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
$project_id=$_REQUEST['projet_id'];
require_once('../../main.inc.php');
//require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

//liste les ressources, les taches , le cout à la tache et le cout total

//recupere la liste des gars

//$hrm=new hrm($db);

require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$project = new Project($db);
$project->id = $project_id;
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_projet = ".$project_id;
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    $project->costTaskRH($res->rowid);
}

//require_once('Var_Dump.php');
print "<div style='width:743px; padding: 10px; margin: 10px; border: 1px Solid #68ACCF; -moz-border-radius: 8px;  -webkit-border-radius: 8px; max-height: 450px; '>";

print "<table  style='border-collapse:collapse; padding: 10px; padding-left: 20px; margin: 10px; width:730px; '>";
print "<tr><th colspan='3' style=' color: white; border: 1px Solid #68ACCF;' >R&eacute;capitulatif</th></tr>";
$totaLDur = 0;
$totaLCost = 0;
print "<tr style='border-top: 1px Solid #68ACCF; border-bottom: 1px Solid #68ACCF; border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF; font-weight:bold; font-family:Helvetica,Arial,sans-serif;font-size:1.1em; '>";
print "    <th align='left' style='border-right:1px Solid #68ACCF;border-left:1px Solid #68ACCF;'><span style='padding-left: 17px;'>Acteur</span></th>";
print "    <th align='right'  style='border-width:0 0 0 0;  padding-right: 17px;border-right:1px Solid #68ACCF;'>Dur&eacute;e totale</th>" ;
print "    <th align='right' style='border-width:0 0 0 0;  border-left:1px Solid #68ACCF;  border-right:1px Solid #68ACCF; padding-right: 17px;'>Co&ucirc;t total</th>" ;
print "</tr>";
$pairimpair = "pair";

foreach($project->CostRHByUser as $key=>$val)
{
    if ($pairimpair == "pair") { $pairimpair = "impaire";} else
    if ($pairimpair == "impaire") { $pairimpair = "pair";}

    $fuser = new User($db);
    $fuser->id = $key;
    $fuser->fetch($fuser->id);
    print "<tr class='".$pairimpair."' style='border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF;'>";
    print "    <td align='left'  style='border-right:1px Solid #68ACCF; width: 230px;'>".utf8_encode($fuser->getNomUrl(1))."</td>";
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px; border-right:1px Solid #68ACCF;'>". round($val['totalDur'] / 3600)." h</td>" ;
    $totaLDur += ($val['totalDur'] / 3600);
    print "    <td align='right' style='border-width:0 0 0 0; padding-right: 17px;'>". price(round($val['totalCostForUser']*100/100),0)." &euro;</td>" ;
    $totaLCost += ($val['totalCostForUser']);
    print "</td></tr>";
}

    print "<tr style='border-top: 1px Solid #68ACCF;border-bottom: 1px Solid #68ACCF; border-left: 1px Solid #68ACCF;border-right: 1px Solid #68ACCF; font-weight:bold; font-family:Helvetica,Arial,sans-serif;font-size:1.1em; '>";
    print "    <td align='left' style='border-right:1px Solid #68ACCF; width: 230px;'><span style='padding-left: 17px;'>Total</span></td>";
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px;border-right:1px Solid #68ACCF;'>".round($totaLDur)." h</td>" ;
    print "    <td align='right' style='border-width:0 0 0 0;  padding-right: 17px;'>". price(round($totaLCost*100)/100,0) ." &euro;</td>" ;
    print "</td></tr>";


print "</table></div><br/><br/>";

//table par tache
print "<div style=' height: 450px;'>";
print "<div style='width:350px; float: left; padding: 10px; margin: 10px; border: 1px Solid #68ACCF; -moz-border-radius: 8px;  -webkit-border-radius: 8px; max-height: 350px; '>";
print "<div style='width:348px; border: 1px Solid #68ACCF; height: 16px; font-size: 16px; padding-top: 3px;padding-bottom: 3px; background-color: #DDDDDD;'><center>Co&ucirc;t par t&acirc;che</center></div>";
print "<div style='max-height:300px;overflow-y: scroll; overflow-x:none; margin:0; padding: 0; width:350px;'><table style='width:335px; border-collapse:collapse; border: 1px Solid #68ACCF;'>";
$idx=0;
$pairimpair = "pair";
//
//        require_once('Var_Dump.php');
//        Var_Dump::Display($project->CostRHByTaskByUser);


foreach($project->CostRHByTaskByUser as $key=>$val)
{
    foreach($val as $taskId => $valArr)
    {
        $idx=0;
        $iter=0;
        $soustot=0;
        $soustotDur=0;
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
            print "<td align='right'>".round($DescArr['duration'] / 3600 ). "h";
            print "</td>";
            print "<td align='right'>".price(round($DescArr['taskCostForUser']*100)/100,0) . "&euro;";
            print "</td>";
            print "</tr>";
            $iter++;
            $soustot += $DescArr['taskCostForUser'];
            $soustotDur += $DescArr['duration'];

        }
        if ($iter > 1)
        {
            print "<tr class='".$pairimpair."'>";
            print "<td  style='border-top: 3px #333333; border-top-style: double; '><i>Sous-total</i>";
            print "</td>";
            print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".round($soustotDur / 3600) . "h</i>";
            print "</td>";
            print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".price(round($soustot*100)/100,0) . "&euro;</i>";
            print "</td>";
            print "</tr>";
        }

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
foreach($project->CostRHByUserByTask as $userId=>$val)
{
        $fuser = new User($db);
        $fuser->id = $userId;
        $fuser->fetch($fuser->id);
    foreach($val as $key => $valArr)
    {
        $idx=0;
        $soustotDur=0;
        $soustot=0;
        $iter=0;
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
            print "<td align='right'>".round($DescArr['duration'] / 3600) . "h";
            print "</td>";
            print "<td align='right'>".price(round($DescArr['taskCostForUser']*100)/100,0) . "&euro;";
            print "</td>";
            print "</tr>";
            $iter++;
            $soustot += $DescArr['taskCostForUser'];
            $soustotDur += $DescArr['duration'];
        }
        if ($iter > 1)
        {
            print "<tr class='".$pairimpair."'>";
            print "<td  style='border-top: 3px #333333; border-top-style: double; '><i>Sous-total</i>";
            print "</td>";
            print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".round($soustotDur / 3600) . "h</i>";
            print "</td>";
            print "<td align='right' style='border-top: 3px #333333; border-top-style: double; '><i>".price(round($soustot*100)/100,0) . "&euro;</i>";
            print "</td>";
            print "</tr>";
        }

    }
}
print "</table>";
print "</div></div></div>";



?>