<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 7 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : activatePdf-xml_response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');

    $xml="";
    $debug = false;

    $pdfName = addslashes($_REQUEST['pdf']);
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$pdfName."' AND  type='process' ";
    $sql = $db->query($requete);
    if ($db->num_rows($sql) > 0)
    {
        $xml = "<KO>-2</KO>";
    } else {
        require_once(DOL_DOCUMENT_ROOT."/core/modules/synopsis_process/pdf_process_".$pdfName.".modules.php");
        $tmp = "pdf_process_".$pdfName;
        $class = new $tmp($db);
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, libelle) VALUES ('".$pdfName."','process','".$class->name."')";
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml = "<OK>OK</OK>";
        } else {
            $xml = "<KO>KO</KO>";
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