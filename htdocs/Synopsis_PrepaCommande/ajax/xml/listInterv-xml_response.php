<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listInterv-xml_response.php
  * GLE-1.2
  */

  require_once('../../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
  $id = $_REQUEST['id'];
  $com = new Synopsis_Commande($db);
  $com->fetch($id);
  $xmlStr = "<ajax-response>";

  $arrGrpCom = array($com->id=>$com->id);
  $arrGrp = $com->listGroupMember(true);
  if($arrGrp && count($arrGrp) > 0)
  foreach($arrGrp as $key=>$commandeMember)
  {
      $arrGrpCom[$commandeMember->id]=$commandeMember->id;
  }


  if ($id > 0)
  {
    if ($_REQUEST['type']=='DI')
    {
        $requete = "SELECT rowid,
                           ref,
                           ifnull(fk_user_target, fk_user_prisencharge) as fk_interv,
                           UNIX_TIMESTAMP(datei) as dateinterv
                      FROM llx_Synopsis_demandeInterv
                     WHERE fk_commande  = ".join(',',$arrGrpCom). "
                  ORDER BY datei DESC ";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $tmpUser = new User($db);
            $tmpUser->id = $res->fk_interv;
            $tmpUser->fetch();
            $xmlStr .= "<datainterv id='".$res->rowid."'>";
            $xmlStr .= "<rowid>".$res->rowid."</rowid>";
            $xmlStr .= "<ref><![CDATA[".$res->ref."]]></ref>";
            $xmlStr .= "<date><![CDATA[".date('d/m/Y',$res->dateinterv)."]]></date>";
            $xmlStr .= "<interv><![CDATA[".utf8_encode($tmpUser->fullname)."]]></interv>";
            $xmlStr .= "</datainterv>";
        }

    }

    if ($_REQUEST['type']=='FI')
    {
        $requete = "SELECT rowid,
                           ref,
                           fk_user_author as fk_interv,
                           UNIX_TIMESTAMP(datei) as dateinterv
                      FROM ".MAIN_DB_PREFIX."Synopsis_fichinter
                     WHERE fk_commande in (".join(',',$arrGrpCom). ")
                  ORDER BY datei DESC ";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $tmpUser = new User($db);
            $tmpUser->id = $res->fk_interv;
            $tmpUser->fetch();
            $xmlStr .= "<datainterv id='".$res->rowid."'>";
            $xmlStr .= "<rowid>".$res->rowid."</rowid>";
            $xmlStr .= "<ref><![CDATA[".$res->ref."]]></ref>";
            $xmlStr .= "<date><![CDATA[".date('d/m/Y',$res->dateinterv)."]]></date>";
            $xmlStr .= "<interv><![CDATA[".utf8_encode($tmpUser->fullname)."]]></interv>";
            $xmlStr .= "</datainterv>";
        }

    }


    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";

  } else {
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print "<error>Aucun Element</error>";
    print "</ajax-response>";

  }



?>
