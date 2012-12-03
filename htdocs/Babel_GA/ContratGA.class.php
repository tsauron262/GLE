<?php
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
class ContratGA extends contrat{

    public function ContratGA($db) {
        $this->db = $db ;
        $this->product = new Product($this->db);
        $this->societe = new Societe($this->db);
        $this->user_service = new User($this->db);
        $this->user_cloture = new User($this->db);
        $this->client_signataire = new User($this->db);
    }
    public $isTx0 =0;
    public $montantTotHTAFinancer = 0;
    public $tauxMarge = 0;
    public $tauxFinancement = 0;
    public $financement_period_refid = 0;
    public $echu = 0;
    public $periodDescription = "";
    public $periodDescription2 = "";
    public $pNbIterAn = "";
    public $client_signataire;
    public $cessionnaire_refid;

    public $total_ht;

    public function fetch($id)
    {

        $sql = "SELECT rowid, statut, ref, fk_soc, ".$this->db->pdate("mise_en_service")." as datemise,";
        $sql.= " fk_user_mise_en_service, ".$this->db->pdate("date_contrat")." as datecontrat,";
        $sql.= " fk_user_author, (SELECT max(echu) FROM Babel_GA_contrat WHERE contratdet_refid IN (SELECT rowid FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$id.")) as echu, ";
        $sql.= " fk_projet, type";
        $sql.= " modelPdf,";
        $sql.= " linkedTo,";
        $sql.= " is_financement,cessionnaire_refid,fournisseur_refid, tva_tx";
        $sql.= " fk_commercial_signature, fk_commercial_suivi,";
        $sql.= " note, note_public, date_valid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$id;
//print $sql ."<br/>";
        dol_syslog("Contrat::fetch sql=".$sql);
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $result = $this->db->fetch_array($resql);

            if ($result)
            {
                $this->id                = $result["rowid"];
                $this->ref               = (!isset($result["ref"]) || !$result["ref"]) ? $result["rowid"] : $result["ref"];
                $this->statut            = $result["statut"];
                $this->factureid         = $result["fk_facture"];
                $this->facturedetid      = $result["fk_facturedet"];
                $this->mise_en_service   = $result["datemise"];
                $this->date_fin_validite = $result["datefin"];
                $this->linkedTo          = $result["linkedTo"];
                $this->modelPdf          = $result["modelPdf"];
                $this->date_valid        = $result['date_valid'];


                $this->date_contrat      = $result["datecontrat"];

                $this->user_author_id    = $result["fk_user_author"];

                $this->commercial_signature_id = $result["fk_commercial_signature"];
                $this->commercial_suivi_id = $result["fk_commercial_suivi"];

                $this->user_service->id  = $result["fk_user_mise_en_service"];
                $this->user_cloture->id  = $result["fk_user_cloture"];

                $this->note              = $result["note"];
                $this->note_public       = $result["note_public"];

                $this->fk_projet         = $result["fk_projet"];

                $this->socid            = $result["fk_soc"];
                $this->societe->fetch($result["fk_soc"]);    // TODO A virer car la societe doit etre charge par appel de fetch_client()
                $this->total_ht = 0;

                $this->is_financement = $result['is_financement'];
                $this->cessionnaire_refid = $result['cessionnaire_refid'];
                $this->fournisseur_refid = $result['fournisseur_refid'];
                $this->duree = 0;
                $this->period_id = 0;
                $this->echu = $result['echu'];
                $this->tva_tx = $result['tva_tx'];
                $this->type = $result['type'];
                $this->typeContrat = $result['type'];


                $arr = $this->liste_contact(4,'external');
                foreach($arr as $key=>$val)
                {
                    if ($val['code'] == 'BILLING') {
                        $this->client_signataire_refid= $val["id"];
                    }
                }


                $this->db->free($resql);

                return $this->id;
            } else {
                dol_syslog("Contrat::Fetch Erreur contrat non trouve");
                $this->error="Contrat non trouve";
                return -2;
            }
        } else {
            dol_syslog("Contrat::Fetch Erreur lecture contrat");
            $this->error=$this->db->error();
            return -1;
        }

        //Get Extra
    }

    public function fetch_lignes()
    {
        $this->nbofserviceswait=0;
        $this->nbofservicesopened=0;
        $this->nbofservicesclosed=0;
        $this->lignes=array();
        $this->total_ht = 0;
        // Selectionne les lignes contrats liees a un produit
        $sql = "SELECT p.label,
                       p.description as product_desc,
                       p.ref,
                       d.rowid,
                       d.statut,
                       d.description,
                       d.price_ht,
                       d.tva_tx,
                       d.line_order,
                       d.total_ht,
                       d.qty,
                       d.remise_percent,
                       d.subprice,
                       d.info_bits,
                       d.fk_product,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture
                  FROM ".MAIN_DB_PREFIX."contratdet as d,
                       llx_product as p
                 WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = p.rowid
              ORDER BY d.line_order, d.rowid ASC";

        dol_syslog("Contrat::fetch_lignes sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $objp                  = $this->db->fetch_object($result);

                $ligne                 = new ContratLigne($this->db);
                $ligne->id             = $objp->rowid;
                $ligne->desc           = $objp->description;  // Description ligne
                $ligne->description    = $objp->description;  // Description ligne
                $ligne->qty            = $objp->qty;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->statut            = $objp->statut;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht          = $objp->total_ht;
                $ligne->fk_product     = $objp->fk_product;

                if ($objp->fk_product > 0)
                {
                    $product = new Product($this->db);
                    $product->id =$objp->fk_product;
                    $product->fetch($objp->fk_product);
                    $ligne->product=$product;
                } else {
                    $ligne->product=false;
                }

                $ligne->info_bits      = $objp->info_bits;

                $ligne->ref            = $objp->ref;
                $ligne->libelle        = $objp->label;        // Label produit

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;
                if ($objp->line_order != 0)
                {
                    $this->lignes[$objp->line_order]        = $ligne;
                } else {
                    $this->lignes[]        = $ligne;
                }
                //dol_syslog("1 ".$ligne->desc);
                //dol_syslog("2 ".$ligne->product_desc);

                if ($ligne->statut == 0) $this->nbofserviceswait++;
                if ($ligne->statut == 4) $this->nbofservicesopened++;
                if ($ligne->statut == 5) $this->nbofservicesclosed++;

                $i++;
            }
            $this->db->free($result);
        } else {
            dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liees aux produits");
            return -3;
        }

        // Selectionne les lignes contrat liees a aucun produit
        $sql = "SELECT d.rowid,
                       d.statut,
                       d.qty,
                       d.description,
                       d.price_ht,
                       d.total_ht,
                       d.subprice,
                       d.tva_tx,
                       d.line_order,
                       d.rowid,
                       d.remise_percent,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture
                 FROM ".MAIN_DB_PREFIX."contratdet as d
                WHERE d.fk_contrat = ".$this->id ."
                  AND (d.fk_product IS NULL OR d.fk_product = 0)";   // fk_product = 0 garde pour compatibilite

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $objp                  = $this->db->fetch_object($result);
                $ligne                 = new ContratLigne($this->db);
                $ligne->id                = $objp->rowid;
                $ligne->libelle        = stripslashes($objp->description);
                $ligne->desc           = stripslashes($objp->description);
                $ligne->qty            = $objp->qty;
                $ligne->statut            = $objp->statut;
                $ligne->ref            = $objp->ref;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht          = $objp->total_ht;
                $ligne->fk_product     = 0;

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;


                $requete = "SELECT * FROM Babel_GA_contrat WHERE contratdet_refid = ".$ligne->id;
                $sql = $this->db->query($requete);
                $res = $this->db->fetch_object($sql);

                $ligne->isTx0 = $res->isTx0;
                $ligne->montantTotHTAFinancer = $res->montantTotHTAFinancer;
                $ligne->tauxMarge = $res->tauxMarge;
                $ligne->tauxFinancement = $res-> tauxFinancement;
                $ligne->financement_period_refid = $res->financement_period_refid;
                $ligne->echu = $res->echu;
                $ligne->duree = $res->duree;
                $this->getFinancementPeriod($ligne->financement_period_refid);
                $ligne->client_signataire_refid = $res->client_signataire_refid;
                require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
                $ligne->client_signataire = new Contact($this->db);
                $ligne->client_signataire->id = $ligne->client_signataire_refid;
                $ligne->client_signataire->fetch($ligne->client_signataire_refid);
                $this->total_ht += floatval($res->montantTotHTAFinancer);
                $ligne->total_ht->$ligne->montantTotHTAFinancer;

                $ligne->NbIterAn = $this->NbIterAn;
                $ligne->loyer = $this->getLoyer($res->tauxFinancement, $ligne->NbIterAn,$ligne->duree,$ligne->total_ht * (1+$ligne->tauxMarge/100),$ligne->echu);

                $this->duree = $res->duree;
                $this->period_id = $res->financement_period_refid;
                //$this->echu = $res->echu;

                $this->finDetail[$ligne->id]['tauxFin']=$ligne->tauxFinancement;
                $this->finDetail[$ligne->id]['tauxMarge']=$ligne->tauxMarge;
                $this->finDetail[$ligne->id]['NbIterAn']=$ligne->NbIterAn;
                $this->finDetail[$ligne->id]['total_ht']=$ligne->total_ht;
                $this->finDetail[$ligne->id]['echu']=$ligne->echu;
                $this->finDetail[$ligne->id]['duree']=$ligne->duree;
                $this->finDetail[$ligne->id]['loyer']=$ligne->loyer;

//                if ($ligne->statut == 0) $this->nbofserviceswait++;
//                if ($ligne->statut == 4) $this->nbofservicesopened++;
//                if ($ligne->statut == 5) $this->nbofservicesclosed++;
                if ($objp->line_order != 0)
                {
                    $this->lignes[$objp->line_order]        = $ligne;
                } else {
                    $this->lignes[]        = $ligne;
                }

                $i++;
            }

            $this->db->free($result);
        } else {
            dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrat non liees aux produits");
            $this->error=$this->db->error();
            return -2;
        }

        $this->nbofservices=sizeof($this->lignes);

        ksort($this->lignes);
        return $this->lignes;
    }

    public function getFinancementPeriod($i)
    {
        $requete = "SELECT * FROM Babel_financement_period WHERE active=1 AND id = ".$i;
        $sql = $this->db->query($requete);
        while($res = $this->db->fetch_object($sql))
        {
            $this->periodDescription = $res->Description;
            $this->periodDescription2 = $res->Description2;
            $this->NbIterAn = $res->NbIterAn;
        }
    }

    public function displayPreLine()
    {
        $html = "";
        //Le bouton configuration
        if ($this->statut == 0)
        {
            $html .= "<button id='configContrat' class='butAction ui-corner-all ui-widget-header ui-state-default' style='padding: 5px 10px;'>";
            $html .= "<span style='float: left;margin-right: 10px;' class='ui-icon ui-icon-newwin'></span>";
            $html .= "<span style='float: left;'>Configuration du financement</span>";
            $html .= "</button><br/><br/>";
            return($html);
        }
    }

    public function displayLine()
    {
        global $conf, $form;
        $html = "";


        //1 on affiche les ligne de contrant à financer
        $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.description,
                           ".MAIN_DB_PREFIX."contratdet.total_ht,
                           Babel_GA_contrat.isTx0,
                           Babel_GA_contrat.montantTotHTAFinancer,
                           Babel_GA_contrat.tauxMarge,
                           Babel_GA_contrat.tauxFinancement,
                           Babel_GA_contrat.financement_period_refid,
                           Babel_GA_contrat.echu,
                           Babel_GA_contrat.duree,
                           Babel_GA_contrat.client_signataire_refid,
                           Babel_financement_period.NbIterAn
                      FROM ".MAIN_DB_PREFIX."contratdet,
                           Babel_GA_contrat,
                           Babel_financement_period
                     WHERE fk_contrat = ".$this->id. "
                       AND price_ht > 0
                       AND Babel_GA_contrat.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid
                       AND Babel_financement_period.id = Babel_GA_contrat.financement_period_refid
                       AND fk_product is NULL  ";

        $sql = $this->db->query($requete);
        if ($sql)
        {
            $granTot = 0;
            $granTotLoyer=0;

            while ($res = $this->db->fetch_object($sql))
            {
                $principal = $res->total_ht * (1 + $res->tauxMarge / 100);
                $type = $res->echu;
                $payperyear = $res->NbIterAn;
                $amortperiod = $res->duree;
                $loyer = $this->getLoyer($res->tauxFinancement, $payperyear,$amortperiod,$principal,$type);
                $html .= "<table cellpadding=15 width=100%>
                                <tr>
                                    <th width=20% class='ui-widget-header ui-state-default'>".htmlentities(utf8_decode($res->description))."
                                    <td width=30%  class='ui-widget-content'>".price($res->total_ht)."&euro;
                                    <th width=20% class='ui-widget-header ui-state-default'>Loyer HT
                                    <td width=30%  class='ui-widget-content'>".price(round($loyer*100)/100)."&euro;";
                $html .= "</table>";
                $granTot += $res->total_ht;
                $granTotLoyer += $loyer;
            }
            $html .= "<table cellpadding=15 width=100%><tr><th class='ui-widget-header ui-state-default' width=20%>Total &agrave; financer<td width=30% class='ui-widget-content'>".price($granTot)."&euro;";
            $html .= "<th class='ui-widget-header ui-state-default' width=20%>Loyer total<td width=30%  class='ui-widget-content'>".price(round($granTotLoyer*100)/100)."&euro;";
            $html .= "</table>";

            if($this->statut==0)
            {
                $html .= "<table cellpadding=15 width=100%><tr><th width=20% class='ui-state-default ui-widget-header' colspan=2>Ajouter des produits du contrat</th>";
                $html .= "<td class='ui-widget-content' colspan=1>";
                $html .= "<div style='height: 100%; width: 100%' class='ui-widget-content'>";
                $html .= "<table class='noborder'>";
                $html .= "<tr><td>";
                $html .= "Qt&eacute;.";
                $html .= "<td>";
                $html .= "<input type='text' id='qty' name='qty'  style='width: 4em;' value='1'>";
                $html .= "<td>";
                if($conf->global->PRODUIT_MULTIPRICES == 1)
                    $html .= $form->select_produits('','p_idprod',$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
                else
                    $html .= $form->select_produits('','p_idprod',"",$conf->produit->limit_size,false,1,true,true,false);
                $html .=  '<td><div class="nocellnopadd" id="ajdynfieldp_idprod"></div>';

                $html .= "<td><button style='padding: 5px 10px;' class='button butAction ui-widget-header ui-corner-all ui-state-default' id='GAaddLine' >
                                <span class='ui-icon ui-icon-circle-plus' style='float: left; margin-right: 10px;'></span>
                                <span style='float: left;'>Ajouter</span>
                              </button>";
                $html .= "</td>";
                $html .= "</table>";
                $html .= "</table>";
            }
            //liste du matériel
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet  WHERE fk_contrat = ".$this->id. " AND price_ht = 0 AND fk_product is not NULL";
            $sql = $this->db->query($requete);
            if ($sql)
            {
                $html .= "<center>";
                $html .= "<table cellpadding=5 width=80% id='prodTable'>";
                $html .= "<tr style='border: 2px Solid #0073EA;'>
                            <th width=20% class='ui-state-default ui-widget-header' colspan=4 style='padding: 15px;  font-size: 14px;'>Produits du contrat</th>";
                while ($res = $this->db->fetch_object($sql))
                {
                    $prodTmp = new Product($this->db);
                    $prodTmp->id = $res->fk_product;
                    $prodTmp->fetch($prodTmp->id);
                    $html .= "<tr><th width=80% align=left class='ui-widget-content' style='padding-left: 15px'>".$prodTmp->getNomUrl(1) . " ". $prodTmp->libelle."
                                  <th class='ui-widget-content' >Qt&eacute;</td>
                                  <td class='ui-widget-content' id='qteText' align='center'>
                                    <table style=''>
                                        <tr><td rowspan=3 width=32><span>".$res->qty."</span>";
                    if($this->statut==0)
                    {
                        $html.=            "<tr><td width=16 id=".$res->rowid." align='center' class='plus ui-widget-header'><span style='padding:0; margin: -3px; margin-bottom: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-n'></span>
                                            <tr><td width=16 id=".$res->rowid." align='center' class='moins ui-widget-header'><span style='padding:0; margin: -3px; margin-top: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-s'></span>";
                    }
                    $html.=        "</table>";
                    if($this->statut==0)
                    {
                        $html.= "     <td align=center style='padding-left: 15px' class='ui-widget-content'><span class='deleteGA'  style='cursor: pointer;' id='".$res->rowid."'>" . img_delete()."</span>";
                    }
                }
                $html .= "</table>";
                $html .= "</center>";
            }

        } else {
            //error
        }
        //Si taux 0
            //2 affiche le formulaire de choix du matériel à 0%
            //3 affiche la saisie libre => tag
            //4 affiche le formulaire de choix à taux pas 0
        //Sinon
            //2 affiche le formulaire de choix du matériel
            //3 affiche la saisie libre => tag
            return ($html);
    }

    public function displayAddLine($mysoc,$objp)
    {
        $html = "";
        return($html);
    }
    public function initDialog(){
        $html = "";
        global $mysoc, $form;
        if ($this->statut == 0)
        {
            $html = "<div id='configDialog'>";
            $html .= "<form id='formConfig' onSubmit='return (false);'>";
            //1 taux de financement des lignes par défault

            $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.total_ht,
                               ".MAIN_DB_PREFIX."contratdet.description,
                               Babel_GA_contrat.tauxMarge,
                               Babel_GA_contrat.tauxFinancement
                          FROM ".MAIN_DB_PREFIX."contratdet,
                               Babel_GA_contrat
                         WHERE fk_contrat = " . $this->id . "
                           AND fk_product is null
                           AND ".MAIN_DB_PREFIX."contratdet.rowid = Babel_GA_contrat.contratdet_refid ";
            $sql = $this->db->query($requete);
            $iter=0;
            $ligne = array();
            while ($res = $this->db->fetch_object($sql))
            {
                $ligne[$iter]['design']=$res->description;
                $ligne[$iter]['total_ht']=round($res->total_ht*100)/100;
                $ligne[$iter]['marge']=round($res->tauxMarge*100)/100;
                $ligne[$iter]['taux']=round($res->tauxFinancement*100)/100;
                $iter++;
            }

            $html .= "<fieldset><legend>Montant & taux</legend>";
            $html .= "<table width=100%>";
            $iter =0;
            $html .= "<tr>";
            $html .= "   <th class='ui-state-default ui-widget-header'>D&eacute;signation";
            $html .= "   <th class='ui-state-default ui-widget-header'>Montant HT";
            $html .= "   <th class='ui-state-default ui-widget-header'>Taux";
            $html .= "   <th class='ui-state-default ui-widget-header'>Marge";
            $html .= "   <th width=16px class='ui-state-default ui-widget-header'>Action";
            foreach($ligne as $key=>$val)
            {
                $html .= "<tr>
                            <td class='ui-state-default ui-widget-header'>".utf8_decode($val['design'])."
                            <input type='hidden' id='design-".$iter."' name='design-".$iter."' value='".utf8_decode($val['design'])."'></td>
                            <td align='center' class='ui-widget-content' style='width: 100px;'>
                                <input name='total-".$iter."' class='required currency sup1'  id='total-".$iter."'  style='text-align: center; width: 100px;' value='".$val['total_ht']."'>
                            </td>
                            <td style='width: 100px;' align='center' class='ui-widget-content'>
                                <input class='required percentdecimal' name='taux-".$iter."'  id='taux-".$iter."'  style='text-align: center;width:100px;' value='".$val['taux']."'>
                            </td>
                            <td style='width: 100px;' align='center' class='ui-widget-content'>
                                <input name='marge-".$iter."'  id='marge-".$iter."' class='required percentdecimal' style='text-align: center;width: 100px;' value='".$val['marge']."'>";
                $html .= "<td align=center><span class='ui-icon ui-icon-trash' id='delLigneFinContrat'></span></td>";
                $iter++;
            }
            $html .= "<tfoot>";
            $html .= "<tr><td class='ui-widget-header ui-state-focus' colspan=7 align=right><span id='addLigneFinContrat' class='ui-corner-all ui-icon ui-icon-circle-plus'>";
            $html .= "</tfoot>";
            $html .= "</table>";
            $html .= "</fieldset>";
            $html .= "<br/>";

            $html .= "<fieldset><legend>Cessionnaire</legend>";
            $html .= "<table width=100%>";
            $html .= "<tr><th width=250 class='ui-widget-header ui-state-default'>Cessionnaire</th>";
            $html .= "<td class='ui-widget-header ui-state-default'>";
            $html .= "<select name='cessionnaire' id='cessionnaire'>";
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE cessionnaire = 1";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                if ($this->cessionnaire_refid == $res->rowid)
                {
                    $html .= "<option SELECTED value='".$res->rowid."'>".$res->nom."</option>";
                } else {
                    $html .= "<option value='".$res->rowid."'>".$res->nom."</option>";
                }
            }
            $html .= "</select>";
            $html .= "</td>";
            $html .= "<tr><th width=250 class='ui-widget-header ui-state-default'>Fournisseur</th>";
            $html .= "<td class='ui-widget-header ui-state-default'>";
            $html .= "<select name='fournisseur' id='fournisseur'>";
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 1";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                if ($this->cessionnaire_refid == $res->rowid)
                {
                    $html .= "<option SELECTED value='".$res->rowid."'>".$res->nom."</option>";
                } else {
                    $html .= "<option value='".$res->rowid."'>".$res->nom."</option>";
                }
            }
            $html .= "</select>";
            $html .= "</td>";
            $html .= "</table>";
            $html .= "</fieldset>";
            $html .= "<br/>";

            $html .= "<fieldset><legend>TVA</legend>";
            $html .= "<table width=100%>";
            $html .= "<tr><th width=250  class='ui-widget-header ui-state-default'>Taux de TVA</th>";
            $html .= "<td class='ui-widget-header ui-state-default'>";
            $html .= $form->select_tva("Linetva_tx",$objp->tva_tx,$mysoc,$contrat->societe,"",0,false);
            $html .= "</td>";
            $html .= "</table>";

            $html .= "</fieldset>";
            $html .= "<br/>";


            //6 type de contrat ??
//            $html .= "<fieldset><legend>Type de contrat</legend>";
//            $html .= "taux 0 / taux fixe";
//            $html .= "</fieldset>";
//            $html .= "<br/>";
            //7 echu ou a echoir
            $html .= "<fieldset><legend>Chronologie</legend>";
            $html .= "<table width=100%>";
            $html .= "<tr><td>P&eacute;riodicit&eacute;</td>
                          <td><select name='period'  id='period'>";
            $requete ="SELECT * FROM Babel_financement_period WHERE active = 1 ORDER BY NbIterAn DESC";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                if ($res->id == $this->period_id)
                {
                    $html .= "<option selected value='".$res->id."'>".$res->Description."</option>";
                } else {
                    $html .= "<option value='".$res->id."'>".$res->Description."</option>";
                }
            }
            $html .= "</select>";


            $html .= "</td><td>Dur&eacute;e (en mois)</td>
                           <td><input class='required nombreentier' id='duree' name='duree' value='".$this->duree."'></td>
                           <td>Terme de paiement</td>";
            $extra = "checked";
//            if ($this->echu != 1) $extra = "";
            if ($this->echu ==0 ) $extra = "";
            $html .= "     <td><input id='echu'  name='echu' type='checkbox' ".$extra.">
                           <td><span ".($this->echu==1?"style='display:none;'":"")." id='dialogEchoirChkBox'>A echoir</span><span ".($this->echu!=1?"style='display:none;'":"")." id='dialogEchuChkBox'>echu&nbsp;&nbsp;&nbsp;&nbsp;</span></span>";
            $html .= "</table>";
            $html .= "</fieldset>";

            $html .= <<<EOF
                <script>
                jQuery(document).ready(function(){
                    jQuery('#echu').click(function(){
                        if(jQuery('#echu').attr('checked') == true)
                        {
                            jQuery('#dialogEchoirChkBox').css('display','none');
                            jQuery('#dialogEchuChkBox').css('display','block');
                        } else {
                            jQuery('#dialogEchoirChkBox').css('display','block');
                            jQuery('#dialogEchuChkBox').css('display','none');
                        }
                    });
                });
                </script>
EOF;

            $html .= "<br/>";
            //8 duree
            //9 period
            $html .= "";
            $html .= "</form>";
            $html .= "</div>";
        }
        return($html);

    }
    public  function displayDialog()
    {
    }
    public function getLoyer($interest, $payperyear,$amortperiod,$principal,$type=0)
    {
        $interest = $interest / 100;
        $amortperiod = $amortperiod / 12;
        $intrate = floatval($interest) / floatval($payperyear);
        $numpayments = floatval($amortperiod) * floatval($payperyear);
        $x = pow(1 + floatval($intrate),floatval($numpayments));
        $payments = (floatval($intrate) * floatval($principal) * floatval($x)) / ((floatval($x) -1) * (1 + floatval($intrate) * floatval($type)));
        return (floatval($payments));
    }

    public function getLoyerTot()
    {
        //$interest, $payperyear,$amortperiod,$principal,$type=0;
        $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.total_ht,
                           Babel_GA_contrat.tauxMarge,
                           Babel_GA_contrat.tauxFinancement,
                           Babel_GA_contrat.financement_period_refid,
                           Babel_GA_contrat.echu,
                           Babel_GA_contrat.duree,
                           Babel_GA_contrat.client_signataire_refid,
                           Babel_financement_period.NbIterAn,
                           Babel_financement_period.Description as periodicite
                      FROM ".MAIN_DB_PREFIX."contratdet,
                           Babel_GA_contrat,
                           Babel_financement_period
                     WHERE fk_product is null
                       AND total_ht > 0
                       AND ".MAIN_DB_PREFIX."contratdet.rowid = Babel_GA_contrat.contratdet_refid
                       AND Babel_financement_period.id = Babel_GA_contrat.financement_period_refid
                       AND  fk_contrat = ".$this->id;
        $sql = $this->db->query($requete);
        $loyerTot = 0;
        while ($res = $this->db->fetch_object($sql))
        {

            $principal = $res->total_ht * (1+ $res->tauxMarge/100);
            $interest = $res->tauxFinancement;
            $payperyear = $res->NbIterAn;
            $amortperiod = $res->duree;
            $type = $res->echu;
            $this->duree = $amortperiod;
            $this->NbIterTot = $payperyear * ($res->duree / 12);
            $this->periodicite = $res->periodicite;
            $this->echu = $res->echu;
            $interest = $interest / 100;
            $amortperiod = $amortperiod / 12;
            $intrate = floatval($interest) / floatval($payperyear);
            $numpayments = floatval($amortperiod) * floatval($payperyear);
            $x = pow(1 + floatval($intrate),floatval($numpayments));
            $payments = (floatval($intrate) * floatval($principal) * floatval($x)) / ((floatval($x) -1) * (1 + floatval($intrate) * floatval($type)));
            $loyerTot += floatval($payments);
        }
        return (floatval($loyerTot));
    }

    public function displayExtraInfoCartouche()
    {
            print '<tr><th align=left style="padding: 5px 15px;" colspan=4 class="ui-widget-header ui-state-default">Gestion d\'actif';

            print '<tr><td class="ui-widget-header ui-state-default">Client signataire';
            print '<td class="ui-widget-content" colspan=3>';
            $contactTmp = new Contact($this->db);
            $contactTmp->fetch($this->client_signataire_refid);
            print $contactTmp->getNomUrl(1);

            print '<tr><td class="ui-widget-header ui-state-default">Cessionnaire';
            print '<td class="ui-widget-content" colspan=3>';
            if ($this->cessionnaire_refid ."x" != "x")
            {
                $socTmp = new Societe($this->db);
                $socTmp->fetch($this->cessionnaire_refid);
                print $socTmp->getNomUrl(1);
            } else {
                print '<span class="ui-icon ui-icon-alert" style="float: left; margin-right: 5px; margin-top: -1px;"></span><span class="error">Pas de cessionnaire</span>';
            }

            print '<tr><td class="ui-widget-header ui-state-default">Fournisseur';
            print '<td class="ui-widget-content" colspan=3>';
            if ($this->fournisseur_refid ."x" != "x")
            {
                $socTmp = new Societe($this->db);
                $socTmp->fetch($this->fournisseur_refid);
                print $socTmp->getNomUrl(1);
            } else {
                print '<span class="ui-icon ui-icon-alert" style="float: left; margin-right: 5px; margin-top: -1px;"></span><span class="error">Pas de fournisseur</span>';
            }

            if ($this->statut > 0)
            {
                //Affichage de la date
                //On recupere la date de validation et la durée
                print '<tr><td class="ui-widget-header ui-state-default">Date et dur&eacute;e';
                print '<td class="ui-widget-content" colspan=3>Du&nbsp;&nbsp;';
                print ''.date('d/m/Y',strtotime($this->date_valid));
                $requeteDF = "SELECT date_add('".$this->date_valid."', interval ".$this->duree." month) as df ";
                $sqlDF = $this->db->query($requeteDF);
                $resDF = $this->db->fetch_object($sqlDF);
                print '&nbsp;&nbsp;au&nbsp;'.date('d/m/Y',strtotime($resDF->df));
                print '&nbsp;&nbsp;&nbsp;(Soit: '.$this->duree."&nbsp;Mois)";

                //Renouvellement
                $cntRenew = $this->renewList();
                if ($cntRenew > 0)
                {
                    print '<tr><td class="ui-widget-header ui-state-default">Renouvellement';
                    print '<td class="ui-widget-content" colspan=3>';
                    print '<table width=100%>';
                    print '<tr><th>Date du renouvellement<th>Dur&eacute;e<th colspan=3>Loyer';
                    foreach($this->renewCont as $key=>$val)
                    {
                        print "<tr><td>".date('d/m/Y',$val['dateRenew']);
                        print "    <td>".$val['dureeRenew']." Mois";
                        print "    <td colspan=2>".price(round($val['loyerHT']*100)/100)." &euro;";
                    }
                    print "</table>";
                }

                //Affichage du taux, de la marge , du type d'échéance
                print '<tr><td class="ui-widget-header ui-state-default">D&eacute;tails : ';
                print '<td class="ui-widget-content" colspan=3>';
                print '<table width=100% style="border-collapse: collapse;">';
                print '<tr>';
                if ($user->rights->GA->VenteAffiche){
                    print '<th class="ui-widget-header ui-state-hover">Tx Financement';
                }
                if ($user->rights->GA->MargeAffiche){
                    print '<th class="ui-widget-header ui-state-hover">Tx Marge';
                }
                print '<th class="ui-widget-header ui-state-hover">Paiements /an';
                print '<th class="ui-widget-header ui-state-hover">Total HT';
                print '<th class="ui-widget-header ui-state-hover">Echu ?';
                print '<th class="ui-widget-header ui-state-hover">Dur&eacute;e (mois)';
                print '<th class="ui-widget-header ui-state-hover">Loyer';
                foreach ($this->finDetail as $ligneId => $val)
                {
                    $tauxFin = $val['tauxFin'];
                    $tauxMarge = $val['tauxMarge'];
                    $NbIterAn = $val['NbIterAn'];
                    $total_ht = $val['total_ht'];
                    $echu = $val['echu'];
                    $duree = $val['duree'];
                    $loyer = $val['loyer'];
                    print '<tr>';
                    if ($user->rights->GA->VenteAffiche){
                        print '<td align="center" class="ui-widget-content">'.round($tauxFin*100)/100 ." %";
                    }
                    if ($user->rights->GA->MargeAffiche){
                        print '<td align="center" class="ui-widget-content">'.round($tauxMarge*100)/100 ." %";
                    }
                    print '<td align="center" class="ui-widget-content">'.$NbIterAn;
                    print '<td align="center" class="ui-widget-content">'.price(round($total_ht*100)/100). " &euro;";
                    print '<td align="center" class="ui-widget-content">'.($echu == 0?'non':'oui');
                    print '<td align="center" class="ui-widget-content">'.$duree;
                    print '<td align="center" class="ui-widget-content">'.price(round($loyer*100)/100). " &euro;";
                }
                print "</table>";
//TODO Avenant
            }
    }
    public function displayButton($nbofservices)
    {
        /*************************************************************
         * Boutons Actions
         *************************************************************/
        global $langs, $conf, $user;
        $html = "";
        if ($user->societe_id == 0)
        {
            $html .=  '<div class="tabsAction">';

            if ($this->statut == 0)
            {
                //if ($user->rights->contrat->creer) $html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
                if ($user->rights->GA->contrat->Valider && $this->total_ht > 0 && $this->cessionnaire_refid ."x" != "x" && $this->fournisseur_refid."x" != 'x') $html .=  '<a class="butAction" href="#" id="ValidateContrat">'.$langs->trans("Validate").'</a>';
                $html .= "<script>";
                $html .= "var urlBase='".DOL_URL_ROOT."/Babel_GA/ajax/contrat_validate-xmlresponse.php';";
                $html .= "var contratFinID='".$this->id."';";
                $html .= <<<EOF
                jQuery(document).ready(function(){
                    jQuery('#ValidateContrat').click(function(){
                        jQuery('#validateDialog').dialog('open');
                    });
                    jQuery('#validateDialog').dialog({
                        autoOpen: false,
                        minWidth: 760,
                        width: 760,
                        modal: true,
                        title: "Validation du contrat",
                        buttons: {
                            "OK": function() {
                                //1 validate Form
                                if (jQuery('#formDialogValid').validate().form())
                                {
                                    var data = jQuery('#formDialogValid').serialize();
                                    jQuery.ajax({
                                        url: urlBase,
                                        datatype: "xml",
                                        data: 'action=validateContrat&id='+contratFinID+'&'+data,
                                        success: function(msg){
                                            if (jQuery(msg).find('OK').text()=='OK')
                                            {
EOF;
                $html .=  'location.href="'.$_SERVER['PHP_SELF'].'?id='.$this->id.'";';
                $html .= <<<EOF

                                            } else {
                                                console.log(jQuery(msg).find('KOText').text());
                                            }
                                        }

                                    })

                                }
                            },
                            "Annuler": function(){
                                jQuery('#validateDialog').dialog('close');
                            }
                        },
                        open: function(){
                            jQuery.ajax({
                                url:urlBase,
                                async: false,
                                data: 'id='+contratFinID,
                                datatype:'xml',
                                success: function(msg){
                                    //console.log(msg)
                                    var html="<form id='formDialogValid'><table width='100%'><thead>";
EOF;
                    if ($conf->global->CONTRATVALIDATE_CREATE_FOURN_FACT==1)
                    {
                        $html .= "html +='<tr><th style=\"padding: 15px; color: white;\">Ref facture fournisseur';\n";
                        $html .= "html +='    <th><input type=\"text\" id=\"factFournRef\" class=\"required\" name=\"factFournRef\">';\n";
                        $html .= "html +='<tr><td colspan=\"2\">&nbsp;';";
                        $html .= "html +='<tr><th style=\"padding: 15px;  color: white;\" colspan=\"2\">Param&egrave;tres de location';\n";
                        $html .= "html += '</thead><tbody>';\n";
                    } else {
                        $html .= "html +='<tr><th style=\"color: white;padding: 15px; \" colspan=2>Param&egrave;tres de location';\n";
                        $html .= "html += \"</thead><tbody>\";\n";
                    }
                $html .= <<<EOF
                                     html += "       <tr><th class='ui-widget-header ui-state-default'>Designation</th> \
                                                         <th class='ui-widget-header ui-state-default'>serial</th> \
                                              ";
                                              // TODO colspan=4 dans la facture fourn
//                                                         <th class='ui-widget-header ui-state-default'>Qte en Stock \
//                                                         <th class='ui-widget-header ui-state-default'>Qte ds le stock de location \
//                                                         <th class='ui-widget-header ui-state-default'>Forcer la commande \

                                    var j=0;
                                    var prodList = jQuery(msg).find('prod').each(function(){
                                        var qte = jQuery(this).attr('qte');
                                        var design = jQuery(this).text();
                                        var qteStockDispo = jQuery(this).attr('qteStockDispo');
                                        var id = jQuery(this).attr('id');
                                        var qteGADispo = jQuery(this).attr('qteGADispo');
                                        for(var i=0; i<qte ;i++)
                                        {
                                            j++;
                                            html += "<tr><td nowrap class='ui-widget-content'><input type='hidden' name='fk_product-"+j+"' id='fk_product-"+j+"' value='"+id+"'>"+design+" \
                                                         <td align='center' class='ui-widget-content'><input name='serial-"+j+"' id='serial-"+j+"'> \
                                                    ";
//                                                         <td align='center' class='ui-widget-content'>"+qteStockDispo+" \
//                                                         <td align='center' class='ui-widget-content'>"+qteGADispo+" \
//                                                         <td align='center' class='ui-widget-content'><input name='forceFournCom-"+j+"' id='forceFournCom-"+j+"' type='checkbox'> \
                                        }

                                    });
                                        html += "</tbody></table></form>";
                                        jQuery('#validContent').replaceWith("<div id='validContent'>"+html+"</div>");
                                }
                            })
                            //1 serial par ligne de produit (attn quantity)
                            //2 Prendre produit dans le Stock ou pas (et stock de location)
                            //3 Faire commande fourn si necessaire
                            //4 Commencer la location

                        }

                    });
                });
EOF;
                $html .= "</script>";
                $html .= "<div id='validateDialog'>";
                $html .= "<div id='validContent'>";
                $html .= "<img src='".DOL_URL_ROOT.'/theme/'.$conf->theme."/img/ajax-loader.gif'>";
                $html .= "</div>";
                $html .= "</div>";
            }

            if ( $this->statut > 0)
            {
                if ($user->rights->GA->contrat->CreateAvenant) $html .=  '<a class="butAction" id="avenantContrat" href="#">'.$langs->trans("Avenant").'</a>';
                $html .= <<<EOF
                    <script>
                    jQuery(document).ready(function(){
                        jQuery('#avenantContrat').click(function(){
                            jQuery('#avenantDialog').dialog('open');
                        });

                        jQuery('#avenantDialog').dialog({
                            autoOpen: false,
                            minWidth: 760,
                            width: 760,
                            modal: true,
                            title: "Cr&eacute;er un avenant au contrat",
                            buttons: {
                            "OK": function() {
EOF;
                $html .= "var urlBase='".DOL_URL_ROOT."/Babel_GA/ajax/contrat_validate-xmlresponse.php';";
                $html .= <<<EOF
                                if (jQuery('#formDialogRenew').validate().form())
                                {
                                    var data = jQuery('#formDialogAvenant').serialize();
                                    jQuery.ajax({
                                        url: urlBase,
                                        datatype: "xml",
                                        data: 'action=avenantContrat&id='+contratFinID+'&'+data,
                                        success: function(msg){
                                            if (jQuery(msg).find('OK').text()=='OK')
                                            {
EOF;
                $html .=  'location.href="'.$_SERVER['PHP_SELF'].'?id='.$this->id.'";';
                $html .= <<<EOF

                                            } else {
                                                console.log(jQuery(msg).find('KOText').text());
                                            }
                                        }

                                    })

                                }
                            },
                            "Annuler": function(){
                                jQuery('#avenantDialog').dialog('close');
                            }
                        },

                        });

                    });
                    </script>
EOF;
                $html .= "<div id='avenantDialog'><form id='formDialogAvenant' onsubmit='return false' method='post' action='#'>";
 //TODO box produit + box ajout
                $html .= '<table width=100% cellpadding=15>
                              <tr><th class="ui-state-default ui-widget-header" colspan=1>Date de l\'avenant:</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dateRenew" class="requiredNoBR FRDateNoBR" name="dateRenew"></td> ';
                $html .= '    <tr><th class="ui-state-default ui-widget-header" colspan=1>Renouvellement pour (en mois)</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dureeRenew" class="requiredNoBR  nombreentierNoBR sup1NoBR" name="dureeRenew"></td> ';
                $html .= '</table>';
                $html .= "</form></div>";
            }


            if ($this->statut > 0)
            {
                if ($user->rights->GA->contrat->RenewContrat) $html .=  '<a id="renewContrat" class="butAction" href="#">'.$langs->trans("Renouveler").'</a>';
                $html .= <<<EOF
                    <script>
                    jQuery(document).ready(function(){
                        jQuery('#renewDialog').dialog({
                            autoOpen: false,
                            minWidth: 760,
                            width: 760,
                            modal: true,
                            title: "Renouvellement du contrat",
                            buttons: {
                            "OK": function() {
EOF;
                $html .= "var urlBase='".DOL_URL_ROOT."/Babel_GA/ajax/contrat_validate-xmlresponse.php';";
                $html .= <<<EOF
                                if (jQuery('#formDialogRenew').validate().form())
                                {
                                    var data = jQuery('#formDialogRenew').serialize();
                                    jQuery.ajax({
                                        url: urlBase,
                                        datatype: "xml",
                                        data: 'action=renewContrat&id='+contratFinID+'&'+data,
                                        success: function(msg){
                                            if (jQuery(msg).find('OK').text()=='OK')
                                            {
EOF;
                $html .=  'location.href="'.$_SERVER['PHP_SELF'].'?id='.$this->id.'";';
                $html .= <<<EOF

                                            } else {
                                                console.log(jQuery(msg).find('KOText').text());
                                            }
                                        }

                                    })

                                }
                            },
                            "Annuler": function(){
                                jQuery('#renewDialog').dialog('close');
                            }
                        },

                        });
                        jQuery('#renewContrat').click(function(){
                            jQuery('#renewDialog').dialog('open');
                        });

                    });
                    </script>
EOF;
                $html .= "<div id='renewDialog'><form id='formDialogRenew' onsubmit='return false' method='post' action='#'>";
//TODO datepicker pour date
                $html .= '<table width=100% cellpadding=15>
                              <tr><th class="ui-state-default ui-widget-header" colspan=1>Date du renouvellement:</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dateRenew" class="requiredNoBR FRDateNoBR" name="dateRenew"></td> ';
                $html .= '    <tr><th class="ui-state-default ui-widget-header" colspan=1>Renouvellement pour (en mois)</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dureeRenew" class="requiredNoBR  nombreentierNoBR sup1NoBR" name="dureeRenew"></td> ';
                $html .= '</table>';
                $html .= "</form></div>";
            }

            //$html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=close">'.$langs->trans("Cloturer").'</a>';
            if ($this->statut > 0)
            {
                $html .=  '<a class="butAction" href="#" id="clotureGA">'.$langs->trans("Cloturer").'</a>';
            }
            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $this->statut == 0) || $user->rights->contrat->supprimer || $user->rights->GA->contrat->Effacer)
            {
                $html .=  '<a class="butActionDelete" href="fiche.php?id='.$this->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            $html .=  "</div>";
            $html .=  '<br>';
        }
        return($html);
    }
    public function active_line($user, $line_id, $date, $duree='')
    {
        global $langs,$conf;

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4,";
        $sql.= " date_ouverture = '".$this->db->idate($date)."',";
        if ($duree) $sql.= " date_fin_validite = 'date_add(date_ouverture, interval ".$duree." month)',";
        $sql.= " fk_user_ouverture = ".$user->id.",";
        $sql.= " date_cloture = null";
        $sql.= " WHERE rowid = ".$line_id;// . " AND (statut = 0 OR statut = 3 OR statut = 5)";

        dol_syslog("Contrat::active_line sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_SERVICE_ACTIVATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            $this->db->commit();
            return 1;
        } else {
            $this->error=$this->db->lasterror();
            dol_syslog("Contrat::active_line error ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }
    public $facFournRef;
    public function validate($data=array())
    {
        global $lang, $conf, $user;
        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 1 ,date_valid=now()";
        $sql .= " WHERE rowid = ".$this->id. " AND statut = 0";
        $resql = $this->db->query($sql) ;
        //Cloturer la commande

        $sql = "SELECT * FROM Babel_GA_li_cont_co WHERE contrat_refid = ".$this->id;
        $resql1 = $this->db->query($sql) ;
        while($res = $this->db->fetch_object($resql1))
        {
            require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
            $comTmp = new Commande($this->db);
            $comTmp->claimSpecialValidation = 1;
            $comTmp->cloture($user);
            if($conf->global->CONTRATVALIDATE_CREATE_FACT)
            {
                $comTmp->classer_facturee();
            }
        }


        if ($resql)
        {

            if (count($data)>0)
            {
                $duree =  $this->duree;
                $dateFin = ' date_add(dateDeb,interval   '.$duree.' month) ';
                $dateDeb = ' now() ';
                $prodTmp = new Product($this->db);
                $entrepot_refid = $conf->global->BABELGA_STOCKLOC;
                $statut = 1;
                $period_id =  $this->period_id;
                $echu =  $this->echu;
                $cessionnaire_refid = $this->cessionnaire_refid;
                $fournisseur_refid = $this->fournisseur_refid;
                $user_resp_refid = $user->id;
                $client_refid = $this->socid;

                $montantTotHTAFinancer = $this->total_ht;
                $tauxMarge = 0;
                $tauxFinancement = 0;

                foreach($this->lignes as $key=>$val)
                {
                    $tauxMarge = ($val->tauxMarge > 0?$val->tauxMarge:$tauxMarge);
                    $tauxFinancement = ($val->tauxFinancement > 0?$val->tauxFinancement:$tauxFinancement);
                    $this->active_line($user, $val->id, $dateDeb, $duree);
                    // active toutes les lignes de contrats

                }
                $ent = 'null';
                if ($conf->global->BABELGA_STOCKLOC."x" != "x") $ent=$conf->global->BABELGA_STOCKLOC;
                //Create emp stock
                $requete = "INSERT INTO Babel_GA_entrepot_location
                                          (debutLoc,
                                           finLoc,
                                           entrepot_location_refid,
                                           Loyer,
                                           TotalFinancer,
                                           TxMarge,
                                           TxFinancement,
                                           periodicite_refid,
                                           duree,
                                           contrat_refid)
                                    VALUES( '".$dateDeb."',
                                            '".$dateFin."',
                                            ".$ent.",
                                            ".preg_replace('/,/','.',$this->getLoyerTot()).",
                                            ".$montantTotHTAFinancer.",
                                            ".$tauxMarge.",
                                            ".$tauxFinancement.",
                                            ".$period_id. ",
                                            ".$duree. ",
                                            ".$this->id.")";
//TODO location historique
                $sql = $this->db->query($requete);
                if ($sql)
                {
                    $comFourn = "";
                    $factCess="";
                    $factFourn="";
                    if ($conf->global->CONTRATVALIDATE_CREATE_FOURN_COMM == 1)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
                        $comFourn = new CommandeFournisseur($this->db);
                        $comFourn->socid = $this->fournisseur_refid;
                        $tmpFournObj = new Societe($this->db);
                        $tmpFournObj->fetch($this->fournisseur_refid);
                        $comFourn->ref = $comFourn->getNextNumRef($tmpFournObj);
                        $return = $comFourn->create($user);
                        $comFourn->UpdateNote($user, "Cr&eacute;er automatiquement &agrave; partir du contrat ".$this->ref, "Contrat ".$this->ref);
                        $requete = 'INSERT INTO Babel_GA_li_cont_coFourn (commande_refid, contrat_refid) VALUES ('.$comFourn->id.','.$this->id.')';
                        $this->db->query($requete);
                    }
                    if ($conf->global->CONTRATVALIDATE_CREATE_FOURN_FACT == 1)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
                        $factFourn = new FactureFournisseur($this->db);
                        $factFourn->socid = $this->fournisseur_refid;
                        $tmpFournObj = new Societe($this->db);
                        $tmpFournObj->fetch($this->fournisseur_refid);
                        $factFourn->date = time();
                        $factFourn->ref = $this->facFournRef;
                        $factFourn->date_echeance = time();
                        $factFourn->note = "Cr&eacute;er automatiquement &agrave; partir du contrat ".$this->ref;

                        $return = $factFourn->create($user);
                        $requete = 'INSERT INTO Babel_GA_li_cont_faFourn (fact_refid, contrat_refid) VALUES ('.$factFourn->id.','.$this->id.')';
                        $this->db->query($requete);

//SI commande & facture fourn => lié les 2

                    }
                    if ($conf->global->CONTRATVALIDATE_CREATE_FACT == 1)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
                        $factCess = new Facture($this->db);
                        $factCess->socid = $this->cessionnaire_refid;
                        $factCess->contratid = $this->id;
//                        $factCess->note = "Cr&eacute;er automatiquement &agrave; partir du contrat ".$this->ref;
                        $tmpCess = new Societe($this->db);
                        $tmpCess->fetch($this->cessionnaire_refid);
                        $factCess->number         = $factCess->getNextNumRef($tmpCess);
                        $factCess->ref         = $factCess->number;
                        $factCess->date           = $dateDeb;
                        $factCess->cond_reglement_id = ($conf->global->BABELGA_LIMREG_FACTURE.'x' != 'x'?$conf->global->BABELGA_LIMREG_FACTURE:0);

                        $factCess->note_public    = "Facturation du contrat ".$this->ref;
                        $factCess->note           = "Cr&eacute;er automatiquement &agrave; partir du contrat ".$this->ref;
                        $factCess->ref_client     = "";
                        $factCess->modelpdf       = $conf->global->FACTURE_ADDON_PDF;
                        $factCess->projetid          = $this->fk_projet;
                        $factCess->mode_reglement_id = 0;
                        $factCess->remise_absolue    = 0;
                        $factCess->remise_percent    = 0;
                        $factCess->type              = 0;


                        $return = $factCess->create($user);

                        $requete = 'INSERT INTO Babel_GA_li_cont_fa (fact_refid, contrat_refid) VALUES ('.$factCess->id.','.$this->id.')';
                        $this->db->query($requete);

                    }


                    $GA_entrepot_refid=$this->db->last_insert_id('BABELGA_STOCKLOC');
                    foreach($data as $key=>$arrVal)
                    {
                        //save to DB => Babel_GA_entrepotdet
                        $fk_product = $arrVal['fk_product'];
                        $serial = $arrVal['serial'];
                        $force_commande = $arrVal['force_commande'];
                        $prodTmp->fetch($fk_product);
                        $libelle = $prodTmp->libelle;
                        //create GA stock entry
//TODO mettre taux Fin et marge Babel_GA_entrepotdet car si multitaux

//lier au contrat
                        $requete = " INSERT INTO Babel_GA_entrepotdet (libelle,
                            fk_product,
                            serial,
                            cessionnaire_refid,
                            fournisseur_refid,
                            client_refid,
                            dateDeb,
                            dateFin,
                            user_resp_refid,
                            statut,
                            GA_entrepot_refid) VALUES ( '".$libelle."' ,
                             ".$fk_product." ,
                             '".$serial."' ,
                             ".$cessionnaire_refid." ,
                             ".$fournisseur_refid." ,
                             ".$client_refid." ,
                             '".$dateDeb."' ,
                             '".$dateFin."' ,
                             ".$user_resp_refid." ,
                             ".$statut." ,
                             ".$GA_entrepot_refid." )";

                        $sql = $this->db->query($requete);
                        if ($sql)
                        {

                            $this->db->commit();
                        }  else {
                            $this->error = "Error SQL : Requete Location ".$this->db->error();
                            $this->db->rollback();
                            return(-1);
                        }
                        //create stock entry si create Stock
                        if ($conf->global->CONTRATGAVALIDATE_ENTER_STOCK && $conf->global->BABELGA_STOCKLOC."x" !='x')
                        {
                            //On ouvre le stock
                            $prodTmp->correct_stock($user, $conf->global->BABELGA_STOCKLOC, 1,0);
                            //Puis on retire
                            $prodTmp->correct_stock($user, $conf->global->BABELGA_STOCKLOC, 1,1);
                        }
                        //commande Fourn
                        if ($conf->global->CONTRATVALIDATE_CREATE_FOURN_COMM == 1)
                        {
                           $ret = $comFourn->addline($libelle,
                                               0,
                                               1,
                                               "19.6",
                                               $fk_product,
                                               0,
                                               "",
                                               0,
                                               'HT',
                                               0);
                        }
                        //creation de la ligne facture Fourn
                        if ($conf->global->CONTRATVALIDATE_CREATE_FOURN_FACT == 1)
                        {
                           $ret = $factFourn->addline($libelle. " ".$serial,
                                               0,
                                               "19.6",
                                               1,
                                               $fk_product,
                                               0,
                                               "",
                                               "",
                                               1,
                                               '',
                                               'HT');
                        }

                    }

//creation des lignes de facture
                    if ($conf->global->CONTRATVALIDATE_CREATE_FACT == 1)
                    {
                        $marge=0;
                        $this->fetch_lignes();
                        $lines = $this->lignes;

                        foreach ($lines as $i=>$val)
                        {
                            if ($val->fk_product ."x" != "x")
                            {
                                $result = $factCess->addline(
                                    $factCess->id,
                                    $val->libelle,
                                    $val->subprice,
                                    $val->qty,
                                    "19.6",
                                    $val->fk_product,
                                    $val->remise_percent,
                                    "",
                                    "",
                                    0,
                                    $val->info_bits,
                                    $val->fk_remise_except
                                );
                            } else {
                                $result = $factCess->addline(
                                    $factCess->id,
                                    utf8_decode($val->libelle),
                                    $val->subprice,
                                    1,
                                    "19.6",
                                    "",
                                    $val->remise_percent,
                                    "",
                                    "",
                                    0,
                                    $val->info_bits,
                                    $val->fk_remise_except
                                );

                            }
                            $margeTmp = $val->tauxMarge / 100 * $val->subprice;
                            $marge += $margeTmp;
                        }
                        //Ajoute la marge
                        $result = $factCess->addline(
                            $factCess->id,
                            utf8_decode("Apport d'affaire"),
                            $marge,
                            1,
                            "19.6",
                            "",
                            0,
                            "",
                            "",
                            0,
                            0,
                            null
                        );
                    }
//TODO
                    //If main module zimbra
                    //Zimbra
                    //recupere la date 3 mois avant la fin => Zimbra
                    $delayWarn = $conf->global->CONTRATWARN_DELAY=3;
                    $requete = "SELECT date_sub(date_add('".$dateDeb."', interval ".$duree." month) , interval ".$delayWarn." month) as dateWarn";
                    $sql1 = $this->db->query($requete);
                    $res1 = $this->db->fetch_object($sql1);
                    $dateWarn = $res1->dateWarn;

                    return(1);
                } else {
                    $this->db->rollback();
                    $this->error="Erreur SQL : insertion des stocks : " . $this->db->lasterror . " ".$this->db->lastqueryerror;
                    return -1;
                }



                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('CONTRACT_VALIDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

            } else {
                $this->db->rollback();
                $this->error="Erreur SQL : ".$this->db->lasterror . " ".$this->db->lastqueryerror;
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error="Erreur SQL : " . $this->db->lasterror . " ".$this->db->lastqueryerror;
            return -1;
        }

    }
    public $renewCont = array();
    public function renewList()
    {
        $requete = "SELECT * FROM Babel_GA_contrat_renouvellement WHERE contrat_refid = ".$this->id;
        $res=$this->db->query($requete);
        $iter=0;
        while ($sql = $this->db->fetch_object($res))
        {
            $this->renewCont[$res->id]['dateRenew']=$sql->date_renouvellement;
            $this->renewCont[$res->id]['dureeRenew']=$sql->duree_renouvellement;
            $this->renewCont[$res->id]['loyerHT']=$sql->loyerHT;
            $iter++;
        }
        return($iter);
    }



    public function create_from_propale($user,$propale_id)
    {
        //Contrat de financement
        dol_syslog("ContratGA::create_from_propale propale_id=$propale_id");
        global $langs,$conf;

        require_once(DOL_DOCUMENT_ROOT."/Babel_GA/PropalGA.class.php");
        $propal = new PropalGA($this->db);
        $res = $propal->fetch($propale_id);

        //Creer le contrat
        $this->commercial_signature_id = $propal->commercial_signataire_refid;
        $this->commercial_suivi_id = $propal->user_author_id; // Le créateur de la propal

        $this->socid = $propal->socid;
        $soc = new Societe($this->db);
        $soc->id = $this->socid;
        $soc->fetch($soc->id);
        $this->date_contrat = time();
        $this->ref = $this->getNextNumRef($soc);
        $this->linkedTo = "p".$propale_id;
        $this->typeContrat = 6;
        $this->fk_projet = $propal->projetidp;
        $this->is_financement = $propal->isFinancement;
        $this->fournisseur_refid = $propal->fournisseur_refid;
        $this->client_signataire_refid = $propal->client_signataire_refid;

        $result = $this->create($user,$langs,$conf);
        $contratId = $result;

        if ($result > 0)
        {
            //ajoute le signataire
            //
            $result=$this->add_contact($this->client_signataire_refid,'BILLING','external');
            //liaison GA contrat -> propale

            $requete = 'INSERT INTO Babel_GA_li_cont_pr (propal_refid, contrat_refid) VALUES ('.$propale_id.','.$this->id.')';
            $this->db->query($requete);



            //Creer les lignes de contrat
            foreach ($propal->lignes as $key=>$val)
            {
                //On recupere la ligne
                //Si fk_prod => produit

                $tmpId = $this->addline($val->desc, $val->subprice,
                               $val->qty, $val->tva_tx,
                               $val->fk_product, 0,
                               time(), "", 'HT',
                               $val->total_ttc, 0);
                //Manque le tx fin
                //On recupere le financement de la ligne
                //Creer le financement
                if ($tmpId > 0)
                {
                    $requete = " INSERT INTO Babel_GA_contrat (
                                    contratdet_refid,
                                    isTx0,
                                    montantTotHTAFinancer,
                                    tauxMarge,
                                    tauxFinancement,
                                    financement_period_refid,
                                    echu,
                                    client_signataire_refid,
                                    duree) VALUES (
                                     ".$tmpId." ,
                                     ".$val->isTx0." ,
                                     '".$val->total_ht."' ,
                                     '".$val->tauxMarge."' ,
                                     '".$val->tauxFinancement."' ,
                                     ".$val->financement_period_refid." ,
                                     ".$val->echu." ,
                                     ".$propal->client_signataire_refid.",
                                      ".$val->duree." )";
                     $sql = $this->db->query($requete);
                } else {
                    print "<div class='error ui-state-error'>Erreur dans la cr&eacute;ation des lignes du contrat</div>";
                }
            }
            return $contratId;
        } else {

            return (-1);
        }
    }

}

?>