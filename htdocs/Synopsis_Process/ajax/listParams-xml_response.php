<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 30 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : formPreview.php
  * GLE-1.2
  *
  */

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');
    $id = preg_replace('/^sortable_/','',$_REQUEST['id']);
    $type = $_REQUEST['type'];
    $xml="";
    $debug = false;

    $requete = "SELECT element_name,valeur,p.id
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t
                       LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop as p ON p.type_refid = t.id
                       LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value as v ON p.id = v.prop_refid AND model_refid = ".$id."
                 WHERE t.code = '".$type."'";

    //print $requete;
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        $xml .= "<params>";
        $xml .= "    <name><![CDATA[".utf8_encode($res->element_name)."]]></name>";
        $xml .= "    <id><![CDATA[".utf8_encode($res->id)."]]></id>";
        $xml .= "    <valeur><![CDATA[".utf8_encode($res->valeur)."]]></valeur>";
        $xml .= "</params>";
    }
    $requete = "SELECT element_name, p.id, valeur
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t
                       LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style as p ON p.type_refid = t.id
                       LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value as v ON p.id = v.style_refid AND model_refid = ".$id."
                 WHERE t.code = '".$type."'";

    //print $requete;
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        $xml .= "<style>";
        $xml .= "    <name><![CDATA[".utf8_encode($res->element_name)."]]></name>";
        $xml .= "    <id><![CDATA[".utf8_encode($res->id)."]]></id>";
        $xml .= "    <valeur><![CDATA[".utf8_encode($res->valeur)."]]></valeur>";
        $xml .= "</style>";
    }

    $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type as t
                       LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value as v ON v.model_refid = ".$id."
                 WHERE t.code = '".$type."'";

    //print $requete;
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        $xml .= "<class>";
        $xml .= "    <valeur><![CDATA[".utf8_encode($res->valeur)."]]></valeur>";
        $xml .= "</class>";
    }

    $requete = "SELECT rights
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as t
                 WHERE t.id = ".$id;

    //print $requete;
    $sql = $db->query($requete);
    while($res = $db->fetch_object($sql))
    {
        $xml .= "<rights>";
        $xml .= "    <valeur><![CDATA[".utf8_encode($res->rights)."]]></valeur>";
        $xml .= "</rights>";
    }

    $requete = "SELECT v.valeur,
                       f.params,
                       concat (f.class,'::',f.fct) as fct_name,
                       v.label,
                       f.id
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src as s,
                       " . MAIN_DB_PREFIX . "Synopsis_Process_form_model as m,
                       " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct as f
             LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value as v ON v.model_refid = ".$id." AND v.fct_refid = f.id
                 WHERE m.src_refid = s.id
                   AND s.fct_refid = f.id
                   AND m.id = ".$id;
    //print $requete;
    $sql = $db->query($requete);
    $arr=array();
    $arrParams = array();
    $fct_name="";
    $fct_id=0;
    while($res = $db->fetch_object($sql))
    {
        if ($res->label."x" != "x"){
            $arr[$res->label]=$res->valeur;
        }
        if ($res->params."x" != "x")
            $arrParams=preg_split('/\|\|/',$res->params);
        if ($res->fct_name."x" != "x")
            $fct_name=$res->fct_name;
        if ($res->id."x" != "x")
            $fct_id = $res->id;
    }
    foreach($arrParams as $key=>$val)
    {
        $xml .= "<fctParams fct_name='".utf8_encode($fct_name)."'  fct_id='".utf8_encode($fct_id)."'>";
        $xml .= "    <label><![CDATA[".utf8_encode($val)."]]></label>";
        $xml .= "    <id><![CDATA[".utf8_encode($val)."]]></id>";
        $valeur = $arr[$val];
        $xml .= "    <valeur><![CDATA[".utf8_encode($valeur)."]]></valeur>";
        $xml .= "</fctParams>";
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