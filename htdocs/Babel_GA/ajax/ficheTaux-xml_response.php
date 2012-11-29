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
  * Name : ficheTaux-xml_response.php
  * GLE-1.1
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
                $requete = "SELECT DISTINCT max(Babel_GA_taux_cessionnaire.dateValidite) as dateVal
                              FROM Babel_GA_taux_cessionnaire
                             WHERE Babel_GA_taux_cessionnaire.cessionnaire_id = ".$id."
                               AND Babel_GA_taux_cessionnaire.dateValidite <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_cessionnaire
                                      WHERE cessionnaire_id = ".$id . "
                                        AND dateValidite = '".$date."'";
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
                $requete = "SELECT DISTINCT max(Babel_GA_taux_fournisseur.dateValidite) as dateVal
                              FROM Babel_GA_taux_fournisseur
                             WHERE Babel_GA_taux_fournisseur.fournisseur_id = ".$id."
                               AND Babel_GA_taux_fournisseur.dateValidite <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_fournisseur
                                      WHERE fournisseur_id = ".$id . "
                                        AND dateValidite = '".$date."'";
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
                $requete = "SELECT DISTINCT max(Babel_GA_taux_client.dateValidite) as dateVal
                              FROM Babel_GA_taux_client
                             WHERE Babel_GA_taux_client.client_id = ".$id."
                               AND Babel_GA_taux_client.dateValidite <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_client
                                      WHERE client_id = ".$id . "
                                        AND dateValidite = '".$date."'";
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
                $requete = "SELECT DISTINCT max(Babel_GA_taux_user.dateValidite) as dateVal
                              FROM Babel_GA_taux_user
                             WHERE Babel_GA_taux_user.client_id = ".$id."
                               AND Babel_GA_taux_user.dateValidite <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_user
                                      WHERE user_id = ".$id . "
                                        AND dateValidite = '".$date."'";
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
                $requete = "SELECT DISTINCT max(Babel_GA_taux_default.dateValidite) as dateVal
                              FROM Babel_GA_taux_default
                             WHERE Babel_GA_taux_default.dateValidite <= now()";
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $date = $res->dateVal;
                if ('x'.$date != 'x')
                {
                    $requete = "DELETE FROM Babel_GA_taux_default
                                      WHERE dateValidite = '".$date."'";
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