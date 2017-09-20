<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 17 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : delDI-xml_response.php
  * BIMP-ERP-1.2
  */


    require_once('../../../main.inc.php');

    $id = $_REQUEST['id'];
    require_once(DOL_DOCUMENT_ROOT.'/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php');
    $di = new Synopsisdemandeinterv($db);
    $di->fetch($id);
    $di->author = $user->id;
    $di->fetch_lines();
    
    $di->getExtra();
    
    if($di->date < date(time()))
        $di->date = date(time());

    $res = $di->create();
    
    
    foreach($di->tabExtraV as $idExtraKey => $val)
        $di->setExtra ($idExtraKey, $val);
    
    if ($res)
    foreach($di->lignes as $key=>$val)
    {
        $val->fk_synopsisdemandeinterv = $res;
        if($db->jdate($val->datei) < date(time()))
            $val->datei = $di->date;
        $val->insert();
    }


    $xmlStr = "<ajax-response>";
    if ($res){
        $xmlStr .= "<OK>OK</OK>";
    } else {
        $xmlStr .= "<KO>KO</KO>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";



?>