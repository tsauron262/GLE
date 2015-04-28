<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 29 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ficheCessionnaire-xml_response.php
  * GLE-1.1
  */


require_once('../../main.inc.php');
$action = $_REQUEST['action'];
$xml = "<ajax-response>";
switch ($action)
{
    case 'SetTaux':
    {
        $date = $_REQUEST['dateFinAdd'];
        $dateValidite = "";

        if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['dateFinAdd'],$arr))
        {
            $dateValidite = $arr[3]."-".$arr[2]."-".$arr[1];
            $id = $_REQUEST['id'];
            deleteIfExistTxFin($_REQUEST['type'],$dateValidite,$id);
            foreach ($_REQUEST as $key => $val)
            {
                if (preg_match('/txfinAdd([0-9]*)/',$key,$arr))
                {
                    $subId = $arr[1];
                    $plafond = $_REQUEST['plafondFinAdd'.$subId];
                    if ('x'.$plafond == "x")
                    {
                        $montantPlafond = 'NULL';
                    } else {
                        $montantPlafond = $plafond;
                    }
                    $tauxFinancement = preg_replace('/,/','.',$val);
                    $requete = "";
                    switch ($_REQUEST['type'])
                    {
                        case 'cessionnaire':
                        {

                            $requete = " INSERT INTO Babel_GA_taux_cessionnaire (
                                                tauxFinancement,
                                                montantPlafond,
                                                dateValidite,
                                                cessionnaire_id)
                                        VALUES (
                                                '".$tauxFinancement."' ,
                                                '".$montantPlafond."' ,
                                                '".$dateValidite."' ,
                                                ".$id." )";
                        }
                        break;
                        case 'fournisseur':
                        {
                            $requete = " INSERT INTO Babel_GA_taux_fournisseur (
                                                tauxFinancement,
                                                montantPlafond,
                                                dateValidite,
                                                fournisseur_id)
                                        VALUES (
                                                '".$tauxFinancement."' ,
                                                '".$montantPlafond."' ,
                                                '".$dateValidite."' ,
                                                ".$id." )";
                        }
                        break;
                        case 'client':
                        {
                            $requete = " INSERT INTO Babel_GA_taux_client (
                                                tauxFinancement,
                                                montantPlafond,
                                                dateValidite,
                                                client_id)
                                        VALUES (
                                                '".$tauxFinancement."' ,
                                                '".$montantPlafond."' ,
                                                '".$dateValidite."' ,
                                                ".$id." )";
                        }
                        break;
                        case 'user':
                        {
                            $requete = " INSERT INTO Babel_GA_taux_user (
                                                tauxFinancement,
                                                montantPlafond,
                                                dateValidite,
                                                user_id)
                                        VALUES (
                                                '".$tauxFinancement."' ,
                                                '".$montantPlafond."' ,
                                                '".$dateValidite."' ,
                                                ".$id." )";

                        }
                        break;
                        case 'dflt':
                        {
                            $requete = " INSERT INTO Babel_GA_taux_default (
                                                tauxFinancement,
                                                montantPlafond,
                                                dateValidite)
                                        VALUES (
                                                '".$tauxFinancement."' ,
                                                '".$montantPlafond."' ,
                                                '".$dateValidite."')";

                        }
                        break;
                    }
                    //deleteIfExistTxFin($_REQUEST['type'],$dateValidite,$id);
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$requete."]]></KO>";
                    }
                }
            }
        } else {
            $xml .= "<KO>KO La date n'est pas au bon format</KO>";
        }
    }
    break;
    case 'SetTauxMarge':
    {
        $date = $_REQUEST['dateTxMargeAdd'];

        if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$date,$arr))
        {
            $dateValidite = $arr[3]."-".$arr[2]."-".$arr[1];
            $id = $_REQUEST['id'];
            foreach ($_REQUEST as $key => $val)
            {
                if (preg_match('/txMargeAdd/',$key,$arr))
                {

                    $requete = "SELECT count(*) as cnt
                                  FROM Babel_GA_taux_marge
                                 WHERE type_ref = ".$_REQUEST['type']."
                                   AND dateTx = '.$dateValidite.'";
                    $sql1 = $db->query($requete);
                    $res1 = $db->fetch_object($sql1);
                    if ($res1->cnt > 0)
                    {
                        $requete = "DELETE
                                      FROM Babel_GA_taux_marge
                                     WHERE type_ref = ".$_REQUEST['type']."
                                       AND dateTx = '.$dateValidite.'";
                        $sql1 = $db->query($requete);
                    }
                    $subId = $arr[1];
                    $tauxMarge = preg_replace('/,/','.',$val);
                    $requete = "";
                    $requete = " INSERT INTO Babel_GA_taux_marge (
                                        taux,
                                        type_ref,
                                        dateTx,
                                        obj_refid)
                                VALUES (
                                        '".$tauxMarge."' ,
                                        '".$_REQUEST['type']."' ,
                                        '".$dateValidite."' ,
                                        ".$id." )";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$requete."]]></KO>";
                    }
                }
            }
        } else {
            $xml .= "<KO>KO La date n'est pas au bon format ".$date."</KO>";
        }
    }
    break;

}

$xml .= "</ajax-response>";

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    //echo "</ajax-response>";


function deleteIfExistTxFin($type,$dateValidite,$id=-1)
{
    global $db;
    switch ($_REQUEST['type'])
    {
        case 'cessionnaire':
        {

            $requete = "SELECT count(dateValidite) as cntdateVal
                                      FROM Babel_GA_taux_cessionnaire
                                     WHERE Babel_GA_taux_cessionnaire.cessionnaire_id = ".$id."
                                       AND Babel_GA_taux_cessionnaire.dateValidite ='". $dateValidite."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $cnt = $res->cntdateVal;
            if ($cnt > 0)
            {
                $requete = "DELETE        FROM Babel_GA_taux_cessionnaire
                                         WHERE Babel_GA_taux_cessionnaire.cessionnaire_id = ".$id."
                                           AND Babel_GA_taux_cessionnaire.dateValidite ='". $dateValidite."'";
                $db->query($requete);
            }
        }
        break;
        case 'fournisseur':
        {

            $requete = "SELECT count(dateValidite) as cntdateVal
                                      FROM Babel_GA_taux_fournisseur
                                     WHERE Babel_GA_taux_fournisseur.fournisseur_id = ".$id."
                                       AND Babel_GA_taux_fournisseur.dateValidite ='". $dateValidite."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $cnt = $res->cntdateVal;
            if ($cnt > 0)
            {
                $requete = "DELETE        FROM Babel_GA_taux_fournisseur
                                         WHERE Babel_GA_taux_fournisseur.fournisseur_id = ".$id."
                                           AND Babel_GA_taux_fournisseur.dateValidite ='". $dateValidite."'";
                $db->query($requete);
            }
        }
        break;
        case 'client':
        {

            $requete = "SELECT count(dateValidite) as cntdateVal
                                      FROM Babel_GA_taux_client
                                     WHERE Babel_GA_taux_client.client_id = ".$id."
                                       AND Babel_GA_taux_client.dateValidite ='". $dateValidite."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $cnt = $res->cntdateVal;
            if ($cnt > 0)
            {
                $requete = "DELETE        FROM Babel_GA_taux_client
                                         WHERE Babel_GA_taux_client.client_id = ".$id."
                                           AND Babel_GA_taux_client.dateValidite ='". $dateValidite."'";
                $db->query($requete);
            }
        }
        break;
        case 'user':
        {

            $requete = "SELECT count(dateValidite) as cntdateVal
                                      FROM Babel_GA_taux_user
                                     WHERE Babel_GA_taux_user.user_id = ".$id."
                                       AND Babel_GA_taux_user.dateValidite ='". $dateValidite."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $cnt = $res->cntdateVal;
            if ($cnt > 0)
            {
                $requete = "DELETE        FROM Babel_GA_taux_user
                                         WHERE Babel_GA_taux_user.user_id = ".$id."
                                           AND Babel_GA_taux_user.dateValidite ='". $dateValidite."'";
                $db->query($requete);
            }
        }
        break;
        case 'dflt':
        {

            $requete = "SELECT count(dateValidite) as cntdateVal
                                      FROM Babel_GA_taux_default
                                       AND Babel_GA_taux_default.dateValidite ='". $dateValidite."'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $cnt = $res->cntdateVal;
            if ($cnt > 0)
            {
                $requete = "DELETE        FROM Babel_GA_taux_default
                                         WHERE Babel_GA_taux_default.dateValidite ='". $dateValidite."'";
                $db->query($requete);
            }
        }
        break;
    }

}

?>
