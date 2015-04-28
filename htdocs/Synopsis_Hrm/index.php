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

//TODO :> si RH admin => mettre un tag pour le reset de session dans la iframe

 include_once('../main.inc.php');
setcookie('adminRH',"false",time()+60*60*24*1,"/");
setcookie('Loggedin',"false",time()+60*60*24*1,"/");
setcookie('userid',$user->id,time()+60*60*24*1,"/");

foreach(array('fname','empID',"isAdmin", "user","isProjectAdmin","isManager","isDirector","isAcceptor","isOfferer","timePeriodSet","styleSheet","WPATH","printBenefits","localRights") as $key)
{
    $_SESSION[$key] = false;
}

 llxHeader();
 if($user->rights->hrm->hrm->Admin)
 {
    print '<br/><p><a class="butAction" href="admin.php?mainmenu='.$_GET['mainmenu'].'&idmenu='.$_GET['idmenu'].'">Admin RH</a><a  target="_blank" class="butAction" href="orange/jobs.php">Candidature</a></p>';
 }else{
    print '<br><a class="butAction" target="_blank" href="orange/jobs.php">Candidature</a>';
 }

 print "<iframe border=\"0\" name=\"iframedoli\" style=\"border: 0px;\" src=\"orange/index.php?doliauth=1\" width=\"100%\" height=\"750px\"></iframe>";
 
llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');
?>
