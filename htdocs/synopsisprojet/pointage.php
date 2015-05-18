<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : pointage.php
  * GLE-1.1
  */

  //TODO : saisi facile
  //TODO : enregistrement des données
  //TODO : tooltip :> avec notice pour les bouton de la saisie facile

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");

  $projId = $_REQUEST['id'];
  $projet = new SynopsisProject($db);
  $projet->id = $projId;
  $projet->fetch($projet->id);

//Pour un projet, permettre le pointage des équipes par jours (present, absent, ... de tel heure à tel heure), cf NL et CMP


$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.tabs.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.slider.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.dialog.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.datepicker.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.timepicker.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.treeview.js"></script>'."\n";



$header .=  '<script language="javascript">'."\n";
$header .= '    var project_id = '.$projId.";"."\n";
$header .= '    var user_id = '.$user->id.";"."\n";
$header .= '    var DOL_URL_ROOT = "'.DOL_URL_ROOT.'";'."\n";
$sel=0;
if ($_REQUEST['selectedTabs'] > 0)
{
    $sel = $_REQUEST['selectedTabs'];
}
$header .= '  $(document).ready(function(){
    $("#tabs").tabs({
        cache: true,fx: { opacity: "toggle" }
        spinner:"Chargement ...",
        selected: '.$sel.'
    });
  });
';
$header .= '</script>'."\n";

llxHeader($header,"Projet - Pointage","",1);
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Pointage', $langs->trans("Project"));

//header
if ($_REQUEST["curdate"]>0)
{
    $curdate = $_REQUEST["curdate"];
} else {
    $curdate = date('U');
}
$prevDate =  intval($curdate) - 24 * 3600 ;
$nextDate =  intval($curdate) + 24 * 3600 ;

print "<a href='pointage.php?id=".$projId."&curdate=". $prevDate ."' ><span style='font-decoration:none; font-weight: 900; color: #FF3333; font-size: 16pt;'>&lt;&lt;&nbsp;</span></a><span style='font-size:16pt;'>".date('d/m/Y',$curdate)."</span><a href='pointage.php?id=".$projId."&curdate=". $nextDate ."' ><span  style='font-weight: 900; color: #3333FF; font-size: 16pt;' >&nbsp;&gt;&gt;</span></a>";

//nb de tranchehoraire
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire";
$sql=$db->query($requete);
$tranche=array();
$countTrancheHoraire =0;

while($res=$db->fetch_object($sql))
{
    $deb=$res->debut;
    if(preg_match('/([0-9]{2}):([0-9]{2})/',$deb,$arr))
    {
        $deb = intval($arr[1])*3600 + intval($arr[2] * 60);
    }
    $fin=$res->fin;
    if(preg_match('/([0-9]{2}):([0-9]{2})/',$fin,$arr))
    {
        $fin = intval($arr[1])*3600 + intval($arr[2] * 60);
    }
    $facteur=$res->facteur/100;
    $tranche[$deb]['fin']=$fin;
    $tranche[$deb]['facteur']=$facteur;
    //$tranche[$trancheId]['facteur']=$facteur;
    $countTrancheHoraire++;
}
$fistTranche = 3600 * 24;
$lastTranche = 0;
foreach($tranche as $key=>$val)
{
    if ($key < $fistTranche) $fistTranche = $key;
    if ($val['fin'] > $lastTranche) $lastTranche =  $val['fin'];
}


//1tabs par equipe
$hrm = new hrm($db);
$hrm->listTeam(); //TODO date doit être embauché et doit être dans la team à curdate
print '<div id="tabs" style="min-height: 650px;">';
print '     <ul>';

$iter=0;
//TODO filtrer les groupe lié au projet
foreach($hrm->teamRessource as $teamid => $val) // pour chaque team
{
    $requete = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors
                 WHERE fk_projet = ".$projId." AND type='group' AND fk_user = ".$teamid;
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        print '<li><a href="#fragment-'.$iter.'"><span>'.preg_replace('/[\w;&]*$/',"",$val['name']).'</span></a></li>';
        $iter++;
    }
}
print '     </ul>';

$iter=0;
$atleastone=false;
foreach($hrm->teamRessource as $teamid => $val) // pour chaque team
{
    $requete = "SELECT count(*) as cnt
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors
                 WHERE fk_projet = ".$projId." AND type='group' AND fk_user = ".$teamid;
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        $atleastone=true;
        print "<div  id='fragment-".$iter."' class='grid np'>";
        print "<table style='border-collapse:collapse;' width=100%>";
        $nbCol =  $countTrancheHoraire + 4;
        print "<tbody>";
        print "<tr><th>Nom</th>";
        print "<th align='center' width=75>Absent ?</th>";
        //print "<th align='center' width=150>De 00:00 &agrave; ".secToHeure($fistTranche)."</th>";

      //Si ferie :8
      //Si samedi : 6
      //Si dimanche : 7
      //Si non null

      $dayOfWeek = date('N',$curdate);
      $where = "day =".$dayOfWeek;
      if ($dayOfWeek <6)
      {
        $where = "day is null";
      }
      //si c'est ferie selon la hrm

      $arrFerie = $hrm->jourFerie();
      foreach($arrFerie as $key)
      {

          if (  date('d',$curdate) == date('d',$key)
             && date('m',$curdate) == date('m',$key)
             && date('Y',$curdate) == date('Y',$key) )
          {
                $where = " day = 8 ";
          }
      }

      $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE ".$where;
      $sql = $db->query($requete);
      $result=array();
      $firstTS=24*3600;
      $lastTS=0;
      $remArr = array();
      while($res = $db->fetch_object($sql))
      {
        $debut = $res->debut;
         $fin = $res->fin;
         $facteur = $res->facteur;
         $debutTS;
         $finTS;
         if(preg_match("/([0-9]{2}):([0-9]{2})/",$debut,$arr))
         {
            $debutTS = $arr[1] * 3600 + $arr[2] * 60;
         }
         if(preg_match("/([0-9]{2}):([0-9]{2})/",$fin,$arr))
         {
            $finTS = $arr[1] * 3600 + $arr[2] * 60;
         }
         $result[$debutTS]['facteur']=$res->facteur;
         $result[$debutTS]['duree']=$finTS - $debutTS;
         $result[$debutTS]['fin']=$finTS;
         $result[$debutTS]['rowid']=$res->id;
         if($firstTS>$debutTS)
         {
            $firstTS=$debutTS;
         }
         if($lastTS<$finTS)
         {
            $lastTS=$finTS;
         }
         array_push($remArr,$res->id);
      }
      print "<th>De 00:00 &agrave; ".secToHeure($firstTS)."</th>";

      for($i=$firstTS;$i<$lastTS;$i+=60)
      {
          if ($result[$i]['facteur']."x"!="x")
          {
              print '<th>De '.secToHeure($i).' &agrave; ';
              print '    '.secToHeure($i + $result[$i]['duree']).'</th>';
          }

      }
        print "<th align='center' width=150>De ".secToHeure($lastTS)." &agrave; 24:00</th>";
        print "<th align='center' >Total</th>";
        print "</tr>";
        $pairImpair[true]='pair';
        $pairImpair[false]='impair';
        $bool=true;
        foreach ($val['empInfo'] as $empid => $val1) //pour chaque user de la team
        {
            //ajouter filtre

            //Foreach($usr in team)
            // si vacances :> affiche vacances
            $tmpUser=new User($db);
            $tmpUser->fetch($val1['GLEId']);
            $bool = !$bool;
            print "<tr class='".$pairImpair[$bool]."'>";
            print "<td>".$tmpUser->getNomUrl(1)."</td>";
            print "<td align='center'><input name='AbsEmp-".$tmpUser->id."' id='AbsEmp-".$tmpUser->id."' type='checkbox'></input></td>";


            print "<td align='center'><input name='Emp-".$tmpUser->id."-tranche-0' type='text'></input></td>";
            foreach($remArr as $keyTranche)
            {
                print "<td align='center'><input name='Emp-".$tmpUser->id."-tranche-".$keyTranche."' type='text'></input></td>";
            }
            print "<td align='center'><input name='Emp-".$tmpUser->id."-tranche-".$lastTranche."' type='text'></input></td>";
            print "<td></td>";
            print "</tr>";

        }
        print "</tbody></table>";
        print "</div>";//fin du fragment
        $iter++;
    }
}
if (!$atleastone)
{
    print "Pas de groupe dans ce projet";
}
print '</div>';//fin de tabs

//mettre horaire début de journée en haut et heure de fin de journée

// si ferie :> affiche que c'est férié
// si we :> affiche que c'est samedi ou dimanche
//bouton reprendre derniere val

//fin foreach


//affiche un tableau
//1 ligne par user
//prevoir heure majoré en config
//1 col pour le nombre d'heure pour chaque projet
//Prévoir le fait qu'un user à peut être déjà pointé ???
//Prévoir, vacance, jour férié
//Prévoir absence
//Prévoir status et validation
//Prévoir si quipe ur plusiur projet


//1 lister toutes les teams et tous les users lié au projets Prob pas de groupe dans project 1
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                   ".MAIN_DB_PREFIX."Synopsis_projet_task
             WHERE fk_projet=".$projId . '
               AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task = ".MAIN_DB_PREFIX."Synopsis_projet_task.rowid';
//2 affiche les users par team
//3 affiche les case pour le pointage, 2 cases 1 horaires majoré é horaire standard
//4 possibilité de mettr la meme valeur dans toutes les cases




function secToHeure($ts)
{
    $hour = floor(intval($ts)/3600);
    $min = round(intval($ts) - $hour * 3600)/60;
    if ($hour<10)$hour = "0".$hour;
    if ($min<10)$min = "0".$min;
    $ret = $hour.":".$min;
    return($ret);
}

?>
