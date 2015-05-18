<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 23 aout 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : riskDetail.php
  * GLE-1.1
  */

  //TODO :> impact dépendance sur coût du risque
  //TODO :> cout tache pas OK
  //TODO :> supprimer

require_once('../../main.inc.php');

require_once(DOL_DOCUMENT_ROOT.'/synopsisprojet/class/synopsisproject.class.php');

$taskId = $_REQUEST['taskId'];

$task = new SynopsisProjectTask($db);
$task->fetch($taskId);
$projet = new SynopsisProject($db);
$projet->fetch($task->fk_projet);

$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";
$header .= '<link type="text/css" rel="stylesheet" href="'.DOL_URL_ROOT.'/Synopsis_Common/css/jquery.treeview.css" />';

$header .= '<script language="javascript" src="'.$jqueryuipath.'jquery-ui.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.slider.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

$error="";

print $header;

$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
             WHERE fk_task = ".$taskId;
$sql = $db->query($requete);
$countRisk=0;
$value1=0;
$value2=0;
$desc="";
if ($sql)
{
    $res = $db->fetch_object($sql);
    if ($res->occurence > 0)
    {
        $value1 = $res->occurence;
    }
    if ($res->gravite > 0)
    {
        $value2 = $res->gravite;
    }
    $countRisk = $db->num_rows($sql);
    $desc=$res->description;
    if ('x'.$desc =='x')
    {
        $desc="<i>Cliquer ici pour ajouter une description</i>";
    }
}

print '<div id="loadRisk" style="float: left; padding-left: 10pt;width: 800px; ">';
print '<form id="FormRisk" action="risque.php?id='.$task->fk_projet.'&selectedTabs=2&selectedTask='.$taskId.'" method="POST">';

print '<div style="border:1px solid #CCCCCC; height: 50px; font-size: 16px; padding-top: 5px; padding-left: 5px;">';
print "".$task->title;
print "</div>";
print "<input type='hidden'  name='nom' value='".$task->title."'></input>";

print "<div style='max-height: 400px; overflow-y:auto;'>";
print "<table width=100%><tr><td style='min-width:400px;width:40%;vertical-align:top;'>";
print "<div id='desc' style='background-color: #CCCCCC; min-height:350px;width:90%;cursor: pointer; padding: 5px;' onClick='editInplace(this);' >".$desc."</div>";
print "<input type='hidden' id='descStr' name='desc' value='".$desc."'></input>";
print "</td>";
print "<td style='min-width:60%;vertical-align:top;'>";
//table dependance
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_depends,
                   ".MAIN_DB_PREFIX."Synopsis_projet_task
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task_depends.fk_depends = ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid
               AND fk_task = ".$taskId;
$sql = $db->query($requete);
if ($db->num_rows($sql) > 0)
{
    print "<table width=100%><tr>";
    print "<td colspan='2'>Impact sur les d&eacute;pendances<td></tr><tr><td colspan=2 style='vertical-align:top;'><table style='border:1px Solid; background-color: #DDDDDD; width: 100%'>";
    print "</tr>";
    while ($res = $db->fetch_object($sql))
    {
        print '<tr><td>';
        print $res->title."</td><td><input type='text' name='impactDep-".$res->rowid."' id='impactDep-".$res->rowid."'></input>";
        print "</td></tr>";
    }
    print "</table>";
}
print "</td>";
print "</tr>";
print "</table>";
print "</table>";
print "</div>";
print "<br/>";

//calcul du cout de la tache
////coutTache = cout humain + materiel + frais de proet lié a la tache
////frais de projet
//$coutTache=0;
//$requete = "SELECT SUM(montantHT) as fraisProj FROM ".MAIN_DB_PREFIX."Synopsis_projet_frais WHERE fk_task = ".$taskId;
//$sql = $db->query($requete);
//$res = $db->fetch_object($sql);
//if ($res->fraisProj>0)
//    $coutTache += $res->fraisProj;
////ressource mat
//$requete = "SELECT unix_timestamp(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datedeb) as datedebF,
//                   unix_timestamp(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.datefin) as datefinF,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources.cout,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_user_imputation,
//                   ifnull(".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_projet_task,-1) as taskId,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources.nom,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources.fk_resa_type,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources.id
//              FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa,
//                   ".MAIN_DB_PREFIX."Synopsis_global_ressources
//             WHERE ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_ressource = ".MAIN_DB_PREFIX."Synopsis_global_ressources.id
//               AND fk_projet_task = ".$taskId;
//$sql = $db->query($requete);
//while ($res = $db->fetch_object($sql))
//{
//    $count=0;
//    $cout=0;
//    for ($j = $res->datedebF; $j <= $res->datefinF ; $j += 3600 * 24)
//    {
//        if (date('N', $j) < 6) //pas un week end
//        {
//            //ajouter si pas un jour ferie
//            $pasFerie = true;
//            foreach($arrFerie as $key)
//            {
//                //print $key . " " . $j ."\n";
//                if ($key >=$j   && $key <= $j + 24 * 3600)
//                {
//                    $pasFerie=false;
//                    break;
//                }
//            }
//            if ($pasFerie)
//            {
//                $nextDate=0;
//                if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/",date("d/m/Y",$j),$arr))
//                {
//                    $nextDate = mktime (0, 0, 0, $arr[2],$arr[1], $arr[3]) + 24 * 3600;
//                }
//                if ($nextDate > $res->datefinF) $nextDate = $res->datefinF;
//                $durationPerDay = $nextDate - $j;
//     //                            print $nextDate."\n".$res1->datefinF."\n".$durationPerDay / 3600 ."\n";
//                //Prob timezone
//                if ($durationPerDay > $conf->global->ALLDAY * 3600)
//                {
//                    switch ($res->fk_resa_type)
//                    {
//                        case 1:
//                            $count +=$conf->global->ALLDAY * 3600;
//                            $cout += $res->cout * $conf->global->ALLDAY ; // le cout est un cout horaire
//                        break;
//                        case 2:
//                            $count += $conf->global->HALFDAY * 2 * 3600; // le cout est un cout par 1/2j
//                            $cout += $res->cout * 2;
//                        break;
//                        case 3:
//                        default:
//                            $count += $conf->global->ALLDAY * 3600; // le cout est un cout parj
//                            $cout += $res->cout;
//                        break;
//                    }
//                } else {
//                    switch ($res->fk_resa_type)
//                    {
//                        case 1:
//                            $count += $durationPerDay;
//                            $cout += $res->cout * $durationPerDay  / 3600;
//                        break;
//                        case 2:
//                            $count += $conf->global->HALFDAY * 2 * 3600;
//                            $cout += $res->cout * 2;
//                        break;
//                        case 3:
//                        default:
//                            $count += $conf->global->ALLDAY * 3600;
//                            $cout += $res->cout;
//                        break;
//                    }
//                }
//            }
//        }
//    }
//
//    //calcul du cout de la ressource
//
//    $coutTache += $cout;
//
//}
////ressource RH
//
//$hrm=new hrm($db);
//
//$hrm->listRessources();
//foreach($hrm->allRessource as $key=>$val)
//{
//    if ("x".$val['GLEId'] != "x")
//    {
//        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user,
//                           ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid as tid,
//                           ".MAIN_DB_PREFIX."Synopsis_projet_task.title as title
//                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
//                           ".MAIN_DB_PREFIX."Synopsis_projet_task
//                     WHERE ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
//                       AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user = ".$val['GLEId']."
//                       AND ".MAIN_DB_PREFIX."Synopsis_projet_task.fk_projet = ".$project_id;
//        $sql=$db->query($requete);
//        while ($res=$db->fetch_object($sql))
//        {
//            if ($res->fk_user."x" != "x")
//            {
//                //liste la quantité de jour par tache
//                $requete = "SELECT sum(task_duration) as totDur
//                              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_time
//                             WHERE fk_task = ".$res->tid. "
//                               AND fk_user=".$res->fk_user;
//                $sql1= $db->query($requete);
//                $res1 = $db->fetch_object($sql1);
//
//                $requete = "SELECT couthoraire
//                              FROM ".MAIN_DB_PREFIX."Synopsis_hrm_user
//                             WHERE user_id = $res->fk_user
//                          ORDER BY startDate DESC
//                             LIMIT 1";
//                $sql2 = $db->query($requete);
//                $coutHoraire="";
//                if ($sql2)
//                {
//                    $res2=$db->fetch_object($sql2);
//                    $coutHoraire=$res2->couthoraire;
//                }
//                $coutTache+=($res1->totDur / 3600) * $coutHoraire;
//            }
//        }
//    }
//}

$coutTache = $projet->costTask($taskId);
if ($countRisk>0)
{
    print '<input type="hidden" name="action" id="action" value="updtRisk"></input>';
} else {
    print '<input type="hidden" name="action" id="action" value="addRisk"></input>';
}
$costRisk = $coutTache * ($value1 + $value2) / 200;
print "<table width=100%>";
print " <tr>";
print "  <td>Co&ucirc;t estimatif de la t&acirc;che</td>";
print "  <td colspan=2><span>".price(round($coutTache*100)/100)." &euro;</span></td>";
print " </tr>";
print " <tr>";
print "  <td width='150px'>Probabilit&eacute; d'occurence</td>";
print "  <td width='20px'><span id='slide1Str'>".$value1."</span>%</td><td><input type='hidden' name='value1' id='slide1Val' value='".$value1."'></input><div id='slide1' class='slide'></div></td>";
print " </tr>";
print " <tr>";
print "  <td width='150px'>Importance</td>";
print "  <td width='20px'><span id='slide2Str'>".$value2."</span>%</td><td><input type='hidden' name='value2' id='slide2Val' value='".$value2."'></input><div id='slide2' class='slide'></div></td>";
print " </tr>";
print " <tr>";
print "  <td width='150px'>Co&ucirc;t du risque</td>";
print "  <td colspan=2><span id='riskCost'>".price(round($costRisk*100)/100)."</span> &euro;</td>";
print " </tr>";
print "<tr><td>&nbsp;</td></tr>";
print " <tr>";
print "  <td>&nbsp;</td>";
print "  <td style='text-align: left; padding-right: 0;'></td><td colspan=2 align=right><input onclick='document.getElementById(\"FormRisk\").submit();' type='button' class='butAction' style='margin-right: 0;' value='Sauvegarder'></input></td>";
print "</tr>";

print "</table>";
print "</form>";
print '</div>';
$descJS = $desc;
if($descJS == '"<i>Cliquer ici pour ajouter une description</i>"')
{
    $descJS="";
}

print <<<EOF
<script type='text/javascript'>
    $(".slide").slider({
         animate: true ,
         max: 100,
         min: 0,
         range: 'min',
         step: 5,
         change: function(event, ui)
         {
             var value = ui.value;
             var id = event.target.id;
             $('#'+id+'Str').text(value);
             $('#'+id+'Val').val(value);
             var slide1Val = parseInt($("#slide1Val").val());
             var slide2Val = parseInt($("#slide2Val").val());
             var coutRisk = coutTache * (slide1Val + slide2Val)/200;
             //format Price:
                 coutRisk = CurrencyFormatted(coutRisk)
             $('#riskCost').text(coutRisk);
         }
    });

function CurrencyFormatted(amount)
{
    var i = parseFloat(amount);
    if(isNaN(i)) { i = 0.00; }
    var minus = '';
    if(i < 0) { minus = '-'; }
    i = Math.abs(i);
    i = parseInt((i + .005) * 100);
    i = i / 100;
    s = new String(i);
    if(s.indexOf('.') < 0) { s += '.00'; }
    if(s.indexOf('.') == (s.length - 2)) { s += '0'; }
    s = minus + s;
    return s;
}
var eipText="$descJS";
var eipObj;
function editInplace(obj)
{
    eipObj=$("#desc").clone(true);
    $(obj).replaceWith('<textarea onBlur="editInPlaceRet(this)" style="min-height:350px;width:90%;">'+eipText+'</textarea>');
}
function editInPlaceRet(obj)
{
    eipText = $(obj).val();
    eipObj.text(eipText);
    $('#descStr').val(eipText);
    $(obj).replaceWith(eipObj);
}


EOF;
print "$('#slide1').slider('option', 'value', ".$value1.");";
print "$('#slide2').slider('option', 'value', ".$value2.");";
print "var coutTache = ".$coutTache.";";
print "</script>";

?>
