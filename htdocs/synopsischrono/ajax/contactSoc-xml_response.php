<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contactSoc-xml_response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
    $html = new Form($db);
    $xml ="";
    $socid = $_REQUEST['socid'];
    ob_start();
    $html->select_contacts($socid,'','contactid',1,'',false, null, "", 0, 1);
    $formContact = ob_get_clean();
//    $formContact = $html->tmpReturn;
    $xml .= "<contactsList><![CDATA[ $formContact ]]></contactsList>";

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print utf8_encodeRien($xml);
    print "</ajax-response>";

?>
