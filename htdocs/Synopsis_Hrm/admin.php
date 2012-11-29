<?php
/*
 * Created on 27 mars 09
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */

//TODO :> si RH admin => mettre un tag pour le reset de sssion dans la iframe
 include_once('../main.inc.php');

 if(!$user->rights->hrm->hrm->Admin)
 {
    accessforbidden();
 }

setcookie('adminRH',"admin",time()+60*60*24*1,"/");
setcookie('Loggedin',"false",time()+60*60*24*1,"/");
setcookie('userid',$user->id,time()+60*60*24*1,"/");
foreach(array('fname','empID', "user","timePeriodSet","styleSheet","WPATH","printBenefits","localRights") as $key)
{
    unset ($_SESSION[$key]);
}
foreach (array("isProjectAdmin","isManager","isDirector","isAcceptor","isOfferer","isAdmin") as $key)
{
    $_SESSION[$key]="Yes";
}

$_SESSION["user"]="USR001";
$_SESSION["userGroup"]="USG001";
$_SESSION["empID"]=  NULL;
$_SESSION["fname"]="Admin";
$_SESSION["isSupervisor"]=true;
$_SESSION["localRights"]=  array("add"=> true, "edit" =>  true, "delete" =>  true, "view"=>  true, "repDef"=>  true);

 llxHeader();
 print '<br><a  class="butAction"  href="index.php?mainmenu='.$_GET['mainmenu'].'&idmenu='.$_GET['idmenu'].'"">Retour &agrave; ma fiche</a><br/><br/>';
 print "<iframe border=\"0\" name=\"iframedoli\" style=\"border: 0px;\" src=\"orange/index.php?doliauth=1\" width=\"100%\" height=\"750px\"></iframe>";

 llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
