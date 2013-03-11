<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 7 juil. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : addWidget-xml_response.php
  * GLE-1.1
  */

$activePOST=$_REQUEST['ajoute'];
//$disabledPOST=$_REQUEST['disabled'];
//

    require_once("../../../main.inc.php");
    
if(!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';

    $action = $_REQUEST['action'];
    $xml = "<ajax-response>";

    $activeArr=unserialize($activePOST);

    $userid = $_REQUEST['userid'];
        $arr=array();
    $type = $_REQUEST['type'];

    foreach($activeArr as $key=>$val)
    {
        $requete  = "SELECT " . MAIN_DB_PREFIX . "Synopsis_Dashboard_module.id,
                            " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.nom,
                            " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.module
                       FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard_module,
                            " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget
                      WHERE " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.id = " . MAIN_DB_PREFIX . "Synopsis_Dashboard_module.module_refid
                        AND type_refid = '".$type."'
                        AND " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.id=".$val;
        $sql = $db->query($requete);
        //print $requete."\n";
        $res = $db->fetch_object($sql);
        $arr[$res->module]['id']=$res->id;
        $arr[$res->module]['nom']=$res->nom;
        $arr[$res->module]['module']=$res->module;
    }
    $requete  = "SELECT params
                   FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                  WHERE dash_type_refid = '".$type."' AND user_refid =".$userid;
    if ($userid < 0)
    {
        $requete  = "SELECT params
                       FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                      WHERE dash_type_refid = '".$type."'
                        AND user_refid is null";
    }

    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $arrTmp = unserialize($res->params);
    $count =  count($arrTmp[0]) ;

    foreach($arr as $key => $val)
    {
        $arrTmp[0][$val['module']]=$count;
        $count++;
    }
    //Save
    $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard WHERE user_refid = ".$userid. " AND dash_type_refid ='".$type."'";
    if ($userid < 0)
    {
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard WHERE user_refid is null AND dash_type_refid ='".$type."'";
    }
    $sql=$db->query($requete);
    $requete ="INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Dashboard (params,user_refid,dash_type_refid) VALUES ('".serialize($arrTmp)."',".$userid.",'".$type."')";
    if ($userid < 0)
    {
        $requete ="INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Dashboard (params,user_refid,dash_type_refid) VALUES ('".serialize($arrTmp)."',NULL,'".$type."')";
    }
    $sql=$db->query($requete);
    if($sql)
    {
        $xml .= "<OK>OK</OK>";
    } else {
        $xml .= "<KO>KO</KO>";
    }



    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    echo "</ajax-response>";
?>
