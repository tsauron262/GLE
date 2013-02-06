<?php/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
/**
        \file       htdocs/ProspectBabel/ajaxbox.php
        \brief      Fichier de reponse sur evenement Ajax deplacement boxes
        \version    $Revision: 1.3 $
*/

require('../master.inc.php');
require_once(DOL_DOCUMENT_ROOT."/boxes.php");

// Enregistrement de la position des boxes
if((isset($_GET['boxorder']) && !empty($_GET['boxorder'])) && (isset($_GET['userid']) && !empty($_GET['userid'])))
{
    dol_syslog("AjaxBox boxorder=".$_GET['boxorder']." userid=".$_GET['userid'], LOG_DEBUG);

    $infobox=new InfoBox($db);
    $result=$infobox->saveboxorder("22411",$_GET['boxorder'],$_GET['userid']);
}

?>
