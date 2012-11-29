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
  * Name : ficheMarge-xml_response.php
  * GLE-1.1
  *
  */

  require_once("../../main.inc.php");
  $action = $_REQUEST['action'];
  $xml = "<ajax-response>";

  switch($action)
  {
    case 'SupprCurrTableFin':
    {
        $id=$_REQUEST['id'];
        $date = "";
        $typetaux = $_REQUEST['type'];
        switch ($typetaux)
        {
            case 'cessionnaire':
            {
                $requete = "SELECT DISTINCT max(Babel_GA_taux_marge.dateTx) as dateVal
                              FROM Babel_GA_taux_marge
                             WHERE Babel_GA_taux_marge.obj_refid = ".$id."
                               AND Babel_GA_taux_marge.type_ref = 'cessionnaire'
                               AND Babel_GA_taux_marge.dateTx <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_marge
                                      WHERE obj_refid = ".$id . "
                                        AND type_ref = 'cessionnaire'
                                        AND dateTx = '".$date."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                    }
                } else {
                    $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                }

            }
            break;
            case 'fournisseur':
            {
                $requete = "SELECT DISTINCT max(Babel_GA_taux_marge.dateTx) as dateVal
                              FROM Babel_GA_taux_marge
                             WHERE Babel_GA_taux_marge.obj_refid = ".$id."
                               AND Babel_GA_taux_marge.type_ref = 'fournisseur'
                               AND Babel_GA_taux_marge.dateTx <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_marge
                                      WHERE obj_refid = ".$id . "
                                        AND type_ref = 'fournisseur'
                                        AND dateTx = '".$date."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                    }
                } else {
                    $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                }
            }
            break;
            case 'client':
            {
                $requete = "SELECT DISTINCT max(Babel_GA_taux_marge.dateTx) as dateVal
                              FROM Babel_GA_taux_marge
                             WHERE Babel_GA_taux_marge.obj_refid = ".$id."
                               AND Babel_GA_taux_marge.type_ref = 'client'
                               AND Babel_GA_taux_marge.dateTx <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_marge
                                      WHERE obj_refid = ".$id . "
                                        AND type_ref = 'client'
                                        AND dateTx = '".$date."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                    }
                } else {
                    $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                }
            }
            break;
            case 'user':
            {
                $requete = "SELECT DISTINCT max(Babel_GA_taux_marge.dateTx) as dateVal
                              FROM Babel_GA_taux_marge
                             WHERE Babel_GA_taux_marge.obj_refid = ".$id."
                               AND Babel_GA_taux_marge.type_ref = 'user'
                               AND Babel_GA_taux_marge.dateTx <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_marge
                                      WHERE obj_refid = ".$id . "
                                        AND type_ref = 'user'
                                        AND dateTx = '".$date."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                    }
                } else {
                    $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                }

            }
            break;
            case 'dflt':
            {
                $requete = "SELECT DISTINCT max(Babel_GA_taux_marge.dateTx) as dateVal
                              FROM Babel_GA_taux_marge
                             WHERE Babel_GA_taux_marge.obj_refid = ".$id."
                               AND Babel_GA_taux_marge.type_ref = 'dflt'
                               AND Babel_GA_taux_marge.dateTx <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_marge
                                      WHERE obj_refid = ".$id . "
                                        AND type_ref = 'dflt'
                                        AND dateTxl = '".$date."'";
                    $sql = $db->query($requete);
                    if ($sql)
                    {
                        $xml .= "<OK>OK</OK>";
                    } else {
                        $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                    }
                } else {
                    $xml .= "<KO><![CDATA[".$db->lasterror."\n".$db->lasterrno."]]></KO>";
                }

            }
            break;
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
    echo $xml;
    echo "</ajax-response>";



?>