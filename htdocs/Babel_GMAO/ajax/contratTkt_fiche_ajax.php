<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 11 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : contratTkt_fiche_ajax.php
  * GLE-1.2
  */


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/Babel_GMAO/contratTicket.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");
//var_dump($_REQUEST);
// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$contratid,'contrat');


$xml="<ajax-response>";

 $action = $_REQUEST['action'];
$lineId = $_REQUEST['lineId'];

$date_start_update='';
$date_end_update='';
$date_start_real_update='';
$date_end_real_update='';
if ($_REQUEST['dateDeb'])
{
    if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['dateDeb'],$arr))
    {
        $date_start_update = $arr[3]."-".$arr[2]."-".$arr[1];
    }
} else {
    $date_start_update="";
}
if ($_REQUEST['dateFin'])
{
    if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['dateFin'],$arr))
    {
        $date_end_update = $arr[3]."-".$arr[2]."-".$arr[1];
    } else {
        $date_end_update = "";
    }
} else {
    $date_end_update="";
}
if ($_REQUEST['dateDebConf'])
{
    if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['dateDebConf'],$arr))
    {
        $date_start_real_update = $arr[3]."-".$arr[2]."-".$arr[1];
    }
} else {
    $date_start_real_update="";
}
if ($_REQUEST['dateFinConf'])
{
    if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['dateFinConf'],$arr))
    {
        $date_end_real_update = $arr[3]."-".$arr[2]."-".$arr[1];
    }
} else {
    $date_end_real_update="";
}


 switch($action)
 {

    case 'chgSrvAction':
        $newSrc = $_REQUEST['Link'];
        $contTmp = new Contrat($db);
        $contTmp->fetch($_REQUEST['id']);
        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat set linkedTo='".$newSrc."' WHERE rowid =".$_REQUEST['id'];
        $resql = $db->query($requete);
    break;

    default:
        if ($_REQUEST["mode"]=='predefined')
        {
            $date_start='';
            $date_end='';
            if ($_REQUEST["date_startmonth"] && $_REQUEST["date_startday"] && $_REQUEST["date_startyear"])
            {
                $date_start=dol_mktime(12, 0 , 0, $_REQUEST["date_startmonth"], $_REQUEST["date_startday"], $_REQUEST["date_startyear"]);
            }
            if ($_REQUEST["date_endmonth"] && $_REQUEST["date_endday"] && $_REQUEST["date_endyear"])
            {
                $date_end=dol_mktime(12, 0 , 0, $_REQUEST["date_endmonth"], $_REQUEST["date_endday"], $_REQUEST["date_endyear"]);
            }
        }

        // Si ajout champ produit libre
        if ($_REQUEST["mode"]=='libre')
        {
            $date_start_sl='';
            $date_end_sl='';
            if ($_REQUEST["date_start_slmonth"] && $_REQUEST["date_start_slday"] && $_REQUEST["date_start_slyear"])
            {
                $date_start_sl=dol_mktime(12, 0 , 0, $_REQUEST["date_start_slmonth"], $_REQUEST["date_start_slday"], $_REQUEST["date_start_slyear"]);
            }
            if ($_REQUEST["date_end_slmonth"] && $_REQUEST["date_end_slday"] && $_REQUEST["date_end_slyear"])
            {
                $date_end_sl=dol_mktime(12, 0 , 0, $_REQUEST["date_end_slmonth"], $_REQUEST["date_end_slday"], $_REQUEST["date_end_slyear"]);
            }
        }

    break;
    case 'configure':
        $id = $_REQUEST['id'];
        //1 On update le contrat
        $db->begin();
        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET fournisseur_refid = '".$_REQUEST['fournisseur']."' ,
                                           cessionnaire_refid= '".$_REQUEST['cessionnaire']."',
                                           tva_tx = '".$_REQUEST['Linetva_tx']."'
                     WHERE rowid = ".$id;
        $sql = $db->query($requete);
        $duree = $_REQUEST['duree'];
        $period = $_REQUEST['period'];
        $echu = ($_REQUEST['echu']."x" !="x"?0:1);
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratGA.class.php');
        $contrat = new ContratGA($db);
        $contrat->fetch($id);
        $contrat->fetch_lines();

        $client_signataire_refid = $contrat->client_signataire_refid;

        if ($sql)
        {
            $requete = "DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_product is null AND fk_contrat = ".$id;
            $sql = $db->query($requete);
            if (!$sql)
            {
                $xml .= "<KO>KO</KO>";
                $db->rollback();
                break;
            }
            $sql1= false;
            foreach($_REQUEST as $key => $val)
            {
                if (preg_match('/design-([0-9]*)/',$key,$arr))
                {
                    $idx = $arr[1];
                    $total = $_REQUEST['total-'.$idx];
                    $ttc = preg_replace('/,/','.', (1 + $_REQUEST['Linetva_tx']/100)*$total) ;
                    $tva = preg_replace('/,/','.', ($_REQUEST['Linetva_tx']/100)*$total);
                    $marge = preg_replace('/,/','.',$_REQUEST['marge-'.$idx]);
                    $taux = preg_replace('/,/','.',$_REQUEST['taux-'.$idx]);
                    $desc = $val;
                    $lineOrder = 0;
                    $requete = "SELECT max(line_order) as mx FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$id;
                    $sql = $db->query($requete);
                    $res=$db->fetch_object($sql);
                    $lineOrder = $res->mx + 1;
                    if ($total > 0)
                    {
                       $res = $contrat->addline($desc,  $total, 1, $_REQUEST['Linetva_tx'], 0, 0, $contrat->date_contrat, "", 'HT', $ttc, 0);
                       $contratdet_refid = $contrat->newContractLigneId;
                       $requete = " INSERT INTO Babel_GA_contrat (contratdet_refid,
                                                                    isTx0,
                                                                    montantTotHTAFinancer,
                                                                    tauxMarge,
                                                                    tauxFinancement,
                                                                    financement_period_refid,
                                                                    echu,
                                                                    client_signataire_refid,
                                                                    duree) VALUES ( ".$contratdet_refid." ,
                                                                     0 ,
                                                                     '".$total."' ,
                                                                     '".$marge."' ,
                                                                     '".$taux."' ,
                                                                     ".$period." ,
                                                                     ".$echu." ,
                                                                     ".$client_signataire_refid." ,
                                                                     ".$duree." )";
                       $res1  = $db->query($requete);
                       if ($res >0 && $res1 )
                       {
                        $sql1 = true;
                       } else if ($res<0)
                       {
                        $sql1  = false;
                        break;
                       }
                    }
                }
            }


            if ($sql1)
            {
                $db->commit();
                $xml .= "<OK>OK</OK>";
            } else {
                $db->rollback();
                $xml .= "<KO>KO</KO>";
            }
        } else{
            $db->rollback();
            $xml .= "<KO>KO</KO>";
        }
    break;
    case 'add':
    {
        $datecontrat = dol_mktime(12, 0 , 0, $_REQUEST["remonth"], $_REQUEST["reday"], $_REQUEST["reyear"]);

        $contrat = new Contrat($db);

        $contrat->socid         = $_REQUEST["socid"];
        $contrat->date_contrat   = $datecontrat;

        $contrat->commercial_suivi_id      = $_REQUEST["commercial_suivi_id"];
        $contrat->commercial_signature_id  = $_REQUEST["commercial_signature_id"];

        $contrat->note           = trim($_REQUEST["note"]);
        $contrat->projetid       = trim($_REQUEST["projetid"]);
        $contrat->remise_percent = trim($_REQUEST["remise_percent"]);
        $contrat->ref            = $contrat->getNextNumRef("");
        $contrat->linkedTo       = trim($_REQUEST['Link']);

        $result = $contrat->create($user,$langs,$conf);
        if ($result > 0)
        {
//TODO
            Header("Location: fiche.php?id=".$contrat->id);
            exit;
        }
        else {
            $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
        }
        $_REQUEST["socid"]=$_REQUEST["socid"];
        $_REQUEST["action"]='create';
        $action = '';

    }
    break;

    case 'addligne':
    //TODO droit ?
        if ($user->rights->contrat->creer)
        {
            //TODO 2 cas :
            //Seulement la quantité
            //quantité + id prod
            if ($_REQUEST["pqty"] && $_REQUEST["p_idprod"])
            {
                $contrat = new ContratTicket($db);
                $ret=$contrat->fetch($_REQUEST["id"]);
                if ($ret < 0)
                {
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
                $ret=$contrat->fetch_client();
                $tva_tx = $_REQUEST['Linetva_tx'];
                $date_start='';
                $date_end='';

                $date_start = strtotime($date_start_update);
                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';

                // Ecrase $pu par celui du produit
                // Ecrase $desc par celui du produit
                // Ecrase $txtva par celui du produit
                // Ecrase $base_price_type par celui du produit
                if ($_REQUEST['p_idprod'])
                {
                    $prod = new Product($db, $_REQUEST['p_idprod']);
                    $prod->fetch($_REQUEST['p_idprod']);

                    $tva_tx = get_default_tva($mysoc,$contrat->client,$prod->tva_tx);

                    $tva_npr = get_default_npr($mysoc,$contrat->client,$prod->tva_npr);

                    // On defini prix unitaire
                    if ($conf->global->PRODUIT_MULTIPRICES == 1)
                    {
                        $pu_ht = $prod->multiprices[$contrat->client->price_level];
                        $pu_ttc = $prod->multiprices_ttc[$contrat->client->price_level];
                        $price_base_type = $prod->multiprices_base_type[$contrat->client->price_level];
                    } else {
                        $pu_ht = $prod->price;
                        $pu_ttc = $prod->price_ttc;
                        $price_base_type = $prod->price_base_type;

                    }

                    // On reevalue prix selon taux tva car taux tva transaction peut etre different
                    // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
                    if ($tva_tx != $prod->tva_tx)
                    {
                        if ($price_base_type != 'HT' && $price_base_type."x" != 'x')
                        {
                            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
                        } else {

                            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
                        }
                    }


                } else {
                    $pu_ht=$_REQUEST['pu'];
                    $tva_tx=preg_replace('/\*/i','',$_REQUEST['Linetva_tx']);
                    $tva_npr=preg_match('/\*/i',$_REQUEST['Linetva_tx'])?1:0;
                }
//                $desc = $_REQUEST['desc'];
                $info_bits=0;
                if ($tva_npr) $info_bits |= 0x01;

                // Insert line
                $result = $contrat->addline(
                        htmlentities(utf8_decode($desc)),
                        $pu_ht,
                        $_REQUEST["pqty"],
                        $tva_tx,
                        $_REQUEST["p_idprod"],
                        ($_REQUEST["premise"]>0?$_REQUEST["premise"]:0),
                        $date_start,
                        $date_end,
                        $price_base_type,
                        $pu_ttc,
                        $info_bits
                        );
                if ($result > 0)
                {
                    $sla = "";
                    $reconducationAuto=0;
                    $durVal = $_REQUEST['addDur'];
                    $requete="INSERT INTO Babel_GMAO_contratdet_prop (tms,contratdet_refid,durValid,DateDeb,fk_prod,reconductionAuto,qte,SLA)
                                VALUES (now(),".$contrat->newContractLigneId.",".$durVal.",'".date('Y-m-d',$date_start)."',".($_REQUEST["p_idprod"]>0?$_REQUEST["p_idprod"]:"NULL").",".$reconducationAuto.",".$_REQUEST['pqty'].",'".$sla."')";
                    $result1 = $db->query($requete);

                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[<div>".returnNewLine($contrat->newContractLigneId,$_REQUEST['p_idprod'])."</div>]]></OKtext>";

                } else {
//                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
            } else {
                //contrat Ticktsansref à un contrat
                $contrat = new ContratTicket($db);
                $ret=$contrat->fetch($_REQUEST["id"]);
                if ($ret < 0)
                {
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
                $ret=$contrat->fetch_client();
                $tva_tx = $_REQUEST['tva_tx'];
                $date_start='';
                $date_end='';

                $date_start = strtotime($date_start_update);
                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';

                $pu_ht=$_REQUEST['pu_ht'];
                $tva_tx=preg_replace('/\*/i','',$_REQUEST['tva_tx']);
                $tva_npr=preg_match('/\*/i',$_REQUEST['tva_tx'])?1:0;

                $desc = "Tickets de support";
                $info_bits=0;
                if ($tva_npr) $info_bits |= 0x01;

                // Insert line
                $result = $contrat->addline(
                        htmlentities(utf8_decode($desc)),
                        $pu_ht,
                        $_REQUEST["pqty"],
                        $tva_tx,
                        "",
                        0,
                        $date_start,
                        $date_end,
                        $price_base_type,
                        $pu_ttc,
                        $info_bits
                        );
//TODO Modify Prop

                if ($result > 0)
                {
                    $sla = "";
                    $reconducationAuto=0;
                    $durVal = $_REQUEST['addDur'];
                    $requete="INSERT INTO Babel_GMAO_contratdet_prop (tms,contratdet_refid,durValid,DateDeb,fk_prod,reconductionAuto,qte,SLA)
                                VALUES (now(),".$contrat->newContractLigneId.",".$durVal.",'".date('Y-m-d',$date_start)."',".($_REQUEST["p_idprod"]>0?$_REQUEST["p_idprod"]:"NULL").",".$reconducationAuto.",".$_REQUEST['pqty'].",'".$sla."')";
                    $result1 = $db->query($requete);
                    $contrat->updateProp();
                            $xml .= "<OK>OK</OK>";
                            $xml .= "<OKtext><![CDATA[<div>".returnNewLine($contrat->newContractLigneId,$_REQUEST['p_idprod'])."</div>]]></OKtext>";
                } else {
//                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
            }
        }
    break;
    case 'modligne':
            if ($_REQUEST["pqty"] && $_REQUEST["p_idprod"] > 0)
            {
                $contrat = new ContratTicket($db);
                $ret=$contrat->fetch($_REQUEST["id"]);
                if ($ret < 0)
                {
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
                $ret=$contrat->fetch_client();
                $tva_tx = $_REQUEST['Linetva_tx'];
                $date_start='';
                $date_end='';

                $date_start = strtotime($date_start_update);
                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';

                $desc = "Tickets divers";

                // Ecrase $pu par celui du produit
                // Ecrase $desc par celui du produit
                // Ecrase $txtva par celui du produit
                // Ecrase $base_price_type par celui du produit
                if ($_REQUEST['p_idprod']>0)
                {
                    $prod = new Product($db, $_REQUEST['p_idprod']);
                    $prod->fetch($_REQUEST['p_idprod']);
                    $desc = $prod->libelle;
                    $tva_tx = get_default_tva($mysoc,$contrat->client,$prod->tva_tx);

                    $tva_npr = get_default_npr($mysoc,$contrat->client,$prod->tva_npr);

                    // On defini prix unitaire
                    if ($conf->global->PRODUIT_MULTIPRICES == 1)
                    {
                        $pu_ht = $prod->multiprices[$contrat->client->price_level];
                        $pu_ttc = $prod->multiprices_ttc[$contrat->client->price_level];
                        $price_base_type = $prod->multiprices_base_type[$contrat->client->price_level];
                    } else {
                        $pu_ht = $prod->price;
                        $pu_ttc = $prod->price_ttc;
                        $price_base_type = $prod->price_base_type;

                    }

                    // On reevalue prix selon taux tva car taux tva transaction peut etre different
                    // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
                    if ($tva_tx != $prod->tva_tx)
                    {
                        if ($price_base_type != 'HT' && $price_base_type."x" != 'x')
                        {
                            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
                        } else {

                            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
                        }
                    }


                } else {
                    $pu_ht=$_REQUEST['pu'];
                    $tva_tx=preg_replace('/\*/i','',$_REQUEST['Linetva_tx']);
                    $tva_npr=preg_match('/\*/i',$_REQUEST['Linetva_tx'])?1:0;
                }
                $info_bits=0;
                if ($tva_npr) $info_bits |= 0x01;

                // Insert line

//updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
//         $date_start='', $date_end='', $tvatx,
//         $date_debut_reel='', $date_fin_reel='')

                $result = $contrat->updateline(
                        $lineId,
                        htmlentities(utf8_decode($desc)),
                        $pu_ht,
                        $_REQUEST["pqty"],
                        ($_REQUEST["premise"]>0?$_REQUEST["premise"]:0),
                        $date_start,
                        $date_end,
                        $tva_tx,'','',
                        ($_REQUEST["p_idprod"]>0?$_REQUEST["p_idprod"]:0)
                        );

//TODO update contratProp et contratDetProp
                    $sla = "";
                    $reconducationAuto=0;
                    $durVal = $_REQUEST['modDur'];
                    $requete="UPDATE Babel_GMAO_contratdet_prop
                                 SET durValid=".$durVal.",
                                     DateDeb = '".date('Y-m-d',$date_start)."',
                                     fk_prod = ".($_REQUEST["p_idprod"]>0?$_REQUEST["p_idprod"]:"NULL").",
                                     reconductionAuto = ".$reconducationAuto.",
                                     qte = ".$_REQUEST['pqty'].",
                                     SLA = '".$sla."'
                                WHERE contratdet_refid=".$lineId."";
                    $result1 = $db->query($requete);
                    $contrat->updateProp();
                if ($result > 0)
                {
                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[<div>".returnNewLine($lineId,$_REQUEST['p_idprod'])."</div>]]></OKtext>";
                } else {
//                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
            } else {
                //contrat Ticktsansref à un contrat
                $contrat = new ContratTicket($db);
                $ret=$contrat->fetch($_REQUEST["id"]);
                if ($ret < 0)
                {
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
                $ret=$contrat->fetch_client();
                $tva_tx = $_REQUEST['tva_tx'];
                $date_start='';
                $date_end='';

                $date_start = strtotime($date_start_update);
                $date_end = strtotime($date_end_update);
                $price_base_type = 'HT';

                $pu_ht=$_REQUEST['pu_ht'];
                $tva_tx=preg_replace('/\*/i','',$_REQUEST['tva_tx']);
                $tva_npr=preg_match('/\*/i',$_REQUEST['tva_tx'])?1:0;

                $desc = "Tickets divers";
                $info_bits=0;
                if ($tva_npr) $info_bits |= 0x01;

                // Insert line
                $result = $contrat->updateline(
                        $lineId,
                        htmlentities(utf8_decode($desc)),
                        $pu_ht,
                        $_REQUEST["pqty"],
                        ($_REQUEST["premise"]>0?$_REQUEST["premise"]:0),
                        $date_start,
                        $date_end,
                        $tva_tx,
                        0
                        );
//TODO update contratProp et contratDetProp
//TODO update contratProp et contratDetProp
                    $sla = "";
                    $reconducationAuto=0;
                    $durVal = $_REQUEST['modDur'];
                    $requete="UPDATE Babel_GMAO_contratdet_prop
                                 SET durValid=".$durVal.",
                                     DateDeb = '".date('Y-m-d',$date_start)."',
                                     fk_prod = ".($_REQUEST["p_idprod"]>0?$_REQUEST["p_idprod"]:"NULL").",
                                     reconductionAuto = ".$reconducationAuto.",
                                     qte = ".$_REQUEST['pqty'].",
                                     SLA = '".$sla."'
                                WHERE contratdet_refid=".$lineId."";
                    $result1 = $db->query($requete);
                    $contrat->updateProp();


                if ($result > 0)
                {
                    $xml .= "<OK>OK</OK>";
                    $xml .= "<OKtext><![CDATA[<div>".returnNewLine($lineId,$_REQUEST['p_idprod'])."</div>]]></OKtext>";
                } else {
//                    $mesg='<div class="error ui-state-error">'.$contrat->error.'</div>';
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';
                }
            }
    break;
    case 'getStatut':
    {

        $contrat = new Contrat($db);
        $contrat->id = $_REQUEST["id"];
        if ($contrat->fetch($_REQUEST["id"]))
        {
            $contrat->fetch_lines();

            $xml.="<srvPanel>";
            $xml .= "<![CDATA[<div>";
            if ($contrat->statut==0) $xml .= $contrat->getLibStatut(2);
            else $xml .= $contrat->getLibStatut(4);
            $xml .= "</div>]]>";
            $xml.="</srvPanel>";
        } else {
            $xml.="<srvPanel>";
            $xml .= "<![CDATA[";
            $xml .= "Erreur de chargement du contrat";
            $xml .= "]]>";
            $xml.="</srvPanel>";
        }

    }
    break;
    case 'closeLine':
    {
        $ligne = $_REQUEST["lineid"];
        $contrat = new Contrat($db);
        if ($contrat->fetch($_REQUEST["id"]))
        {
            $result = $contrat->close_line($user, $_REQUEST["lineid"],time());
            if ($result>0)
            {
                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[".returnNewLine($_REQUEST["lineid"])."]]></OKtext>";
            } else {
                $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
            }
        } else {
           $xml .= '<KO>Erreur lors de l\'activation</KO>';
        }
    }
    break;
    case 'unactivateLine':
    {
        $ligne = $_REQUEST["lineid"];
        $contrat = new Contrat($db);
        if ($contrat->fetch($_REQUEST["id"]))
        {
            $result = $contrat->cancel_line($user, $_REQUEST["lineid"]);
            if ($result>0)
            {
                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[".returnNewLine($_REQUEST["lineid"])."]]></OKtext>";
            } else {
                $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
            }
        } else {
           $xml .= '<KO>Erreur lors de l\'activation</KO>';
        }
    }
    break;

    case 'activateLine':
    {
        $ligne = $_REQUEST["lineid"];
        $contrat = new Contrat($db);
        if ($contrat->fetch($_REQUEST["id"]))
        {
            $result = $contrat->active_line($user, $ligne, strtotime($date_start_real_update), strtotime($date_end_real_update));
            if ($result>0)
            {
                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[".returnNewLine($_REQUEST["lineid"])."]]></OKtext>";
            } else {
                $xml .= '<KO>La ligne à mettre à jour n\'existe pas</KO>';
            }
        } else {
           $xml .= '<KO>Erreur lors de l\'activation</KO>';
        }
    }
    break;
    case 'deleteline':
        if ($user->rights->contrat->creer)
        {
            $contrat = new Contrat($db);
            $contrat->fetch($_REQUEST["id"]);
        //Modif Babel post 1.0
            $requete = "DELETE FROM Babel_financement
                              WHERE fk_contratdet =".$_REQUEST["lineid"];
            $db->query($requete);
        //fin modif

            $result = $contrat->delete_line($_REQUEST["lineid"]);

            if ($result >= 0)
            {
                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[]]></OKtext>";
            } else {
//                $mesg=$contrat->error;
                    $xml .= '<KO><![CDATA['.$db->lastqueryerror() ."\n".$db->lasterror().'\n'.$db->lasterrno.'\n'.$db->errno.'\n'.$db->error.']]></KO>';

            }
        }

    break;

    //New
    case 'getLineDet':
    {
        $idContrat=$_REQUEST['idContrat'];
        $idLigne = $_REQUEST['idLigneContrat'];
        $requete = "SELECT statut,
                                   label,
                                   description,
                                   date_commande,
                                   date_format(date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                                   date_format(date_ouverture,'%d/%m/%Y') as date_ouverture,
                                   date_format(date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                                   date_format(date_cloture,'%d/%m/%Y') as date_cloture,
                                   tva_tx,
                                   qty,
                                   fk_product,
                                   remise_percent,
                                   subprice,
                                   price_ht,
                                   total_ht,
                                   total_tva,
                                   total_ttc,
                                   fk_user_author,
                                   fk_user_ouverture,
                                   fk_user_cloture,
                                   commentaire,
                                   line_order
                              FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$idLigne." ORDER BY line_order, rowid";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $xml.='<fk_product><![CDATA['.$res->fk_product.']]></fk_product>';
            if ($res->fk_product > 0)
            {
                $requete = "SELECT *
                              FROM llx_product
                             WHERE rowid = ".$res->fk_product;
                $sql1 = $db->query($requete);
                $res1 = $db->fetch_object($sql1);
                $xml .= "<libelleProduit>".$res1->label."</libelleProduit>";
                $xml .= "<qtyTkt>".$res1->qte."</qtyTkt>";
                $xml .= "<descriptionProduit>".$res1->description."</descriptionProduit>";
            }
            $requete = "SELECT durValid, qte,
                               date_format(DateDeb,'%d/%m/%Y') as DateDeb,
                               date_format(date_add(DateDeb,INTERVAL durValid MONTH),'%d/%m/%Y') as DateFin
                          FROM Babel_GMAO_contratdet_prop
                         WHERE contratdet_refid =".$idLigne;
            $sql2 = $db->query($requete);
            $res2=$db->fetch_object($sql2);
            $xml .= "<durValid>".$res2->durValid."</durValid>";
            $xml.='<date_ouverture_prevue><![CDATA['.$res2->DateDeb.']]></date_ouverture_prevue>';
            $xml.='<date_ouverture><![CDATA['.$res2->DateDeb.']]></date_ouverture>';
            $xml.='<date_fin_validite><![CDATA['.$res2->DateFin.']]></date_fin_validite>';
            $xml.='<date_cloture><![CDATA['.$res2->DateFin.']]></date_cloture>';
            $xml.='<qty><![CDATA['.$res2->qte.']]></qty>';

            $xml.='<statut><![CDATA['.$res->statut.']]></statut>';
            $xml.='<label><![CDATA['.$res->label.']]></label>';
            $xml.='<description><![CDATA['.$res->description.']]></description>';
            $xml.='<date_commande><![CDATA['.$res->date_commande.']]></date_commande>';
            $xml.='<tva_tx><![CDATA['.$res->tva_tx.']]></tva_tx>';
            $xml.='<remise_percent><![CDATA['.$res->remise_percent.']]></remise_percent>';
            $xml.='<subprice><![CDATA['.$res->subprice.']]></subprice>';
            $xml.='<price_ht><![CDATA['.$res->price_ht.']]></price_ht>';
            $xml.='<total_ht><![CDATA['.$res->total_ht.']]></total_ht>';
            $xml.='<total_tva><![CDATA['.$res->total_tva.']]></total_tva>';
            $xml.='<total_ttc><![CDATA['.$res->total_ttc.']]></total_ttc>';
            $xml.='<fk_user_author><![CDATA['.$res->fk_user_author.']]></fk_user_author>';
            $xml.='<fk_user_ouverture><![CDATA['.$res->fk_user_ouverture.']]></fk_user_ouverture>';
            $xml.='<fk_user_cloture><![CDATA['.$res->fk_user_cloture.']]></fk_user_cloture>';
            $xml.='<commentaire><![CDATA['.$res->commentaire.']]></commentaire>';
            $xml.='<line_order><![CDATA['.$res->line_order.']]></line_order>';

            $requete1 = "SELECT Babel_financement.id as fid,
                                Babel_financement_period.Description,
                                Babel_financement.taux,
                                Babel_financement.tauxAchat,
                                Babel_financement.duree,
                                Babel_financement.financement_period_refid as fpid
                           FROM Babel_financement,
                                Babel_financement_period
                          WHERE Babel_financement.financement_period_refid = Babel_financement_period.id AND fk_contratdet = ".$idLigne;
                          //print $requete1;
            $sql1 = $db->query($requete1);
            if ($sql1  )
            {
                $res1 = $db->fetch_object($sql1);
                if ($res1->fpid . "x" != "x")
                {
                    $xml .= '<financement_id>'. $res1->fpid .'</financement_id>';
                    $xml .= '<taux>'. $res1->taux  .'</taux>';
                    $xml .= '<duree>'. $res1->duree  .'</duree>';
                    $xml .= '<tauxAchat>'. $res1->tauxAchat  .'</tauxAchat>';
                    $xml .= '<financement_period>'. $res1->Description  .'</financement_period>';
                    $xml .= '<financement_period_id>'. $res1->fpid  .'</financement_period_id>';
                } else {
                    $xml .= '<taux/>';
                    $xml .= '<tauxAchat/>';
                    $xml .= '<duree/>';
                    $xml .= '<financement_period/>';
                }
            } else {
                $xml .= '<taux/>';
                $xml .= '<tauxAchat/>';
                $xml .= '<duree/>';
                $xml .= '<financement_period/>';
            }


        }

    }
    break;
    case 'sortLine':
    {
        $dataRaw = $_REQUEST['data'];
        $data = explode(',',$dataRaw);
        $return = true;
        foreach($data as $key=>$val)
        {

            $newOrder = $key + 1;
            $requete = ' UPDATE ".MAIN_DB_PREFIX."contratdet  SET line_order = '. $newOrder . '  WHERE rowid = '.$val;
            $sql = $db->query($requete);
            if (!$sql){
                $return=false;
                break;
            }
        }
        if ($return)
        {
            $xml .= "<OK>OK</OK>";
        } else {
            $xml .= "<KO><![CDATA[".$db->lastqueryerror . "\n ". $db->lasterror ."]]></KO>";
        }

    }
    break;
    case 'addligneGA':
    {
        $fk_product = $_REQUEST['fk_product'];
        $qty = $_REQUEST['qty'];
        $contratId = $_REQUEST['id'];
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratGA.class.php');
        $contrat = new ContratGA($db);
        $contrat->fetch($contratId);
        $prod = new Product($db);
        $prod->fetch($fk_product);
        $res = $contrat->addline($prod->libelle, 0, $qty, 11, $fk_product, 0, $contrat->date_contrat, "", 'HT', 0, 0);


        if ($res)
        {
            $xml .= "<OK>OK</OK>";
            $xml .= "<OKtext><![CDATA[";
            $xml .= "<tr><th  width=80% align=left style='padding-left: 15px;' class='ui-widget-content'>";
            $xml .= $prod->getNomUrl(1)." ".$prod->libelle;
            $xml .= "<th class='ui-widget-content'>Qt&eacute;";
            $xml .= "<td class='ui-widget-content' id='qteText' align='center'>";
            $xml .= "<table style=''>
                       <tr><td rowspan=3 width=32><span>".$qty."</span>";
            if($contrat->statut==0)
            {
                $xml.=            "<tr><td width=16 id=".$contrat->newContractLigneId." align='center' class='plus ui-widget-header'><span style='padding:0; margin: -3px; margin-bottom: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-n'></span>
                                    <tr><td width=16 id=".$contrat->newContractLigneId." align='center' class='moins ui-widget-header'><span style='padding:0; margin: -3px; margin-top: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-s'></span>";
            }
            $xml.=        "</table>";
            if($contrat->statut==0)
            {
                $xml.= "     <td align=center style='padding-left: 15px' class='ui-widget-content'><span class='deleteGA'  style='cursor: pointer;' id='".$contrat->newContractLigneId."'>" . img_delete()."</span>";
            }


            $xml .= "]]></OKtext>";
        } else {
            $xml .= "<KO><![CDATA[".$db->lastqueryerror . "\n ". $db->lasterror ."]]></KO>";
        }
    }
    break;
    case 'subQty':
    {
        $requete = "SELECT qty FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $qty = $res->qty;
        if ($qty > 1)
        {
            $qty --;
        }

        $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet SET qty=".$qty." WHERE rowid =".$_REQUEST['lineId'];
        $db->query($requete);
        $xml .= "<OK>OK</OK>";
        $xml .= "<OKtext><![CDATA[";
        $xml .= $qty;
        $xml .= "]]></OKtext>";

    }
    break;
    case 'addQty':
    {
        $requete = "SELECT qty FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $qty = ($res->qty.'x'=='x'?1:$res->qty);
        $qty ++;

        $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet SET qty=".$qty." WHERE rowid =".$_REQUEST['lineId'];
        $db->query($requete);
        $xml .= "<OK>OK</OK>";
        $xml .= "<OKtext><![CDATA[";
        $xml .= $qty;
        $xml .= "]]></OKtext>";

    }
    break;
    case 'delGAline':
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<OK>OK</OK>";
        } else {
            $xml .= "<KO><![CDATA[".$db->lastqueryerror . "\n ". $db->lasterror ."]]></KO>";
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


function returnNewLine($plineId,$isProd=false)
{
    global $db, $conf, $langs, $user;
    $contratline = new ContratLigne($db);
    $contratline->fetch($plineId);
    //$contratline->fk_product=$_REQUEST['p_idprod'];
    $contrat = new ContratTicket($db);
    $contrat->id = $contratline->fk_contrat;
    $contrat->fetch($contratline->fk_contrat);
    $contratline->fk_product=$_REQUEST['p_idprod'];
    //var_dump($contratline);
    //TODO not correctly load
    $txt = $contrat->display1line($contratline);


    return ($txt);
}


?>