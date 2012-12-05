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
  * Name : contrat_fiche_ajax.php
  * GLE-1.1
  */


/*
 * Actions
 */
//LOAD DOLI OBJ


require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

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
//var_dump($action);

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
    case 'editLine':
        $idContrat=$_REQUEST['idContrat'];
        $idLigne = $_REQUEST['ligneId'];

//log: keysearchp_idprodmod=&p_idprodmod=697&debut_loc=20%2F12%2F2010&fin_loc=30%2F12%2F2010&pu_ht=1+000%2C00&duration_value=&duration_unit=m&qte=1
        $fk_product = $_REQUEST['p_idprodmod'];
        if ($fk_product > 0 && $idLigne > 0)
        {
            $debutLoc = false;
            $finLoc = false;
            $serial = $_REQUEST['serial'];
            $subprice = price2num($_REQUEST['pu_ht']);
            $tmpProd = new Product($db);
            $tmpProd->fetch($fk_product);
            $dfltValUnit = ($tmpProd->duration_unit."x"!="x"?$tmpProd->duration_unit:"m");
            $dfltVal = ($tmpProd->duration_value."x"!="x"?$tmpProd->duration_value:"1");
            $duration_value = ($_REQUEST['duration_value']>0?$_REQUEST['duration_value']:$dfltVal);
            $duration_unit = ($_REQUEST['duration_unit']."x" != "x"?$_REQUEST['duration_unit']:$dfltValUnit);
            $qte = ($_REQUEST['qte']> 0?$_REQUEST['qte']:1);

            if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['moddebut_loc'],$arr)){
                $debutLoc=strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            } else {
                $debutLoc=$contrat->mise_en_service;
            }
            if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['modfin_loc'],$arr)){
                $finLoc=strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            } else {
                $finLoc=$contrat->date_fin_validite;
            }
            require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratLocProdGA.class.php');
            $contrat = new ContratLocProdGA($db);
            $contrat->fetch($idContrat);
            $res = $contrat->updateline($idLigne,"",$subprice,$qte,0,$debutLoc,$finLoc,19.6,'','',$fk_product,false,$duration_value.$duration_unit);
            if ($res)
            {
                $total_ht = $contrat->getTotalLigne($subprice,$debutLoc,$finLoc,$duration_value.$duration_unit,$qte);
                $contrat->update_totalLigne($idLigne,$total_ht);
                //Update serial
                $requete = "SELECT * FROM llx_product_serial_cont WHERE element_id = ".$idLigne." AND element_type='contratLoc'";
                $sql = $db->query($requete);
                if ($db->num_rows($sql) > 0)
                {
                    $res = $db->fetch_object($sql);
                    $requete = "UPDATE llx_product_serial_cont SET serial_number='".addslashes($serial)."' WHERE id = ".$res->id;
                    $sql = $db->query($requete);
                } else {
                    $requete = "INSERT INTO llx_product_serial_cont
                                            (serial_number,date_creation, element_id, tms, fk_user_author, element_type)
                                     VALUES ('".addslashes($serial)."',now(),".$idLigne.",now(),".$user->id.",'contratLoc')";
                    $sql = $db->query($requete);
                }

                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[";
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$idLigne;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $xml .= $contrat->display1Line($res);
                $xml .= "]]></OKtext>";
            } else {
                $xml .= "<KO><![CDATA[".$db->lastqueryerror . "\n ". $db->lasterror ."]]></KO>";
            }

        } else {
                $xml .= "<KO><![CDATA[Pas de produit]]></KO>";
            }


//function updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
//         $date_start='', $date_end='', $tvatx,
//         $date_debut_reel='', $date_fin_reel='',$fk_prod=false,$fk_commandedet=false)
    break;
    case "editProrata":{
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratLocProdGA.class.php');
        $contrat = new ContratLocProdGA($db);

        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET prorata = ".$_REQUEST['value']." WHERE rowid = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        $contrat->fetch($_REQUEST['id']);
        $arr[0]='P&eacute;riode commenc&eacute;e';
        $arr[1]='Prorata temporis';
        $requete = "SELECT subprice,
                           unix_timestamp(ifnull(date_ouverture,date_ouverture_prevue)) as debutLoc,
                           unix_timestamp(ifnull(date_cloture,date_fin_validite)) as finLoc,
                           prod_duree_loc,
                           qty,
                           rowid
                      FROM ".MAIN_DB_PREFIX."contratdet
                     WHERE fk_contrat = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        while($res = $db->fetch_object($sql))
        {
            $total_ht = $contrat->getTotalLigne($res->subprice,$res->debutLoc,$res->finLoc,($res->prod_duree_loc."x"=="x"?'1m':$res->prod_duree_loc),$res->qty);
            $contrat->update_totalLigne($res->rowid,$total_ht);

        }
        print $arr[$_REQUEST['value']];
    }
    break;
    case 'editFactPer':{
        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET facturation_freq = '".$_REQUEST['value']."' WHERE rowid = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        print ($_REQUEST['value']=='m'?"Mensuelle":($_REQUEST['value']=='y'?'Annuelle':($_REQUEST['value']=='t'?'Trimestrielle':'Manuelle')));

    }
    break;
    case "edittva":{
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratLocProdGA.class.php');
        $contrat = new ContratLocProdGA($db);
        $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET tva_tx = ".$_REQUEST['value']." WHERE rowid = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        $contrat->fetch($_REQUEST['id']);
        $requete = "SELECT subprice,
                           unix_timestamp(ifnull(date_ouverture,date_ouverture_prevue)) as debutLoc,
                           unix_timestamp(ifnull(date_cloture,date_fin_validite)) as finLoc,
                           prod_duree_loc,
                           qty,
                           rowid
                      FROM ".MAIN_DB_PREFIX."contratdet
                     WHERE fk_contrat = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        while($res = $db->fetch_object($sql))
        {
            $total_ht = $contrat->getTotalLigne($res->subprice,$res->debutLoc,$res->finLoc,($res->prod_duree_loc."x"=="x"?'1m':$res->prod_duree_loc),$res->qty);
            $contrat->update_totalLigne($res->rowid,$total_ht);

        }
        print price($_REQUEST['value']."%");
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
                                   commentaire,prod_duree_loc as prod_duree_loc,
                                   line_order FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$idLigne." ORDER BY line_order, rowid";
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $xml.='<fk_product><![CDATA['.$res->fk_product.']]></fk_product>';
            if ($res->fk_product > 0)
            {
                $requete = "SELECT *
                              FROM ".MAIN_DB_PREFIX."product
                             WHERE rowid = ".$res->fk_product;
                $sql1 = $db->query($requete);
                $res1 = $db->fetch_object($sql1);
                $xml .= "<libelleProduit>".$res1->label."</libelleProduit>";
                $xml .= "<descriptionProduit>".$res1->description."</descriptionProduit>";
            }
            $xml.='<statut><![CDATA['.$res->statut.']]></statut>';
            $xml.='<label><![CDATA['.$res->label.']]></label>';
            $xml.='<description><![CDATA['.$res->description.']]></description>';
            $xml.='<date_commande><![CDATA['.$res->date_commande.']]></date_commande>';
            $xml.='<date_ouverture_prevue><![CDATA['.$res->date_ouverture_prevue.']]></date_ouverture_prevue>';
            $xml.='<date_ouverture><![CDATA['.$res->date_ouverture.']]></date_ouverture>';
            $xml.='<date_fin_validite><![CDATA['.$res->date_fin_validite.']]></date_fin_validite>';
            $xml.='<date_cloture><![CDATA['.$res->date_cloture.']]></date_cloture>';
            $xml.='<tva_tx><![CDATA['.$res->tva_tx.']]></tva_tx>';
            $xml.='<qty><![CDATA['.$res->qty.']]></qty>';
            $xml.='<remise_percent><![CDATA['.$res->remise_percent.']]></remise_percent>';
            $xml.='<subprice><![CDATA['.price($res->subprice).']]></subprice>';
            $xml.='<price_ht><![CDATA['.price($res->price_ht).']]></price_ht>';
            $xml.='<total_ht><![CDATA['.$res->total_ht.']]></total_ht>';
            $xml.='<total_tva><![CDATA['.$res->total_tva.']]></total_tva>';
            $xml.='<total_ttc><![CDATA['.$res->total_ttc.']]></total_ttc>';
            $xml.='<fk_user_author><![CDATA['.$res->fk_user_author.']]></fk_user_author>';
            $xml.='<fk_user_ouverture><![CDATA['.$res->fk_user_ouverture.']]></fk_user_ouverture>';
            $xml.='<fk_user_cloture><![CDATA['.$res->fk_user_cloture.']]></fk_user_cloture>';
            $xml.='<commentaire><![CDATA['.$res->commentaire.']]></commentaire>';
            $xml.='<line_order><![CDATA['.$res->line_order.']]></line_order>';
            $xml.='<prod_duree_loc><![CDATA['.$res->prod_duree_loc.']]></prod_duree_loc>';
            $xml.='<prod_duree_loc_unit><![CDATA['.preg_replace('/[0-9]*/',"",$res->prod_duree_loc).']]></prod_duree_loc_unit>';
            $xml.='<prod_duree_loc_value><![CDATA['.preg_replace('/[^0-9]*/',"",$res->prod_duree_loc).']]></prod_duree_loc_value>';
            $requete = "SELECT * FROM llx_product_serial_cont WHERE element_id = ".$idLigne." AND element_type='contratLoc'";
            $sql = $db->query($requete);
            $res1 = $db->fetch_object($sql);
            $serial = ($res1->serial_number."x" != "x"?$res1->serial_number:false);

            $xml.='<serial><![CDATA['.$serial.']]></serial>';

// qte, produit, debut, fin, subprice subprice duration subprice duration unit


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
    case "editDateDeb":
    {
        $return = 1;
        $dateDeb="";
        if ($_REQUEST['value'])
        {
            if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['value'],$arr))
            {
                $dateDeb = $arr[3]."-".$arr[2]."-".$arr[1];
                $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET mise_en_service='".$dateDeb."' WHERE rowid = ".$_REQUEST['id'];
                $sql = $db->query($requete);
                $requete="SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE isSubPricePerMonth = 1 AND fk_contrat = ".$_REQUEST['id'];
                $sql=$db->query($requete);
                $contrat=new Contrat($db);
                $contrat->fetch($_REQUEST['id']);
                $nbMonth = $contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite);
                if (!$nbMonth>0) $nbMonth = 1;
                while($res = $db->fetch_object($sql))
                {
                    $total_ht = $res->subprice * $res->qty * $nbMonth;
                    $total_ttc = 1.196 * $total_ht;
                    $total_tva = 0.196 * $total_ht;
                    $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet
                                   SET total_ht = ".preg_replace('/,/','.',$total_ht).",
                                       total_ttc = ".preg_replace('/,/','.',$total_ttc).",
                                       total_tva=".preg_replace('/,/','.',$total_tva)."
                                 WHERE rowid = ".$res->id;
                    $db->query($requete);
                }
                $contrat->update_total_contrat();
            }
        } else {
            $return=false;
        }

        if ($return)
        {
            print $_REQUEST['value'];
        } else {
            print "KO";
        }
    }
    break;
    case "editDateFin":
    {
        $return = 1;
        $dateFin="";
        if ($_REQUEST['value'])
        {
            if(preg_match("/([0-9]*)[\W]([0-9]*)[\W]([0-9]*)/",$_REQUEST['value'],$arr))
            {
                $dateFin = $arr[3]."-".$arr[2]."-".$arr[1];
                $requete = "UPDATE ".MAIN_DB_PREFIX."contrat SET fin_validite='".$dateFin."' WHERE rowid = ".$_REQUEST['id'];
                $sql = $db->query($requete);
                $requete="SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE isSubPricePerMonth = 1 AND fk_contrat = ".$_REQUEST['id'];
                $sql=$db->query($requete);
                $contrat=new Contrat($db);
                $contrat->fetch($_REQUEST['id']);
                $nbMonth = $contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite);
                if (!$nbMonth>0) $nbMonth = 1;
                while($res = $db->fetch_object($sql))
                {
                    $total_ht = $res->subprice * $res->qty * $nbMonth;
                    $total_ttc = 1.196 * $total_ht;
                    $total_tva = 0.196 * $total_ht;
                    $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet
                                   SET total_ht = ".preg_replace('/,/','.',$total_ht).",
                                       total_ttc = ".preg_replace('/,/','.',$total_ttc).",
                                       total_tva=".preg_replace('/,/','.',$total_tva)."
                                 WHERE rowid = ".$res->rowid;
                    $db->query($requete);
                }
                $contrat->update_total_contrat();
            }
        } else {
            $return=false;
        }

        if ($return)
        {
            $contrat = new Contrat($db);
            $contrat->fetch($_REQUEST['id']);
            if (!$contrat->date_fin_validite > 0) $dateFin = "<table><tr><td class='error ui-state-error' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='error ui-state-error' style='border-left: 0px;'>Pas de date de fin</span></table>";
            else if ($contrat->date_fin_validite - time() < 0 && $contrat->statut < 5) $dateFin = "<table><tr><td class='error ui-state-error' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='error ui-state-error' style='border-left: 0px;'>".$_REQUEST['value']."</span></table>";
            else if (($contrat->date_fin_validite - ($conf->global->MAIN_DELAY_RUNNING_SERVICES * 3600 * 24) - time()) < 0 && $contrat->statut < 5) $dateFin = "<table><tr><td class='ui-state-highlight' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='ui-state-highlight' style='border-left: 0px;'>".$_REQUEST['value']."</span></table>";
            else $dateFin = $_REQUEST['value'];
            print $dateFin;
        } else {
            print "KO";
        }

    }
    break;
    case 'addligneLoc':
    {
        $contratId = $_REQUEST['id'];
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/ContratLocProdGA.class.php');
        $contrat = new ContratLocProdGA($db);
        $contrat->fetch($contratId);

        $fk_product = $_REQUEST['fk_product'];
        $qty = $_REQUEST['qty'];
        $pu_ht = $_REQUEST['pu_ht'];
        $prix_mensuel = $_REQUEST['prix_mensuel'];
        $puttc = (1+(floatval($contrat->tva_tx)/100)) * $_REQUEST['pu_ht'];
        $serial=$_REQUEST['serial'];
        $prod = new Product($db);
        $prod->fetch($fk_product);
        $duration = ($_REQUEST['duration_value']>0?$_REQUEST['duration_value']:1).($_REQUEST['duration_unit']."x"!="x"?$_REQUEST['duration_unit']:'m');
        $datedeb=false;
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['debut_loc'],$arr)){
            $datedeb=strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
        } else {
            $datedeb=$contrat->mise_en_service;
        }
        $datefin=false;
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$_REQUEST['fin_loc'],$arr)){
            $datefin=strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
        } else {
            $datefin=$contrat->date_fin_validite;
        }
        if ( $datedeb > 0 && $datefin > 0 && $fk_product > 0)
        {

            $res = $contrat->addline($prod->libelle, $pu_ht, $qty, 11, $fk_product, 0, $datedeb, $datefin, 'HT', $pu_ttc, 0,false,$duration);

            if ($res)
            {
                $requete = "INSERT INTO llx_product_serial_cont
                                        (serial_number,date_creation, element_id, tms, fk_user_author, element_type)
                                 VALUES ('".addslashes($serial)."',now(),".$contrat->newContractLigneId.",now(),".$user->id.",'contratLoc')";
                $sql = $db->query($requete);
                $total_ht = $contrat->getTotalLigne($pu_ht,$datedeb,$datefin,$duration,$qty);
                $contrat->update_totalLigne($idLigne,$total_ht);

                $xml .= "<OK>OK</OK>";
                $xml .= "<OKtext><![CDATA[";
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$contrat->newContractLigneId;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $xml .= $contrat->display1Line($res);
                $xml .= "]]></OKtext>";
            } else {
                $xml .= "<KO><![CDATA[".$db->lastqueryerror . "\n ". $db->lasterror ."]]></KO>";
            }
        }
    }
    break;
    case 'subQty':
    {

        $requete = "SELECT fk_contrat, qty, isSubPricePerMonth, subprice FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $qty = $res->qty;
        if ($qty > 1)
        {
            $qty --;
        }
        $prix_mensuel = $res->isSubPricePerMonth;
        $pu_ht = $res->subprice;
        $contrat = new Contrat($db);
        $contrat->fetch($res->fk_contrat);
        $total_ht=0;
        $total_ttc=0;
        $total_tva=0;
        if ($prix_mensuel > 0 && $contrat->mise_en_service > 0 && $contrat->date_fin_validite > 0)
        {
            $nbMonth = $contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite);
            if (!$nbMonth>0)$nbMonth = 1;
            $total_ht = $pu_ht * $qty * $nbMonth;
            $total_tva = 0.196 * $total_ht;
            $total_ttc = 1.196 * $total_ht;
        } else {
            $total_ht = $pu_ht * $qty;
            $total_tva = 0.196 * $total_ht;
            $total_ttc = 1.196 * $total_ht;
        }

        $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet
                       SET qty=".$qty.",
                           total_ht=".($total_ht>0?preg_replace("/,/",'.',$total_ht):0).",
                           total_tva=".($total_tva>0?preg_replace("/,/",'.',$total_tva):0).",
                           total_ttc=".($total_ttc>0?preg_replace("/,/",'.',$total_ttc):0)."
                           WHERE rowid =".$_REQUEST['lineId'];
        $db->query($requete);
        $contrat->update_total_contrat();
        $contrat->fetch($contrat->id);
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid =".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res=$db->fetch_object($sql);

        $xml .= "<OK>OK</OK>";
        $xml .= "<OKtext><![CDATA[";
        $xml .= $qty;
        $xml .= "]]></OKtext>";
        $xml .= "<total_ht_ligne><![CDATA[";
        $xml .= price($res->total_ht). " &euro;";
        $xml .= "]]></total_ht_ligne>";
        $xml .= "<total_ht><![CDATA[";
        $xml .= price($contrat->total_ht). " &euro;";
        $xml .= "]]></total_ht>";
        $xml .= "<loyer><![CDATA[";
        $xml .= price($contrat->total_ht/$contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite)). " &euro;";
        $xml .= "]]></loyer>";

    }
    break;
    case 'addQty':
    {
        $requete = "SELECT fk_contrat, qty, isSubPricePerMonth, subprice FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $qty = ($res->qty.'x'=='x'?1:$res->qty);
        $qty ++;
        $prix_mensuel = $res->isSubPricePerMonth;
        $pu_ht = $res->subprice;
        $contrat = new Contrat($db);
        $contrat->fetch($res->fk_contrat);
        $total_ht=0;
        $total_ttc=0;
        $total_tva=0;
        if ($prix_mensuel > 0 && $contrat->mise_en_service > 0 && $contrat->date_fin_validite > 0)
        {
            $nbMonth = $contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite);
            if (!$nbMonth>0)$nbMonth = 1;
            $total_ht = $pu_ht * $qty * $nbMonth;
            $total_tva = 0.196 * $total_ht;
            $total_ttc = 1.196 * $total_ht;
        } else {
            $total_ht = $pu_ht * $qty;
            $total_tva = 0.196 * $total_ht;
            $total_ttc = 1.196 * $total_ht;
        }

        $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet
                       SET qty=".$qty.",
                           total_ht=".($total_ht>0?preg_replace("/,/",'.',$total_ht):0).",
                           total_tva=".($total_tva>0?preg_replace("/,/",'.',$total_tva):0).",
                           total_ttc=".($total_ttc>0?preg_replace("/,/",'.',$total_ttc):0)."
                           WHERE rowid =".$_REQUEST['lineId'];
        $db->query($requete);
        $contrat->update_total_contrat();
        $contrat->fetch($contrat->id);
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid =".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        $res=$db->fetch_object($sql);

        $xml .= "<OK>OK</OK>";
        $xml .= "<OKtext><![CDATA[";
        $xml .= $qty;
        $xml .= "]]></OKtext>";
        $xml .= "<total_ht_ligne><![CDATA[";
        $xml .= price($res->total_ht). " &euro;";
        $xml .= "]]></total_ht_ligne>";
        $xml .= "<total_ht><![CDATA[";
        $xml .= price($contrat->total_ht). " &euro;";
        $xml .= "]]></total_ht>";
        $xml .= "<loyer><![CDATA[";
        $xml .= price($contrat->total_ht/$contrat->datediff($contrat->mise_en_service,$contrat->date_fin_validite)). " &euro;";
        $xml .= "]]></loyer>";

    }
    break;
    case 'delLocline':
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid = ".$_REQUEST['lineId'];
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<OK>OK</OK>";
            $requete = "DELETE FROM llx_product_serial_cont WHERE element_id = ".$_REQUEST['lineId'];
            $sql = $db->query($requete);
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


function returnNewLine($lineId,$isProd=false)
{
    global $db, $conf, $langs, $user;
    $contratline = new ContratLigne($db);
    $contratline->fetch($lineId);
    //$contratline->fk_product=$_REQUEST['p_idprod'];
    $contrat = new Contrat($db);
    $contrat->id = $contratline->fk_contrat;
    $contrat->fetch($contratline->fk_contrat);
    $contratline->fk_product=$_REQUEST['p_idprod'];
    //var_dump($contratline);
    //TODO not correctly load
    $txt = $contrat->display1line($contratline);


    return ($txt);
}

?>