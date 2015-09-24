<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 23 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getElements_xml-response.php
  * GLE-1.2
  */

    require_once('../../main.inc.php');
    $id = $_REQUEST['id'];
    $affaireId = $_REQUEST['affaireId'];

    $xml = "<ajax-response>";

    $arrExclude=array();
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Affaire_Element WHERE affaire_refid = ". $affaireId ." ORDER BY type";
    $sql = $db->query($requete);
    while ($res=$db->fetch_object($sql))
    {
        $arrExclude[$res->type][$res->element_id]=1;
    }

    function doExclude($gid,$eid)
    {
        global $arrExclude;
        if ($arrExclude[$gid][$eid])
        {
            return false;
        } else {
            return true;
        }
    }

    //Propale / PropaleGA
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='propale'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("propale",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Commande
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='commande'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("commande",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".price($res->total_ht)." &euro;]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Contrat / ContratGA
    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_contrat) as datec,statut FROM ".MAIN_DB_PREFIX."contrat WHERE is_financement = 0 AND fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='contrat'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("contrat",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount>".date('d/m/Y',$res->datec)."</amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }
    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_contrat) as datec,statut FROM ".MAIN_DB_PREFIX."contrat WHERE is_financement = 1 AND fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='contratGA'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("contratGA",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount>".date('d/m/Y',$res->datec)."</amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Expedition
    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_expedition) as datee, fk_statut FROM ".MAIN_DB_PREFIX."expedition WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='expedition'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("expedition",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datee)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }


    //Livraison
    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_livraison) as datee, fk_statut FROM ".MAIN_DB_PREFIX."livraison WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='livraison'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("livraison",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datee)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }


    //Projet
    $requete = "SELECT rowid, ref, title, fk_statut FROM ".MAIN_DB_PREFIX."Synopsis_projet_view WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='projet'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("projet",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".utf8_encode($res->title)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Commande Fourn
    $requete = "SELECT rowid, ref, total_ht FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='commande fournisseur'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("commande fournisseur",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Facture Fourn
    $requete = "SELECT rowid, facnumber as ref, total_ht FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='facture fournisseur'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("facture fournisseur",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Facture
    $requete = "SELECT rowid, facnumber as ref, total as total_ht FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='facture'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("facture",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Livraison
    $requete = "SELECT rowid, ref, unix_timestamp(date_livraison) as datel FROM ".MAIN_DB_PREFIX."expedition, ".MAIN_DB_PREFIX."livraison
                 WHERE ".MAIN_DB_PREFIX."livraison.fk_expedition = ".MAIN_DB_PREFIX."expedition.rowid
                   AND fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='livraison'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("livraison",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datel)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //DI / FI
    $requete = "SELECT rowid, ref, unix_timestamp(datei) as dateiU
                  FROM ".MAIN_DB_PREFIX."fichinter
                 WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='FI'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("FI",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->dateiU)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }
    $requete = "SELECT rowid, ref, unix_timestamp(datei) as dateiU
                  FROM ".MAIN_DB_PREFIX."synopsisdemandeinterv
                 WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='DI'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("DI",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->dateiU)."]]></amount>";
                $xml .= "  <statut>".$res->fk_statut."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Contacts
    $requete = "SELECT rowid, ref, unix_timestamp(datec) as dateiU
                  FROM ".MAIN_DB_PREFIX."socpeople
                 WHERE fk_soc = ".$id;
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='livraison'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("livraison",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->firstname." ".$res->name."]]></ref>";
                $xml .= "  <amount><![CDATA[".$res->email."]]></amount>";
                $xml .= "  <statut></statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }


    //Domaines
    $requete = "SELECT rowid, label, unix_timestamp(datec) as datecU, unix_timestamp(datef) as datefU
                  FROM Babel_domain";
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='domain'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("domain",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->label."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datecU)." ".date('d/m/Y',$res->datefU)."]]></amount>";
                $xml .= "  <statut>".$res->active."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //SSL Cert
    $requete = "SELECT rowid, label, unix_timestamp(datec) as datecU, unix_timestamp(datef) as datefU
                  FROM Babel_SSLCert";
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='SSLCert'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("SSLCert",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->label."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datecU)." ".date('d/m/Y',$res->datefU)."]]></amount>";
                $xml .= "  <statut>".$res->active."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }

    //Licences
    $requete = "SELECT rowid, label, unix_timestamp(datec) as datecU, unix_timestamp(datef) as datefU
                  FROM Babel_Licence";
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<group id='licence'>";
        while ($res = $db->fetch_object($sql))
        {
            if (doExclude("licence",$res->rowid))
            {
                $xml .= '<element>';
                $xml .= "  <rowid>".$res->rowid."</rowid>";
                $xml .= "  <ref><![CDATA[".$res->label."]]></ref>";
                $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datecU)." ".date('d/m/Y',$res->datefU)."]]></amount>";
                $xml .= "  <statut>".$res->active."</statut>";
                $xml .= '</element>';
            }
        }
        $xml .= "</group>";
    }


    //Paiement ?? paiement Fourn ?? prelevement ??

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;
    echo "</ajax-response>";


?>