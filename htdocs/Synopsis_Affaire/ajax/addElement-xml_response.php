<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 25 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : addElement-xml_response.php
  * GLE-1.2
  */


    require_once('../../main.inc.php');
    $id = $_REQUEST['id'];
    $eid = $_REQUEST['eid'];
    $gid = $_REQUEST['gid'];

    $affaireId = $_REQUEST['id'];

    $xml = "<ajax-response>";
    $requete = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_Affaire_Element`
                            (`type`,`element_id`,`datea`,`fk_author`,`affaire_refid`)
                     VALUES
                            ('".$gid."', ".$eid.", now(), ".$user->id.", ".$affaireId.")";
    $sql = $db->query($requete);
    if ($sql)
    {
        $xml .= "<OK>OK</OK>";

        //Store recurs data here


        $langs->load('sendings');
        $langs->load('orders');
        $langs->load('propal');
        getXml($gid,$eid);

    //
    //
    //    //Contrat / ContratGA
    //    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_contrat) as datec,fk_statut FROM ".MAIN_DB_PREFIX."contrat WHERE fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='contrat'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount>".date('d/m/Y',$res->datec)."</amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }
    //
    //    //Expedition
    //    $requete = "SELECT rowid, ref, UNIX_TIMESTAMP(date_expedition) as datee, fk_statut FROM ".MAIN_DB_PREFIX."expedition WHERE fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='expedition'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datee)."]]></amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }
    //
    //    //Projet
    //    $requete = "SELECT rowid, ref, title, fk_statut FROM ".MAIN_DB_PREFIX."Synopsis_projet_view WHERE fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='projet'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount><![CDATA[".utf8_encode($res->title)."]]></amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }
    //
    //
    //    //Facture Fourn
    //    $requete = "SELECT rowid, facnumber as ref, total_ht FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='facture fournisseur'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }
    //
    //    //Facture
    //    $requete = "SELECT rowid, facnumber as ref, total_ht FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='facture'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount><![CDATA[".price($res->total_ht)."]]></amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }
    //
    //    //Livraison
    //    $requete = "SELECT rowid, ref, unix_timestamp(date_livraison) as datel FROM ".MAIN_DB_PREFIX."expedition, ".MAIN_DB_PREFIX."livraison
    //                 WHERE ".MAIN_DB_PREFIX."livraison.fk_expedition = ".MAIN_DB_PREFIX."expedition.rowid
    //                   AND fk_soc = ".$id;
    //    $sql = $db->query($requete);
    //    if ($sql)
    //    {
    //        $xml .= "<group id='facture'>";
    //        while ($res = $db->fetch_object($sql))
    //        {
    //            $xml .= '<element>';
    //            $xml .= "  <rowid>".$res->rowid."</rowid>";
    //            $xml .= "  <ref><![CDATA[".$res->ref."]]></ref>";
    //            $xml .= "  <amount><![CDATA[".date('d/m/Y',$res->datel)."]]></amount>";
    //            $xml .= "  <statut>".$res->fk_statut."</statut>";
    //            $xml .= '</element>';
    //        }
    //        $xml .= "</group>";
    //    }

        //DI / FI

        //Ndf

        //Contacts

        //Domaines

        //SSL Cert

        //Licences

        //Paiement ?? paiement Fourn ?? prelevement ??

    } else {
        $xml .= "<KO>KO</KO>";
        $xml .= "<KO>".$requete. "<br/>".$db->lasterrno . "<br/>".$db->lastqueryerror."</KO>";
    }
    //$xml .= join()
//    foreach($xmlArr as $key => $val)
//    {
//        $xml .= "<group id='".$key."'>";
//        $xml .= $val;
//        $xml .= "</group>";
//
//    }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo utf8_encode($xml);
    echo "</ajax-response>";

    $referent ="";
    $filter = array();
    $xmlArr=array();
    function get_referent($type,$rowid)
    {
        global $db,$xml,$arr,$filter,$xmlArr;
        $arr[]=array('rowid'=>$rowid,'type'=>$type);
        $filter[$type][$rowid]=1;
        recurseElement();
    }
    $arr=array();
    function recurseElement()
    {
        global $db,$xml,$arr,$filter,$xmlArr;
        while(count($arr)>0)
        {
            $tmp = array_pop($arr);
            getXml($tmp['type'],$tmp['rowid']);
        }
    }

    function getXml($gid,$eid)
    {
        global $db,$xml,$arr,$filter,$xmlArr;
        if (!strlen($xmlArr[$gid]) > 0)
        {
            $xmlArr[$gid]="";
        }

        switch($gid)
        {
            case 'propale':
            {
                //Propale / PropaleGA
                if ($eid > 0)
                {
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $prop = new Propal($db);
                    $prop->fetch($eid);

//                    $xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
//                    $xml .= "</group>";
                    //Lauch recurs
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_propale = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='commande';
                        if (!$filter[$type][$res->fk_commande]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_commande,'type'=>$type);
                            $filter[$type][$res->fk_commande]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pr_liv WHERE fk_propal = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='livraison';
                        if (!$filter[$type][$res->fk_livraison]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_livraison,'type'=>$type);
                            $filter[$type][$res->fk_livraison]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pr_exp WHERE fk_propal = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='expedition';
                        if (!$filter[$type][$res->fk_expedition]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_expedition,'type'=>$type);
                            $filter[$type][$res->fk_expedition]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_propal = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='facture';
                        if (!$filter[$type][$res->fk_facture]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_facture,'type'=>$type);
                            $filter[$type][$res->fk_facture]=1;
                        }
                    }
                    recurseElement();
                }
            }
            break;
            case 'commande':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                if ($eid > 0)
                {
                    $prop = new Commande($db);
                    $prop->fetch($eid);
                    $soc = new Societe($db);
                    $soc->fetch($prop->socid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$soc->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    //Lauch recurs
                    $requete = "SELECT fk_source as fk_propale FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'propal' AND targettype = 'commande' AND fk_target = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='propale';
                        if (!$filter[$type][$res->fk_propale]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_propale,'type'=>$type);
                            $filter[$type][$res->fk_propale]=1;
                        }
                    }
                    $requete = "SELECT fk_target as fk_facture FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'facture' AND fk_source = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='facture';
                        if (!$filter[$type][$res->fk_facture]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_facture,'type'=>$type);
                            $filter[$type][$res->fk_facture]=1;
                        }
                    }
                    $requete = "SELECT fk_target as fk_livraison FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'expedition' AND fk_source = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='livraison';
                        if (!$filter[$type][$res->fk_livraison]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_livraison,'type'=>$type);
                            $filter[$type][$res->fk_livraison]=1;
                        }
                    }
                    $requete = "SELECT fk_target as fk_expedition FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'expedition' AND fk_source = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='expedtion';
                        if (!$filter[$type][$res->fk_expedition]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_expedition,'type'=>$type);
                            $filter[$type][$res->fk_expedition]=1;
                        }
                    }
                    recurseElement();
                }

            }
            break;
            case 'expedition':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php');
                if ($eid > 0)
                {
                    $prop = new Expedition($db);
                    $prop->fetch($eid);
                    $soc = new Societe($db);
                    $soc->fetch($prop->socid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$psoc->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_exp WHERE fk_expedition = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='commande';
                        if (!$filter[$type][$prop->id]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_commande,'type'=>$type);
                            $filter[$type][$prop->id]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."livraison WHERE fk_expedition = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='livraison';
                        if (!$filter[$type][$prop->id]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_livraison,'type'=>$type);
                            $filter[$type][$prop->id]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pr_exp WHERE fk_expedition = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='propale';
                        if (!$filter[$type][$prop->id]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_propal,'type'=>$type);
                            $filter[$type][$prop->id]=1;
                        }
                    }
                    recurseElement();
                }

            }
            break;
            case 'livraison':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php');
                if ($eid > 0)
                {
                    $prop = new Livraison($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."livraison WHERE rowid = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='expedition';
                        if (!$filter[$type][$res->fk_expedition]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_expedition,'type'=>$type);
                            $filter[$type][$res->fk_expedition]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_liv WHERE fk_livraison = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='commande';
                        if (!$filter[$type][$res->fk_commande]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_commande,'type'=>$type);
                            $filter[$type][$res->fk_commande]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."pr_liv WHERE fk_livraison = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='propale';
                        if (!$filter[$type][$res->fk_propal]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_propal,'type'=>$type);
                            $filter[$type][$res->fk_propal]=1;
                        }
                    }
                    recurseElement();
                }

            }
            break;
            case 'commande fournisseur':
                require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
                if ($eid > 0)
                {
                    $prop = new CommandeFournisseur($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $requete = "SELECT fk_target as fk_facture FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'order_supplier' AND targettype = 'invoice_supplier' AND fk_source = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='facture fournisseur';
                        if (!$filter[$type][$res->fk_facture]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_facture,'type'=>$type);
                            $filter[$type][$res->fk_facture]=1;
                        }
                    }
                    recurseElement();
                }
            break;
            case 'facture fournisseur':
                require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
                if ($eid > 0)
                {
                    $prop = new FactureFournisseur($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref_supplier."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $requete = "SELECT fk_source as fk_commande FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'order_supplier' AND targettype = 'invoice_supplier' AND fk_target = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='facture fournisseur';
                        if (!$filter[$type][$res->fk_commande]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_commande,'type'=>$type);
                            $filter[$type][$res->fk_commande]=1;
                        }
                    }
                    recurseElement();
                }
            break;
            case 'facture':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                if ($eid > 0)
                {
                    $prop = new Facture($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[".price($prop->total_ht)."]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $requete = "SELECT fk_source as fk_commande FROM " . MAIN_DB_PREFIX . "element_element as fk_facture WHERE sourcetype = 'commande' AND targettype = 'facture' AND fk_target = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='commande';
                        if (!$filter[$type][$res->fk_commande]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_commande,'type'=>$type);
                            $filter[$type][$res->fk_commande]=1;
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_facture = ".$prop->id;
                    $sql = $db->query($requete);
                    while ($res=$db->fetch_object($sql))
                    {
                        $type='propale';
                        if (!$filter[$type][$res->fk_propal]==1)
                        {
                            $arr[]=array('rowid'=>$res->fk_propal,'type'=>$type);
                            $filter[$type][$res->fk_propal]=1;
                        }
                    }
                    recurseElement();
                }

            }
            break;
            case 'projet':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                if ($eid > 0)
                {
                    $prop = new Project($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $arrConvProj['propal']='propale';
                    $arrConvProj['order']='commande';
                    $arrConvProj['invoice']='facture';
                    $arrConvProj['order_supplier']='commande fournisseur';
                    $arrConvProj['invoice_supplier']='facture fournisseur';
                    foreach(array('propal','order','invoice','order_supplier','invoice_supplier') as $key)
                    {
                        $elem = $prop->get_element_list($key);
                        if(count($elem) > 0)
                        {
                            foreach($elem as $key1=>$val)
                            {
                                $type=$arrConvProj[$key];
                                if (!$filter[$type][$val]==1)
                                {
                                    $arr[]=array('rowid'=>$val,'type'=>$type);
                                    $filter[$type][$val]=1;
                                }
                            }
                        }
                    }
                    recurseElement();
                }
            }
            break;
            case 'domain':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/domain/domain.class.php');
                if ($eid > 0)
                {
                    $prop = new Domain($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    recurseElement();
                }
            }
            break;
            case 'SSLCert':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/Babel_SSLCert/SSLCert.class.php');
                if ($eid > 0)
                {
                    $prop = new SSLCert($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    recurseElement();
                }
            }
            break;
            case 'licence':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/domain/domain.class.php');
                if ($eid > 0)
                {
                    $prop = new Licence($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    recurseElement();
                }
            }
            break;
            case 'FI':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/synopsisfichinter/class/synopsisfichinter.class.php');
                if ($eid > 0)
                {
                    $prop = new Synopsisfichinter($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $tabDI = $prop->getDI();
                    foreach($tabDI as $idDI){
                        $type='DI';
                        if (!$filter[$type][$idDI]==1)
                        {
                            $arr[]=array('rowid'=>$idDI,'type'=>$type);
                            $filter[$type][$idDI]=1;
                        }
                    }
                    
//                    $requete = "SELECT * FROM Babel_li_interv WHERE fi_refid = ".$prop->id;
//                    $sql = $db->query($requete);
//                    while ($res=$db->fetch_object($sql))
//                    {
//                        $type='DI';
//                        if (!$filter[$type][$res->di_refid]==1)
//                        {
//                            $arr[]=array('rowid'=>$res->di_refid,'type'=>$type);
//                            $filter[$type][$res->di_refid]=1;
//                        }
//                    }
                    recurseElement();
                }
            }
            break;
            case 'DI':
            {
                //Commande
                require_once(DOL_DOCUMENT_ROOT.'/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php');
                if ($eid > 0)
                {
                    $prop = new Synopsisdemandeinterv($db);
                    $prop->fetch($eid);
                    //$xml .= "<group id='".$gid."'>";
                        $xmlArr[$gid] .= '<element>';
                        $xmlArr[$gid] .= "  <rowid>".$prop->id."</rowid>";
                        $xmlArr[$gid] .= "  <ref><![CDATA[".$prop->ref."]]></ref>";
                        $xmlArr[$gid] .= "  <amount><![CDATA[]]></amount>";
                        $xmlArr[$gid] .= "  <statut><![CDATA[".$prop->getLibStatut(5)."]]></statut>";
                        $xmlArr[$gid] .= "  <soc><![CDATA[".$prop->societe->getNomUrl(1)."]]></soc>";
                        $xmlArr[$gid] .= "  <type><![CDATA[".$gid."]]></type>";
                        $xmlArr[$gid] .= '</element>';
                    insertDb($gid,$eid);
                    //$xml .= "</group>";
                    $tabFI = $prop->getFI();
                    foreach($tabFI as $idFI){
                        $type='DI';
                        if (!$filter[$type][$idFI]==1)
                        {
                            $arr[]=array('rowid'=>$idFI,'type'=>$type);
                            $filter[$type][$idFI]=1;
                        }
                    }
//                    $requete = "SELECT * FROM Babel_li_interv WHERE di_refid = ".$prop->id;
//                    $sql = $db->query($requete);
//                    while ($res=$db->fetch_object($sql))
//                    {
//                        $type='FI';
//                        if (!$filter[$type][$res->fi_refid]==1)
//                        {
//                            $arr[]=array('rowid'=>$res->fi_refid,'type'=>$type);
//                            $filter[$type][$res->fi_refid]=1;
//                        }
//                    }
                    recurseElement();
                }
            }
            break;
        }
//        print "<hr/>".$db->lasterrno.":".$db->lasterror . "<br/>".$db->lastqueryerror."<hr/>";


    }

    function insertDb($gid,$eid)
    {
        global $user, $affaireId,$db;
        $requete = "INSERT INTO `" . MAIN_DB_PREFIX . "Synopsis_Affaire_Element`
                                (`type`,`element_id`,`datea`,`fk_author`,`affaire_refid`)
                         VALUES
                                ('".$gid."', ".$eid.", now(), ".$user->id.", ".$affaireId.")";
        $sql = $db->query($requete);
        if ($sql)
        {
            return (true);
        } else {
            return false;
        }

    }

?>
