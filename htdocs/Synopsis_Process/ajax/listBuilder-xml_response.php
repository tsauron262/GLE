<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listBuilder-xml_response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');

    $xml="";
    $debug = false;
    if($_REQUEST['action'] =='update' )
    {
        $listeObj = new listform($db);
        $listeObj->id = $_REQUEST['id'];
        $listeObj->label = $_REQUEST['label'];
        $listeObj->description = $_REQUEST['description'];
        $res = $listeObj->update();
        if ($res > 0)
        {
            //delete lignes
            $db->begin();
            $res = $listeObj->delLignes();
            //insert new
            $listeObjMember = new listformmember($db);
            $listeObjMember->list_refid = $listeObj->id;
            foreach($_REQUEST['sortable'] as $key=> $val)
            {
                $listeObjMember->label = $_REQUEST['label-'.$val];
                $listeObjMember->valeur = $_REQUEST['valeur-'.$val];
                $res1 = $listeObjMember->add();
                if ($res1 > 0 && $res) $res = true; else $res=false;
            }

            if ($res) {
                $db->commit();
                $xml = "<OK>OK</OK>";
            } else {
                $db->rollback();
                $xml = "<KO><![CDATA[Erreur SQL : ".$listeObj->error.']]></KO>';
            }
        } else {
            if ($res == -1)
            {
                $xml = "<KO><![CDATA[Erreur SQL : ".$listeObj->error.']]></KO>';;
            } else if ($res == -2){
                $xml = "<KO><![CDATA[Erreur: ".$listeObj->error.']]></KO>';;
            }
        }
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo "<ajax-response>";
    echo $xml;
    echo "</ajax-response>";

?>