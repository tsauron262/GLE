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
  * Name : ressource_html-repsonse.php GLE-1.0
  */
  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_Hrm/hrm.class.php");

//TODO :> faire le recurs orga
//TODO :> faire le getRessource filter
//          :> database :> table des personnes disponible pour un projet ( != tache assignée )
//          :> hrm :> date dans istory
//TODO creeer compte RH manuellement

$projet_id  =$_REQUEST['projet_id'];

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

$hrm=new hrm($db);
//require_once('Var_Dump.php');

//Affiche toutes les ressources de la societe liees au projet
$hrm->listRessources();
$inProjetArr = array();
foreach($hrm->allRessource as $key=>$val)
{
    if ("x".$val['GLEId'] != "x")
    {
        $requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors,
                           ".MAIN_DB_PREFIX."projet_task,
                           ".MAIN_DB_PREFIX."Synopsis_hrm_user
                     WHERE ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user.user_id =  ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user
                       AND ".MAIN_DB_PREFIX."projet_task.fk_projet =  ".$projet_id."
                       AND ".MAIN_DB_PREFIX."Synopsis_hrm_user.user_id = ".$val['GLEId']."";
        $db->query($requete);
        $res=$db->fetch_object($requete);
        if ($res->fk_user."x" != "x")
        {
            $inProjetArr[$res->fk_user]=$val['empId'];
        }
    }
}
$pairimpair = "pair";
if (count($inProjetArr) > 0)
{
    print "<TABLE style='border-collapse: collapse; width: 100%;' cellpadding=15 >";
    print "<THEAD>";
    print "<TR>";
    print "<TH class='ui-widget-header ui-state-default'>Nom</TH><TH class='ui-widget-header ui-state-default'>Email</TH><TH class='ui-widget-header ui-state-default'>T&eacute;l&eacute;phone</TH>";
    print "</TR>";
    print "</THEAD>";
    print"<TBODY>";

    foreach($inProjetArr as $key=>$val)
    {
        if ($pairimpair == "pair") { $pairimpair = 'impair'; } else { $pairimpair = "pair"; }
        $fuser = new User($db);
        $fuser->fetch($key);
        print "<TR class='$pairimpair'><TD>";
        print utf8_encode($fuser->getNomUrl(1));
        print "</TD><TD>";
        print "<A href='mailto:".$fuser->email."'>".img_picto("mail_send.png","mail_send.png","style='vertical-align:text-top'")."&nbsp;&nbsp;".$fuser->email."</a>";
        print "</TD><TD>";
        print img_picto("call_out.png","call_out.png","style='vertical-align:text-top'")."&nbsp;&nbsp;".$fuser->user_mobile;
        print "</TD></TR>";
    }
    print "</TABLE>";

} else {
    print "Pas de ressource dans projet";
}

?>
