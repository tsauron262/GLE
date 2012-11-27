<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 dec. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : formBuilder-xml_response.php
  * GLE-1.2
  */
  require_once('../../main.inc.php');

    $xml="";
    $debug = false;
    switch($_REQUEST['action']){

        case "supprData":{
            if ($_REQUEST['id'] > 0 )
            {
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form WHERE id = ".$_REQUEST['id'];
                $sql = $db->query($requete);
                if ($sql) $xml ="<OK>OK</OK>";
                else $xml ="<KO>KO</KO>";
            }

        }
        break;
        case "saveParamsData":{
            //p-id=&p-length=&p-name=&p-size=&s-border=&s-clear=&s-color=&s-float=&s-margin=&s-padding=&s-text-align=&s-=&class=
            foreach($_REQUEST as $key=>$val)
            {
                $idFormModel = preg_replace('/[^0-9]/',"",$_REQUEST['id']);
                $arr = array();
                if (preg_match('/^([\w])\|\|([\w\W]*)$/',$key,$arr))
                {
                    switch($arr[1]){
                        case 'c':
                        {
                            $idProp = $arr[2];
                            $valeur = addslashes(utf8_decode($val));
                            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value WHERE model_refid = ".$idFormModel ;
                            $sql = $db->query($requete);
                            if ($db->num_rows($sql) > 0){
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value
                                               SET valeur='".$valeur."'
                                             WHERE model_refid=".$idFormModel;

                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_class_value (model_refid,valeur) VALUES (".$idFormModel.",'".$valeur."')";
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            }

                        }
                        break;
                        case 's':
                        {
                            $idProp = $arr[2];
                            $valeur = addslashes(utf8_decode($val));
                            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value WHERE model_refid = ".$idFormModel. " AND style_refid = ".$idProp;
                            $sql = $db->query($requete);
                            if ($db->num_rows($sql) > 0){
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value
                                               SET valeur='".$valeur."'
                                             WHERE model_refid=".$idFormModel."
                                               AND style_refid=".$idProp;
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_style_value (style_refid, model_refid,valeur) VALUES (".$idProp.",".$idFormModel.",'".$valeur."')";
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            }
                        }
                        break;
                        case 'p':
                        {
                            $idProp = $arr[2];
                            $valeur = addslashes(utf8_decode($val));
                            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value WHERE model_refid = ".$idFormModel. " AND prop_refid = ".$idProp;
                            $sql = $db->query($requete);
                            if ($db->num_rows($sql) > 0){
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value
                                               SET valeur='".$valeur."'
                                             WHERE model_refid=".$idFormModel."
                                               AND prop_refid=".$idProp;
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value (prop_refid, model_refid,valeur) VALUES (".$idProp.",".$idFormModel.",'".$valeur."')";
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            }
                        }
                        break;
                        case 'r':
                            $valeur = addslashes(utf8_decode($val));
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
                                               SET rights='".$valeur."'
                                             WHERE id=".$idFormModel;

                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                        break;
                        case 'f':
                        {
                            $arrIds = preg_split('/;;/',$arr[2]);
                            $valeur = addslashes(utf8_decode($val));

                            $fctId = $arrIds[1];
                            $idValFct =addslashes($arrIds[0]);

                            $requete = "SELECT *
                                          FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                                         WHERE model_refid = ".$idFormModel. "
                                           AND label = '".$idValFct."'
                                           AND fct_refid =".$fctId;
                            if($debug) print $requete.";\n";
                            $sql = $db->query($requete);
                            if ($db->num_rows($sql) > 0){
                                $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct_value
                                               SET valeur='".$valeur."'
                                             WHERE model_refid=".$idFormModel."
                                               AND label='".$idValFct."'
                                               AND fct_refid='".$fctId."'";
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            } else {
                                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_type_prop_value
                                                        (label, model_refid,valeur,fct_refid)
                                                 VALUES ('".$idValFct."',".$idFormModel.",'".$valeur."',".$fctId.")";
                                $sql = $db->query($requete);
                                if($debug) print $requete.";\n";
                            }
                        }
                        break;
                    }
                }
            }
        }
        break;

        case "saveData":{
            $arr = $_REQUEST['sortable'];
            $id = $_REQUEST['id'];
            $db->begin();
//            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE form_refid = ".$id;
//            if($debug) print $requete.";\n";
//            $sql = $db->query($requete);
            $arrId = array();
            $requete = "SELECT id FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE form_refid = ".$id;
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql))
            {
                $arrId[$res->id]=$res->id;
            }
            $rang = 0;
            foreach($arr as $key=>$val)
            {
                $rang++;
                $newLine = false;
                if (preg_match("/^n/",$val)){
                    $newLine=1;
                }

                $desc = addslashes(traiteStr($_REQUEST['descr-'.$val]));
                $dflt = addslashes(traiteStr($_REQUEST['dflt-'.$val]));
                if ($_REQUEST['dflt-'.$val.'-var'] ."x" != "x")
                {
                    $tmp = preg_replace('/^g-/','',$_REQUEST['dflt-'.$val.'-var'] );
                    $dflt = '[GLOBVAR]'.$tmp;
                }
                $titre = addslashes(traiteStr($_REQUEST['titre-'.$val]));
                $type = $_REQUEST['type-'.$val];
                $requete = "SELECT *
                              FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type
                             WHERE code = '".$type."'";
                if($debug) print $requete.";\n";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $type = $res->id;
                $src = $_REQUEST['src-'.$val];
                $order = $key;
                $srcId = false;
                switch(true){
                    case preg_match('/^r/',$src):{
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src
                                     WHERE requete_refid = ".substr($src,2);
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0 )
                        {
                            $res=$db->fetch_object($sql);
                            $srcId = $res->id;
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_src (requete_refid) VALUES (".substr($src,2).")";
                            if($debug) print $requete.";\n";
                            $sql = $db->query($requete);
                            $srcId = $db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_src');
                        }
                    }
                    break;
                    case preg_match('/^f/',$src):{
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src
                                     WHERE fct_refid = ".substr($src,2);
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0 )
                        {
                            $res=$db->fetch_object($sql);
                            $srcId = $res->id;
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_src (fct_refid) VALUES (".substr($src,2).")";
                            if($debug) print $requete.";\n";
                            $sql = $db->query($requete);
                            $srcId = $db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_src');
                        }
                    }
                    break;
                    case preg_match('/^l/',$src):{
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src
                                     WHERE list_refid = ".substr($src,2);
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0 )
                        {
                            $res=$db->fetch_object($sql);
                            $srcId = $res->id;
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_src (list_refid) VALUES (".substr($src,2).")";
                            if($debug) print $requete.";\n";
                            $sql = $db->query($requete);
                            $srcId = $db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_src');
                        }
                    }
                    break;
                    case preg_match('/^g/',$src):{
                        $requete = "SELECT *
                                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_src
                                     WHERE global_refid = ".substr($src,2);
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                        if ($db->num_rows($sql) > 0 )
                        {
                            $res=$db->fetch_object($sql);
                            $srcId = $res->id;
                        } else {
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_src (global_refid) VALUES (".substr($src,2).")";
                            if($debug) print $requete.";\n";
                            $sql = $db->query($requete);
                            $srcId = $db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_src');
                        }
                    }
                    break;
                }
                $sql=false;

                if($newLine)
                {
                    if ($srcId)
                    {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
                                                (type_refid, label,description, dflt,src_refid,form_refid,rang)
                                         VALUES (".$type.",'".$titre."','".$desc."','".$dflt."','".$srcId."','".$id."',".$rang.")";
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                    } else {
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
                                                (type_refid, label,description, dflt,src_refid,form_refid,rang)
                                         VALUES (".$type.",'".$titre."','".$desc."','".$dflt."',NULL,'".$id."',".$rang.")";
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                    }
                    $newId = $db->last_insert_id('" . MAIN_DB_PREFIX . "Synopsis_Process_form_model');
                    if ($sql) $db->commit();
                    $xml .= "<majId><new>".$newId."</new><old>".$val."</old></majId>";

                } else {
                    if ($arrId[$val]==$val)
                    {
                        $arrId[$val]=false;
                    }
                    if ($srcId)
                    {
                        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
                                       SET type_refid=".$type.",
                                           label='".$titre."',
                                           description='".$desc."',
                                           dflt='".$dflt."',
                                           rang=".$rang.",
                                           src_refid='".$srcId."'
                                     WHERE id =".$val."";
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                    } else {
                        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Process_form_model
                                       SET type_refid=".$type.",
                                           label='".$titre."',
                                           description='".$desc."',
                                           dflt='".$dflt."',
                                           rang=".$rang.",
                                           src_refid=NULL
                                     WHERE id =".$val."";
                        if($debug) print $requete.";\n";
                        $sql = $db->query($requete);
                    }
                    if ($sql) $db->commit();
                }
            }
        }
        break;
    }

    foreach($arrId as $key=>$val)
    {
        if ($val){
            $requete = 'DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_model WHERE id = '.$val;
            if ($debug) print $requete;
            $sql = $db->query($requete);
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
    
    
    function traiteStr($str){
        return $str;
    }


?>