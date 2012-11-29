<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 19 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : addProdToContrat-xml_response.php
  * GLE-1.2
  */
$resXml="";
require_once('../../../main.inc.php');
//id="+comId+"&contratId="+jQuery('#fk_contrat').find(':selected').val()+"&prodId="+pId+"&comLigneId="+ligneId
$id= $_REQUEST['id'];
$contratId = $_REQUEST['contratId'];
$prodId = $_REQUEST['prodId'];
$comLigneId = $_REQUEST['comLigneId'];
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
$com = new Synopsis_Commande($db);
$com->fetch($id);
$requete = "SELECT * FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid = ".$comLigneId;

$sql = $db->query($requete);
$res = $db->fetch_object($sql);
$total_tva = preg_replace('/,/','.',0.196 * $res->subprice);
$total_ttc = preg_replace('/,/','.',1.196 * $res->subprice);
$qty = $res->qty;
$sql = false;
for($i=0;$i<$qty;$i++)
{
    $line0 = 0;
    $requete = "SELECT max(line_order) + 1 as mx
                  FROM ".MAIN_DB_PREFIX."contratdet
                 WHERE fk_contrat = ".$contratId;
    $sql1 = $db->query($requete);
    $res1 = $db->fetch_object($sql1);
    $lineO = ($res1->mx>0?$res1->mx:1);

    $tmpProd = new Product($db);
    $tmpProd->fetch($res->fk_product);

    $avenant = 'NULL';
    //SI contrat statut > 0 => avenant pas NULL
    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."contrat
                 WHERE rowid = ".$contratId;
    $sql2 = $db->query($requete);
    $res2 = $db->fetch_object($sql2);
    if ($res2->statut > 0)
    {
        $avenant=0;
        //avenant en cours ou pas ?
        $requete = "SELECT max(avenant) + 1 as mx
                      FROM ".MAIN_DB_PREFIX."contratdet
                     WHERE fk_contrat=".$contratId."
                       AND statut > 0";
        $sql3 = $db->query($requete);
        $res3 = $db->fetch_object($sql3);
        $avenant= $res3->mx;
    }

    $requete = "INSERT INTO ".MAIN_DB_PREFIX."contratdet
                            (fk_contrat,fk_product,statut,description,
                             tva_tx,qty,subprice,price_ht,
                             total_ht, total_tva, total_ttc,fk_user_author,
                             line_order,fk_commande_ligne,avenant,date_ouverture_prevue,date_ouverture, date_fin_validite)
                     VALUES (". $contratId .",NULL,0,'Import depuis la commande ".$com->ref."',
                             19.6,1,".$res->subprice.",".$res->subprice.",
                             ".$res->subprice.",".$total_tva.",".$total_ttc.",".$user->id.",
                             ".$lineO.",".$comLigneId.",".$avenant.",now(),now(), date_add(now(),INTERVAL ".($tmpProd->durVal>0?$tmpProd->durVal:0)." MONTH))";
    $sql = $db->query($requete);
    $cdid = $db->last_insert_id('".MAIN_DB_PREFIX."contratdet');

    //Mode de reglement et condition de reglement
    if ($res2->condReg_refid != $com->cond_reglement_id || $res2->modeReg_refid != $com->mode_reglement_id )
    {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('NOTIFY_ORDER_CHANGE_CONTRAT_MODE_REG',$com,$user,$langs,$conf);
        if ($result < 0) { $error++; $errors=$interface->errors; }
        // Fin appel triggers
    }

$isMnt = false;
$isSAV = false;
$isTkt = false;
$qte=1;
if ($tmpProd->Hotline>0 || $tmpProd->TeleMaintenance > 0 || $tmpProd->Maintenance > 0)
{
    $isMnt = true;
    $qte=$tmpProd->VisiteSurSite;
}else if ($tmpProd->isSAV >0)
{
    $isSAV=true;
} else if ($tmpProd->qte > 0)
{
    $isTkt = true;
    $qte = $tmpProd->qte;
}


    //Lier a contratdetprop
    //Babel_GMAO_contratdet_prop
    $requete = "INSERT INTO Babel_GMAO_contratdet_prop
                            (contratdet_refid,fk_contrat_prod,qte,tms,DateDeb,reconductionAuto,
                            isSAV, SLA, durValid,
                            hotline, telemaintenance, maintenance,
                            type )
                     VALUES (".$cdid.",".$res->fk_product.",".$qte.",now(),now(),0,
                            ".($tmpProd->isSAV>0?$tmpProd->isSAV:0).",'".addslashes($tmpProd->SLA)."',".($tmpProd->durValid>0?$tmpProd->durValid:0).",
                            ".($tmpProd->Hotline>0?$tmpProd->Hotline:0).",".($tmpProd->TeleMaintenance>0?$tmpProd->TeleMaintenance:0).",".($tmpProd->Maintenance>0?$tmpProd->Maintenance:0).",
                            ".($isMnt?3:($isSAV?4:($isTkt?2:0))).")";
    $sql1 = $db->query($requete);
//print $requete;

    //lier a contratProp normalement OK??
    //Babel_GMAO_contrat_prop
}
if ($sql)
{
    $resXml = "<OK>OK</OK>";
} else {
    $resXml = "<KO>KO".$requete."</KO>";
}
if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
}
$et = ">";
print "<?xml version='1.0' encoding='utf-8'?$et\n";
print "<ajax-response>";
print $resXml;
print "</ajax-response>";

?>