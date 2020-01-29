<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 30 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : listElementRetour_xml-response.php
  * BIMP-ERP-1.2
  */
require_once('../../main.inc.php');

$socid = $_REQUEST['socid'];

$xml="";

switch ($action)
{
    case 'list1type':
    {
        switch($_REQUEST['type'])
        {
            case 'commande':
            {
                if ($user->rights->commande->lire)
                {
                    $requete = "SELECT rowid,
                                       ref,
                                       unix_timestamp(date_commande) as dc
                                  FROM ".MAIN_DB_PREFIX."commande
                                 WHERE fk_soc = ".$socid;
                    $sql = $db->query($requete);
                    $xml .= "<commandes>";
                    while ($res=$db->fetch_object($sql))
                    {
                        $xml .= "<commande>";
                        $xml .= "<id>".$res->rowid."</id>";
                        $xml .= "<ref>".$res->ref."</ref>";
                        $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                        $xml .= "</commande>";
                    }
                    $xml .= "</commandes>";
                }
            }
            break;
            case 'facture':
            {
                if ($user->rights->facture->lire)
                {
                    $requete = "SELECT rowid,
                                       ref as ref,
                                       unix_timestamp(datef) as df
                                  FROM ".MAIN_DB_PREFIX."facture
                                 WHERE fk_soc = ".$socid;
                    $sql = $db->query($requete);
                    $xml .= "<factures>";
                    while ($res=$db->fetch_object($sql))
                    {
                        $xml .= "<facture>";
                        $xml .= "<id>".$res->rowid."</id>";
                        $xml .= "<ref>".$res->ref."</ref>";
                        $xml .= "<date>".date('d/m/Y',$res->df)."</date>";
                        $xml .= "</facture>";
                    }
                    $xml .= "</factures>";
                }
            }
            break;
            case 'livraison':
            {
                if ($user->rights->livraison->lire)
                {
                    $requete = "SELECT rowid,
                                       ref,
                                       unix_timestamp(date_livraison) as dl
                                  FROM ".MAIN_DB_PREFIX."livraison
                                 WHERE fk_soc = ".$socid;
                    $sql = $db->query($requete);
                    $xml .= "<livraisons>";
                    while ($res=$db->fetch_object($sql))
                    {
                        $xml .= "<livraison>";
                        $xml .= "<id>".$res->rowid."</id>";
                        $xml .= "<ref>".$res->ref."</ref>";
                        $xml .= "<date>".date('d/m/Y',$res->dl)."</date>";
                        $xml .= "</livraison>";
                    }
                    $xml .= "</livraisons>";
                }
            }
            break;
            case 'contrat':
            {
                if ($user->rights->contrat->lire)
                {
                    $requete = "SELECT rowid,
                                       ref,
                                       unix_timestamp(date_contrat) as dc
                                  FROM ".MAIN_DB_PREFIX."contrat
                                 WHERE is_financement = 0
                                   AND fk_soc = ".$socid;
                    $sql = $db->query($requete);
                    $xml .= "<contrats>";
                    while ($res=$db->fetch_object($sql))
                    {
                        $xml .= "<contrat>";
                        $xml .= "<id>".$res->rowid."</id>";
                        $xml .= "<ref>".$res->ref."</ref>";
                        $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                        $xml .= "</contrat>";
                    }
                    $xml .= "</contrats>";
                }
            }
            break;
            case 'contratGA':
            {
                if ($user->rights->GA->contrat->Lire)
                {
                    $requete = "SELECT rowid,
                                       ref,
                                       unix_timestamp(date_contrat) as dc
                                  FROM ".MAIN_DB_PREFIX."contrat
                                 WHERE is_financement = 1
                                   AND fk_soc = ".$socid;
                    $sql = $db->query($requete);
                    $xml .= "<contratGAs>";
                    while ($res=$db->fetch_object($sql))
                    {
                        $xml .= "<contratGA>";
                        $xml .= "<id>".$res->rowid."</id>";
                        $xml .= "<ref>".$res->ref."</ref>";
                        $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                        $xml .= "</contratGA>";
                    }
                    $xml .= "</contratGAs>";
                }
            }
            break;
        }
    }
    break;
    default:
    {
        if ($user->rights->commande->lire)
        {
            $requete = "SELECT rowid,
                               ref,
                               unix_timestamp(date_commande) as dc
                          FROM ".MAIN_DB_PREFIX."commande
                         WHERE fk_soc = ".$socid;
            $sql = $db->query($requete);
            $xml .= "<commandes>";
            while ($res=$db->fetch_object($sql))
            {
                $xml .= "<commande>";
                $xml .= "<id>".$res->rowid."</id>";
                $xml .= "<ref>".$res->ref."</ref>";
                $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                $xml .= "</commande>";
            }
            $xml .= "</commandes>";
        }
        if ($user->rights->facture->lire)
        {
            $requete = "SELECT rowid,
                               ref as ref,
                               unix_timestamp(datef) as df
                          FROM ".MAIN_DB_PREFIX."facture
                         WHERE fk_soc = ".$socid;
            $sql = $db->query($requete);
            $xml .= "<factures>";
            while ($res=$db->fetch_object($sql))
            {
                $xml .= "<facture>";
                $xml .= "<id>".$res->rowid."</id>";
                $xml .= "<ref>".$res->ref."</ref>";
                $xml .= "<date>".date('d/m/Y',$res->df)."</date>";
                $xml .= "</facture>";
            }
            $xml .= "</factures>";
        }
        if ($user->rights->expedition->livraison->lire)
        {
            $requete = "SELECT ".MAIN_DB_PREFIX."livraison.rowid,
                               ".MAIN_DB_PREFIX."livraison.ref,
                               unix_timestamp(".MAIN_DB_PREFIX."livraison.date_delivery) as dl
                          FROM ".MAIN_DB_PREFIX."livraison,
                               ".MAIN_DB_PREFIX."expedition
                         WHERE ".MAIN_DB_PREFIX."expedition.rowid = ".MAIN_DB_PREFIX."livraison.fk_expedition
                           AND ".MAIN_DB_PREFIX."expedition.fk_soc = ".$socid;
            $sql = $db->query($requete);
            $xml .= "<livraisons>";
            while ($res=$db->fetch_object($sql))
            {
                $xml .= "<livraison>";
                $xml .= "<id>".$res->rowid."</id>";
                $xml .= "<ref>".$res->ref."</ref>";
                if ($res->dl > 0)
                {
                    $xml .= "<date>".date('d/m/Y',$res->dl)."</date>";
                } else {
                    $xml .= "<date> - </date>";
                }
                $xml .= "</livraison>";
            }
            $xml .= "</livraisons>";
        }
        if ($user->rights->contrat->lire)
        {
            $requete = "SELECT rowid,
                               ref,
                               unix_timestamp(date_contrat) as dc
                          FROM ".MAIN_DB_PREFIX."contrat
                         WHERE is_financement = 0
                           AND fk_soc = ".$socid;
            $sql = $db->query($requete);
            $xml .= "<contrats>";
            while ($res=$db->fetch_object($sql))
            {
                $xml .= "<contrat>";
                $xml .= "<id>".$res->rowid."</id>";
                $xml .= "<ref>".$res->ref."</ref>";
                $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                $xml .= "</contrat>";
            }
            $xml .= "</contrats>";
        }
        if ($user->rights->GA->contrat->Lire)
        {
            $requete = "SELECT rowid,
                               ref,
                               unix_timestamp(date_contrat) as dc
                          FROM ".MAIN_DB_PREFIX."contrat
                         WHERE is_financement = 1
                           AND fk_soc = ".$socid;
            $sql = $db->query($requete);
            $xml .= "<contratGAs>";
            while ($res=$db->fetch_object($sql))
            {
                $xml .= "<contratGA>";
                $xml .= "<id>".$res->rowid."</id>";
                $xml .= "<ref>".$res->ref."</ref>";
                $xml .= "<date>".date('d/m/Y',$res->dc)."</date>";
                $xml .= "</contratGA>";
            }
            $xml .= "</contratGAs>";
        }

    }
    break;
}


    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= "<xml>".$xml."</xml>";
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;


?>
