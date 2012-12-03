<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 3 janv. 2011
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fctBuilder-xml_response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');

    $xml="";
    $debug = true;
    switch($_REQUEST['action']){
        case "add":{
            $label = addslashes($_REQUEST['label']);
            $description = addslashes($_REQUEST['description']);
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct (label,description) VALUES ('".$label."','".$description."')";
            $sql = $db->query($requete);
            $id = $db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_Process_form_fct");
            if ($id > 0)
            {
                $xml = "<OK>".$id."</OK>";
            } else {
                $xml = "<KO>KO</KO>";
            }
        }
        break;
        case "mod":{
            $id = $_REQUEST['id'];
            $label = addslashes($_REQUEST['label']);
            $description = addslashes($_REQUEST['description']);
            $class = addslashes($_REQUEST['class']);
            $fct = addslashes($_REQUEST['fct']);
            $printVarInsteadOdReturn = ($_REQUEST['printVarInsteadOdReturn']=='On'||$_REQUEST['printVarInsteadOdReturn']=='ON'||$_REQUEST['printVarInsteadOdReturn']=='on'?1:0);
            $VarToBePrinted = addslashes(($_REQUEST['VarToBePrinted']."x"!="x"?$_REQUEST['VarToBePrinted']:false));
            $paramsForHtmlName = addslashes(($_REQUEST['paramsForHtmlName']."x"!="x"?$_REQUEST['paramsForHtmlName']:false));
            $postTraitementValue = addslashes(($_REQUEST['postTraitementValue']."x"!="x"?$_REQUEST['postTraitementValue']:false));
            $fileClass = addslashes($_REQUEST['fileClass']);
            $param=array();
            foreach($_REQUEST['sortable'] as $key=>$val)
            {
                $param[]=$_REQUEST['params-'.$val];
            }
            $paramString = addslashes(join('||',$param));
            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct
                           SET fct = '".$fct."',
                               params = '".$paramString."',
                               description = '".$description."',
                               class = '".$class."',
                               fileClass = '".$fileClass."',
                               printVarInsteadOdReturn = '".$printVarInsteadOdReturn."',
                               VarToBePrinted = '".$VarToBePrinted."',
                               paramsForHtmlName = '".$paramsForHtmlName."',
                               postTraitementValue = '".$postTraitementValue."'
                         WHERE id = ".$id;
            $sql = $db->query($requete);
            if ($sql)
            {
                $xml = "<OK>".$id."</OK>";
            } else {
                $xml = "<KO>KO</KO>";

            }

        }
        break;
        case "del":{
            $id = $_REQUEST['id'];
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct WHERE id = ".$id;
            $sql = $db->query($requete);
            if ($sql)
            {
                $xml = "<OK>".$id."</OK>";
            } else {
                $xml = "<KO>KO</KO>";

            }
        }
        break;
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
