<?php
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
class ContratLocProdGA extends contrat{

    public function ContratLocProdGA($db) {
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
    public $typeContrat=5;

    public $total_ht;

    public function fetch($id)
    {

        $sql = "SELECT rowid, statut, ref, fk_soc, ".$this->db->pdate("mise_en_service")." as datemise,";
        $sql.= " fk_user_mise_en_service, ".$this->db->pdate("date_contrat")." as datecontrat,";
        $sql.= " fk_user_author, ".$this->db->pdate("fin_validite")." as datefin,";
        $sql.= " fk_projet, type,prorata,facturation_freq,";
        $sql.= " modelPdf,";
        $sql.= " linkedTo,";
        $sql.= " is_financement,cessionnaire_refid,fournisseur_refid, tva_tx,";
        $sql.= " fk_commercial_signature, fk_commercial_suivi,";
        $sql.= " note, note_public, date_valid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$id;

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

                $this->prorata           =$result['prorata'];


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
                $this->societe->fetch($result["fk_soc"]);
                $this->total_ht = 0;
                $this->facturation_freq = $result['facturation_freq'];

                $this->is_financement = $result['is_financement'];
                $this->cessionnaire_refid = $result['cessionnaire_refid'];
                $this->fournisseur_refid = $result['fournisseur_refid'];
                $this->duree = 0;
                $this->period_id = 0;
                $this->echu = 0;
                $this->tva_tx = round($result['tva_tx']*10)/10;
                $this->type = $result['type'];
                //$this->typeContrat = $result['type'];


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
        return($html);
    }

    public function displayLine()
    {
        global $conf, $form,$langs;
        $html = "";


        $granTot = 0;
        $granTotLoyer=0;
        $requete = "SELECT sum(total_ht) as tht FROM ".MAIN_DB_PREFIX."contratdet  WHERE fk_contrat = ".$this->id. " AND fk_product is not NULL";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $granTot = $res->tht;
        //if ($this->mise_en_service)
        $nbMonth = $this->datediff($this->mise_en_service,$this->date_fin_validite);
        $granTotLoyer = $granTot / $nbMonth;
//TODO loyer du mois en cours

        $html .= "<table cellpadding=15 width=100%><tr><th class='ui-widget-header ui-state-default' width=20%>Total location<td width=30% class='ui-widget-content'><span id='totalContrat'>".price($granTot)." &euro;</span>";
//TODO loyer du mois en cours
        $html .= "<th class='ui-widget-header ui-state-default' width=20%>Loyer mensuel<br/>(".date("m/Y").")<td width=30%  class='ui-widget-content'><span id='totalLoyer'>".price($granTotLoyer)." &euro;</span>";
        $html .= "</table>";

        if($this->statut==0)
        {
            $html .= "<table cellpadding=15 width=100%><tr><th width=20% class='ui-state-default ui-widget-header' colspan=2>Ajouter des produits au contrat</th>";
            $html .= "<td class='ui-widget-content' colspan=1 style='padding:0;'><form id='addline' method='POST' action='#' onSubmit='return(false);'>";
            $html .= "<table width=100% class='noborder' cellpadding=15>";
            $html .= "<tr><th class='ui-widget-header'>";
            $html .= "Qt&eacute;.";
            $html .= "    <th class='ui-widget-header'>";
            $html .= "PU HT";
            $html .= "    <th class='ui-widget-header' colspan=2>";
            $html .= "P&eacute;riode unitaire";
            $html .= "    <th class='ui-widget-header' colspan=1>";
            $html .= "Dur Loc";
            $html .= "    <th colspan=2 class='ui-widget-header' align=center>Ref ou label";
            $html .= "    <th class='ui-widget-header' colspan=1>";
            $html .= "Num. s&eacute;rie";
            $html .= "    <th class='ui-widget-header' align=center>Action";
            $html .= "<tr><td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $html .= "        <input type='text' id='qty' name='qty'  style='width: 4em; text-align:center;' value='1'>";
            $html .= "    <td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $html .= "        <input type='text' id='pu_ht' name='pu_ht'  style='width: 4em; text-align:center;' value=''>";
            $html .= "    <td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $html .= "        <input id='duration_value' name='duration_value' style='width: 4em; text-align:center;' value=''  >";
            $html .= "    <td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $objDuration=array('m'=>"&nbsp;Mois",'d'=>"&nbsp;Jour(s)",'y'=>"&nbsp;Ann&eacute;e(s)",'w'=>"&nbsp;Semaine(s)");
            $html .= '<span title="Cliquer pour changer la p&eacute;riode">&nbsp;';
            $html .= '<span id="addChangeDurLoc"><span id="duration_text">Mois</span><input type="hidden" id="duration_unit" name="duration_unit" value="m"></span></span></td>';
            $html .= "    <td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $html .= "    <table width=100%><tr><td>D&eacute;but<td>";
            $dateDeb = date('d/m/Y',$this->mise_en_service);

            $html .= "        <input id='debut_loc' name='debut_loc' style='width: 7em; text-align:center;' value='".($this->mise_en_service>0?$dateDeb:'')."' class='datepicker' >";
            $html .= "    <tr><td>Fin<td>";

            $dateFin = date('d/m/Y',$this->date_fin_validite);
            $html .= "        <input id='fin_loc' name='fin_loc' style='width: 7em; text-align:center;' value='".($this->date_fin_validite>0?$dateFin:'')."'  class='datepicker' >";
            $html .= "    </table><td class='ui-widget-content' align=center valign=middle style='padding-top:5px; '>";
            if($conf->global->PRODUIT_MULTIPRICES == 1)
                $html .= $form->select_produits('','p_idprod',$conf->produit->limit_size,$this->societe->price_level,1,true,false,false,false);
            else
                $html .= $form->select_produits('','p_idprod',"",$conf->produit->limit_size,false,1,true,true,false,false);
            $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idprod"></div><td width=10>';
            $html .= "<td class='ui-widget-content' align=center valign=middle style='padding-top:5px;'>";
            $html .= "        <input type='text' id='serial' name='serial'  style='width: 10em; text-align:center;' value=''>";

            $html .= "<td align=center style='padding:0;' class='ui-widget-content'><button style='min-width:102px; padding: 5px 10px;' class='button butAction ui-widget-header ui-corner-all ui-state-default' id='LocaddLine' >
                            <span class='ui-icon ui-icon-circle-plus' style='float: left; margin-right: 10px;'></span>
                            <span style='float: left;'>Ajouter</span>
                          </button>";
            $html .= "</th>";
            $html .= "</table></form>";
            $html .= "</table>";
            $objDuration=array('m'=>"&nbsp;Mois",'d'=>"&nbsp;Jour(s)",'y'=>"&nbsp;Ann&eacute;e(s)",'w'=>"&nbsp;Semaine(s)");
        }
        $html .= <<<EOF
                    <style>
                        #addChangeDurLoc{ cursor: pointer;}
                        #prorataEditable{ cursor: pointer;}
                    </style>
                    <script>
                    jQuery(document).ready(function(){
                        jQuery.datepicker.setDefaults(jQuery.extend({
                            showMonthAfterYear: false,
                            dateFormat: 'dd/mm/yy',
                            changeMonth: true,
                            changeYear: true,
                            showButtonPanel: true,
                            buttonImage: 'cal.png',
                            buttonImageOnly: true,
                            showTime: false,
                            duration: '',
                            constrainInput: false,
                        }, jQuery.datepicker.regional['fr']));

                        var arrNex = new Array();
                            arrNex['m']='y';
                            arrNex['y']='d';
                            arrNex['d']='w';
                            arrNex['w']='m';
                        var arrDuration = new Array();
                            arrDuration['m']='Mois';
                            arrDuration['y']='Ann&eacute;e(s)';
                            arrDuration['d']='Jour(s)';
                            arrDuration['w']='Semaine(s)';
                        jQuery('#addChangeDurLoc').click(function(){
                            var curVal = jQuery(this).find('input#duration_unit').val();
                            var nexVal = arrNex[curVal];
                            if (nexVal+'x' == 'x') nexVal = 'm';
                            var textVal = arrDuration[nexVal];
                            jQuery(this).find('input#duration_unit').val(nexVal);
                            jQuery(this).find('span#duration_text').html(textVal);
                        });
                        jQuery('.datepicker').datepicker();
                    });
                    </script>
EOF;

        //liste du matériel
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$this->id. " AND fk_product is not NULL";
        $sql = $this->db->query($requete);
        if ($sql)
        {
            $html .= "<center><br/>";
            $html .= "<table cellpadding=5 width=90% id='prodTable'>";
            $html .= "<tr style='border: 2px Solid #0073EA;'>
                        <th width=50% class='ui-state-default ui-widget-header' colspan=9 style='padding: 15px;  font-size: 14px;'>Produits du contrat</th>";
            $html .= "<tr><th class='ui-widget-header ui-state-default'>D&eacute;signation<th class='ui-widget-header ui-state-default'>Date<th class='ui-widget-header ui-state-default'>PU HT</th><th  class='ui-widget-header ui-state-default'>Qt&eacute;<th class='ui-widget-header ui-state-default'>Total HT<th class='ui-widget-header ui-state-default'>&nbsp;";
            while ($res = $this->db->fetch_object($sql))
            {
                $html .= $this->display1Line($res);
            }
            $html .= "</table>";
            $html .= "</center>";
        }
       return ($html);
    }

    public function display1Line($res){
        $html = "";
        global $conf,$langs,$user;

        $prodTmp = new Product($this->db);
        $prodTmp->id = $res->fk_product;
        $prodTmp->fetch($prodTmp->id);
        $serial = false;
        $requete = "SELECT * FROM Babel_product_serial_cont WHERE element_id = ".$res->rowid." AND element_type='contratLOC'";
        $sql = $this->db->query($requete);
        $res1 = $this->db->fetch_object($sql);
        $serial = ($res1->serial_number."x" != "x"?$res1->serial_number:false);

        $html .= "<tr id='".$res->rowid."'><th width=50% align=left class='ui-widget-content' style='padding-left: 15px'><table><tr><td colspan=2>".$prodTmp->getNomUrl(1) . " ". $prodTmp->libelle.($serial."x"!="x"?"<tr><th style='font-weight:normal; color: white; padding: 1px 3px;' align=center width=60 nowrap>Num. S&eacute;rie<td style='font-weight:normal; padding: 1px 4px;'>".$serial:"")."</table>";
        $html .= "<td  class='ui-widget-content' align=center><table class='noborder'><tr>";
        $html.= "     <td >Du</td>";
        $html.= "     <td nowrap >".(strtotime($res->date_ouverture>0)?
                                                                    date('d/m/Y',strtotime($res->date_ouverture_prevue)):
                                                                    (strtotime($res->date_ouverture_prevue)>0?
                                                                        date('d/m/Y',strtotime($res->date_ouverture_prevue))
                                                                        :""
                                                                    )
                                                                    )."</td>";
        $html.= " <tr><td nowrap >Au</th>";
        $html.= "     <td nowrap >".(strtotime($res->date_cloture>0)?
                                                                    date('d/m/Y',strtotime($res->date_cloture)):
                                                                    (strtotime($res->date_fin_validite)>0?
                                                                        date('d/m/Y',strtotime($res->date_fin_validite))
                                                                        :""
                                                                    )
                                                                    )."</td></table>";
        $html .= "    <td class='ui-widget-content' align=center>
                      <table><tr><td colspan=2 nowrap id='pu_ht' align='center'>".price($res->subprice)." &euro;";
        $durOpt=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
        if (preg_replace('/[^0-9]*/',"",$res->prod_duree_loc)>1){
            $durOpt=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
        }
        $html .= "    <tr><td nowrap>Pour</td>
                      <td nowrap>".(preg_replace('/[^0-9]*/',"",$res->prod_duree_loc)>0?preg_replace('/[^0-9]*/',"",$res->prod_duree_loc):1)."&nbsp;". $durOpt[(preg_replace('/[0-9]*/',"",$res->prod_duree_loc)."x"=="x"?"m":preg_replace('/[0-9]*/',"",$res->prod_duree_loc))] ."</table>";

        $html .= "    <td class='ui-widget-content' id='qteText' align='center'>
                        <table style=''>
                            <tr><td rowspan=3 align=center width=16><span id='qteLigne'>".$res->qty."</span>";

//        if($this->statut==0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) && $user->rights->contrat->creer)
//        {
//            $html.=            "<tr><td width=16 id=".$res->rowid." align='center' class='plus ui-widget-header'><span style='padding:0; margin: -3px; margin-bottom: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-n'></span>
//                                <tr><td width=16 id=".$res->rowid." align='center' class='moins ui-widget-header'><span style='padding:0; margin: -3px; margin-top: -3px; cursor: pointer;' class='ui-icon ui-icon-carat-1-s'></span>";
//        }
        $html.=        "</table>";
        $html.= "         <td nowrap class='ui-widget-content' align='right'><span id='totalLigneContrat'>".price($res->total_ht)."&euro;</span>";
        if(($this->statut==0) || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) && $user->rights->contrat->creer)
        {
            $html.= "     <td align=center style='padding-left: 15px' class='ui-widget-content'><span class='editLoc'  style='cursor: pointer;' id='".$res->rowid."'>" . img_edit()."</span>";
            $html.= "     <span class='deleteLoc'  style='cursor: pointer;' id='".$res->rowid."'>" . img_delete()."</span>";
        } else {
            $html.= "     <td align=center style='padding-left: 15px' class='ui-widget-content'>";
        }
        return($html);
    }


    public function update_totalLigne($ligneId,$total_ht)
    {
            $tabprice=calcul_price_total(1, $total_ht, 0, $this->tva_tx, 0);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];

        $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet
                       SET total_ht = '".preg_replace('/,/','.',$total_ht)."',
                           tva_tx = '".preg_replace("/,/",".",$this->tva_tx)."',
                           total_tva = '".preg_replace("/,/",".",$total_tva)."',
                           total_ttc = '".preg_replace("/,/",".",$total_ttc)."'
                     WHERE rowid = ".$ligneId;
        $sql = $this->db->query($requete);

    }

    //Pu float
    //dateDeb Datedeb au format timestamp
    //datefin Datefin au format timestamp
    //duree unitaire (1m)

    public function getTotalLigne($pu,$dateDeb,$dateFin,$du_unit,$qte)
    {
//            var_dump($pu);
//            var_dump($dateDeb);
//            var_dump($dateFin);
//            var_dump($du_unit);
//            var_dump($qte);
        if ($dateDeb>$dateFin){
            $tmp = $dateDeb;
            $dateDeb = $dateFin;
            $dateFin = $tmp;
        }
        $dateDebOrig = $dateDeb;

        if ($this->prorata == 1)
        {
            //Calcul au jour
            //Combien de jour de location
            $nbJour = 0;
            $nbPeriod=0;

//            while ($dateDeb< $dateFin){
//                $dateDeb += "3600 * 24";
//                $nbJour += 1;
//            }
            //Quel est le prix jour pour le prorata
            $durUnit = preg_replace('/[0-9]*/',"",$du_unit);
            $durValue = preg_replace('/[^0-9]*/',"",$du_unit);
            $durOp=array("h"=> 1/7,"d"=> 1 ,"w"=> 5 ,"m"=> 30 ,"y"=>360);
            //Combien de period
            switch ($durUnit){
                case 'd':{
                    $iter = 0;
                    while ($dateDeb<= $dateFin){
                        if($iter%$durValue==0) $nbPeriod += 1;
                        $iter++;
                        $dateDeb += (3600 * 24);
                    }
                    $dateDeb -= (3600 * 24);
                }
                break;
                case 'w':{
                    $iter = 0;
                    while ($dateDeb<= $dateFin){
                        if($iter%$durValue==0) $nbPeriod += 1;
                        $iter++;
                        $dateDeb += (3600 * 24 * 7);
                    }
                    $dateDeb -= (3600 * 24 * 7);
                }
                break;
                case 'm':{
                    $remDate = date('m',$dateDeb);
                    $remDate2 = date('Y',$dateDeb);
                    while ($dateDeb<= $dateFin)
                    {
                        if(($remDate!=date('m',$dateDeb) && $remDate2!=date('Y',$dateDeb)) ) { $nbPeriod += 1;  }
//                        var_dump($remDate);
//                        var_dump(date('m',$dateDeb));
                        $remDate = date('m',$dateDeb);
                        $remDate2 = date('Y',$dateDeb);
                        $dateDeb += (3600 * 24);
                    }
                    $nbPeriod = floor($nbPeriod/$durValue);
                    $dateDeb -= (3600 * 24);
                }
                break;
                case 'y':{
                    $remDate = date('Y',$dateDeb);
                    while ($dateDeb<= $dateFin)
                    {
                        if(($remDate!=date('Y',$dateDeb)) ) { $nbPeriod += 1;  }
                        $remDate = date('Y',$dateDeb);
                        $dateDeb += (3600 * 24);
                    }
                    $nbPeriod = floor($nbPeriod/$durValue);
                    $dateDeb -= (3600 * 24);
                }
                break;
            }
            //Combien de jour en prorata
            $nbJourProrata=0;
            while ($dateDeb< $dateFin){
                $dateDeb += (3600 * 24);
                $nbJourProrata += 1;
            }
            if ($nbPeriod == 0 && $durUnit=='m' && ($dateFin - $dateDebOrig > 0))
            {
                $dateDeb = $dateDebOrig;
                while ($dateDeb<= $dateFin)
                {
                    $dateDeb += (3600 * 24);
                    $nbJourProrata += 1;
                }
            }
//            if($nbJourProrata > 0 ){
//                $nbPeriod ++;
//            }


            $pu_jour = $pu / ($durOp[$durUnit] * $durValue);
            //prorata
            $prorata = $nbJourProrata * $pu_jour * $qte;
            //total
            $total = $prorata + ($pu * $nbPeriod * $qte);

//            var_dump($prorata);
//            var_dump($nbJourProrata);
//            var_dump($nbPeriod);
//            var_dump($qte);
//            var_dump($pu_jour);
//            var_dump($total);


            $this->ligneNbPeriod=$nbPeriod;
            return($total);

        } else {
            //Calcul par periode
            //Quelle est la periode
            $durUnit = preg_replace('/[0-9]*/',"",$du_unit);
            $durValue = preg_replace('/[^0-9]*/',"",$du_unit);
            //Combien de periode
            $nbPeriod = 0;
//            var_dump($durUnit);
//            var_dump(date('d/m/Y',$dateDeb));
//            var_dump(date('d/m/Y',$dateFin));
//            var_dump($durUnit);
            switch ($durUnit){
                case 'd':{
                    $iter = 0;
                    while ($dateDeb<=$dateFin){
                        $dateDeb += (3600 * 24);
                        if($iter%$durValue==0) $nbPeriod += 1;
//                        print $nbPeriod."\n";
                        $iter++;
                    }
                }
                break;
                case 'w':{
                    $iter = 0;
                    while ($dateDeb<=$dateFin){
                        $dateDeb += (3600 * 24 * 7);
                        if($iter%$durValue==0) $nbPeriod += 1;
                        $iter++;
                    }
                }
                break;
                case 'm':{
                    $remDate = date('m',$dateFin);
                    $remDate2 = date('Y',$dateDeb);
                    while ($dateDeb<=$dateFin)
                    {
                        $dateDeb += (3600 * 24);
                        if(($remDate!=date('m',$dateDeb)) && ($remDate2!=date('Y',$dateDeb)) ) { $nbPeriod += 1;  }
                        $remDate2 = date('Y',$dateDeb);
                        $remDate = date('m',$dateDeb);
                    }
                    $nbPeriod = ceil($nbPeriod/$durValue);
                }
                break;
                case 'y':{
                    $remDate = date('Y',$dateFin);
                    while ($dateDeb<=$dateFin){
                        $dateDeb += (3600 * 24);
                        if(($remDate!=date('Y',$dateDeb)) ) { $nbPeriod += 1;  }
                        $remDate = date('Y',$dateDeb);
                    }
                    $nbPeriod = ceil($nbPeriod/$durValue);

                }
                break;
            }
            if ($nbPeriod == 0 && $durUnit=='m' && ($dateFin - $dateDebOrig > 0))
            {
                $nbPeriod++;
            }
            //total
            $total = $pu * $nbPeriod * $qte;
//            var_dump($pu);
//            var_dump($nbPeriod);
//            var_dump($qte);

            //total
            $this->ligneNbPeriod=$nbPeriod;
            return($total);

        }
    }

    public function displayAddLine($mysoc,$objp)
    {
        $html = "";
        return($html);
    }
    public function initDialog(){
        $html = "";
        global $mysoc, $form;
        $html .= $this->displayDialog();
        $html .= <<<EOF
        <script>
        jQuery('document').ready(function(){
                    var arrNex = new Array();
                    arrNex['m']='y';
                    arrNex['y']='d';
                    arrNex['d']='w';
                    arrNex['w']='m';
                    var arrDuration = new Array();
                    arrDuration['m']='Mois';
                    arrDuration['y']='Ann&eacute;e(s)';
                    arrDuration['d']='Jour(s)';
                    arrDuration['w']='Semaine(s)';

                    var ligneId = false;
                    jQuery('#editDialog').dialog({
                        modal: true,
                        title: "Modifier une ligne",
                        width: 635,
                        autoOpen: false,
                        open: function(){
                            //TODO Get Line Value
                            jQuery.ajax({
                                url:DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php",
                                data:'action=getLineDet&idLigneContrat='+ligneId+"&idContrat="+contratId,
                                datatype:'xml',
                                type:'POST',
                                cache:false,
                                success: function(msg){
                                    jQuery('#editDialog #qte').val(jQuery(msg).find('qty').text());
                                    jQuery('#editDialog #pu_ht').val(jQuery(msg).find('subprice').text());
                                    jQuery('#editDialog #serial').val(jQuery(msg).find('serial').text());
                                    if (jQuery(msg).find('date_ouverture').text()+"x"!="x"){
                                        jQuery('#editDialog #moddebut_loc').val(jQuery(msg).find('date_ouverture').text());
                                    } else if (jQuery(msg).find('date_ouverture_prevue').text()+"x"!="x"){
                                        jQuery('#editDialog #moddebut_loc').val(jQuery(msg).find('date_ouverture_prevue').text());
                                    }
                                    if (jQuery(msg).find('date_cloture').text()+"x"!="x"){
                                        jQuery('#editDialog #modfin_loc').val(jQuery(msg).find('date_cloture').text());
                                    } else if (jQuery(msg).find('date_fin_validite').text()+"x"!="x"){
                                        jQuery('#editDialog #modfin_loc').val(jQuery(msg).find('date_fin_validite').text());
                                    }
                                    if (jQuery(msg).find('prod_duree_loc_value').text() > 0){
                                        jQuery('#editDialog #duration_value').val(jQuery(msg).find('prod_duree_loc_value').text());
                                    }
                                    if (jQuery(msg).find('prod_duree_loc_unit').text() +"x" != "x"){
                                        jQuery('#editDialog #duration_unit').val(jQuery(msg).find('prod_duree_loc_unit').text());
                                        jQuery('#editDialog #duration_text').html(arrDuration[jQuery(msg).find('prod_duree_loc_unit').text()]);
                                    } else {
                                        jQuery('#editDialog #duration_unit').val("m");
                                        jQuery('#editDialog #duration_text').html(arrDuration["m"]);
                                    }

                                    jQuery('#editDialog #p_idprodmod').val(jQuery(msg).find('fk_product').text());
                                    jQuery('#editForm').validate({
                                        rules:{
                                            "moddebut_loc": { FRDate: true, required:true, moddateOrder: true},
                                            "modfin_loc": { FRDate: true, required:true, moddateOrder: true},
                                            "duration_value": { required:true},
                                            "duration_unit": { required:true},
                                            "pu_ht": { required:true},
                                            "qte": { required:true},
                                            "p_idprodmod": { reqProduct: true},
                                        },
                                        messages:{
                                            "duration_value": { required:"Ce champs est requis"},
                                            "duration_unit": { required:"Ce champs est requis"},
                                            "pu_ht": { required:"Ce champs est requis"},
                                            "qte": { required:"Ce champs est requis"},
                                            "p_idprodmod": { reqProduct:"Ce champs est requis"},

                                        }
                                    }).form();
                                    jQuery.ajax({
                                        datatype: "html",
                                        url: DOL_URL_ROOT+"/product/ajaxproducts.php",
                                        data : 'htmlname=p_idprodmod&showUniq=1&mode=&prodId='+jQuery(msg).find('fk_product').text(),
                                        error: function(XMLHttpRequest, textStatus, errorThrown){
                                            console.log(XMLHttpRequest);
                                            console.log(textStatus);
                                            console.log(errorThrown);
                                        },
                                        success: function(data) {
                                                var tmpHtml = jQuery('<div></div>');
                                                    tmpHtml.html(data);
                                                var tmp = tmpHtml.find('select').parent().html();
                                                if (tmp == null)
                                                {
                                                    jQuery("#editDialog #p_idprodmod").replaceWith(jQuery('<div id="ajdynfieldp_idprodmod"></div>'));
                                                } else {
                                                    jQuery("#editDialog #p_idprodmod").replaceWith(jQuery('<div id="ajdynfieldp_idprodmod">'+tmp+'</div>'));
                                                    jQuery("#editDialog #p_idprodmod").find('SELECT').selectmenu({ style:'dropdown'});
                                                }
                                        }
                                    });
                                }

                            })
                        },
                        buttons: {
                            "Annuler": function() { jQuery(this).dialog("close"); } ,
                            "Modifier": function() {
                                    if (jQuery('#editForm').validate({
                                        rules:{
                                            "moddebut_loc": { FRDate: true, required:true},
                                            "modfin_loc": { FRDate: true, required:true},
                                            "duration_value": { required:true},
                                            "duration_unit": { required:true},
                                            "pu_ht": { required:true},
                                            "qte": { required:true},
                                            "p_idprodmod": { reqProduct:true},
                                        },
                                        messages:{
                                            "duration_value": { required:"Ce champs est requis"},
                                            "duration_unit": { required:"Ce champs est requis"},
                                            "pu_ht": { required:"Ce champs est requis"},
                                            "qte": { required:"Ce champs est requis"},
                                            "p_idprodmod": { reqProduct:"Ce champs est requis"},

                                        }
                                    }).form()){
                                        var data = jQuery('#editForm').serialize();
                                        data += "&action=editLine";
                                        data += "&ligneId="+ligneId
                                        data += "&idContrat="+contratId
                                        jQuery.ajax({
                                            url: DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php",
                                            data:data,
                                            datatype:"xml",
                                            type:"POST",
                                            cache: false,
                                            success: function(msg){
                                                if (jQuery(msg).find('OK')&& jQuery(msg).find('OK').text() == 'OK'){
                                                    location.href="fiche.php?id="+contratId;
                                                }
                                            }
                                        });
                                    }
                            }
                        }
                    });
            jQuery('.editLoc').click(function(){
                ligneId = jQuery(this).parent().parent().attr('id');
                jQuery('#editDialog').dialog('open');
            });
            jQuery('#modChangeDurLoc').click(function(){
                var curVal = jQuery(this).find('input#duration_unit').val();
                var nexVal = arrNex[jQuery(this).find('input#duration_unit').val()];
                if (nexVal+'x' == 'x') nexVal = 'm';
                var textVal = arrDuration[nexVal];
                jQuery(this).find('input#duration_unit').val(nexVal);
                jQuery(this).find('span#duration_text').html(textVal);
            })

        });
        </script>
EOF;
        return($html);

    }
    public  function displayDialog()
    {
        global $form, $conf;
        $html = "";
        $html .= "<div id='editDialog'><form id='editForm' onSubmit='return(false);' action='#' method='POST'>";
        $html .= "<table cellpadding=15><tr>";
        $html .= "<th class='ui-widget-header ui-state-default'>Produit";
        $html .= "<td class='ui-widget-content' colspan=3>";
        if($conf->global->PRODUIT_MULTIPRICES == 1)
            $html .= $form->select_produits('','p_idprodmod'."","",$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
        else
            $html .= $form->select_produits('','p_idprodmod'."","",$conf->produit->limit_size,false,1,true,true,false);
        if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
        $html .= "<tr>";
        $html .= "    <th class='ui-widget-header ui-state-default'>Num&eacute;ro de s&eacute;rie";
        $html.= "     <td class='ui-widget-content' nowrap colspan=3 ><input id='serial' name='serial' value=''>";
        $html .= "<tr>";
        $html .= "<th class='ui-widget-header ui-state-default'>Date";
        $html .= "<td class='ui-widget-content' colspan=3 style='padding:0'>";
        $html.= "<table width=100% cellpadding=15><th class='ui-widget-header' >Du</th>";
        $html.= "     <td nowrap ><input class='datepicker' id='moddebut_loc' name='moddebut_loc' ></td>";
        $html.= "     <th class='ui-widget-header' nowrap >Au</th>";
        $html.= "     <td colspan= nowrap ><input class='datepicker' id='modfin_loc' name='modfin_loc' ></td></table>";

        $html .= "<tr>";
        $html .= "    <th class='ui-widget-header ui-state-default'>PU HT";
        $html.= "     <td class='ui-widget-content' nowrap ><input id='pu_ht' name='pu_ht' value=''>";
        $html .= "    <th class='ui-widget-header ui-state-default'>Pour";
        $html.= "     <td class='ui-widget-content' nowrap ><input size=2 id='duration_value' name='duration_value' value=''>";
        $objDuration=array('m'=>"&nbsp;Mois",'d'=>"&nbsp;Jour(s)",'y'=>"&nbsp;Ann&eacute;e(s)",'w'=>"&nbsp;Semaine(s)");
        $html .= '<span title="Cliquer pour changer la p&eacute;riode">&nbsp;';
        $html .= '<span id="modChangeDurLoc"><span id="duration_text">Mois</span><input type="hidden" id="duration_unit" name="duration_unit" value="m"></span></span>';

        $html .= "<tr>";
        $html .= "    <th class='ui-widget-header ui-state-default'>Qt&eacute;";
        $html.= "     <td class='ui-widget-content' nowrap colspan=3 ><input id='qte' name='qte' size=2 value=''>";
        $html .= "</table></form>";
        $html .= "</div>";
        return($html);
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
        global $conf;
        print '<tr><th align=left style="padding: 5px 15px;" colspan=4 class="ui-widget-header ui-state-default">Location de produits';
        print '<tr><th align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-header ui-state-default">Du';
        $dateDeb = date('d/m/Y',$this->mise_en_service);
        if (!$this->mise_en_service > 0) $dateDeb = "<table><tr><td class='error ui-state-error'><span class='ui-icon ui-icon-info'></span><td class='error ui-state-error'>Pas de date de d&eacute;but</span></table>";
        print '    <td width=25% align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-content">';
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print '<span id="dateDeb">';
        print $dateDeb;
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print "</span>";
        print '    <th align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-header ui-state-default">Au';
        $dateFin = date('d/m/Y',$this->date_fin_validite);
        if (!$this->date_fin_validite > 0) $dateFin = "<table><tr><td class='error ui-state-error' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='error ui-state-error' style='border-left: 0px;'>Pas de date de fin</span></table>";
        else if ($this->date_fin_validite - time() < 0 && $this->statut < 5) $dateFin = "<table><tr><td class='error ui-state-error' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='error ui-state-error' style='border-left: 0px;'>".$dateFin."</span></table>";
        else if (($this->date_fin_validite - ($conf->global->MAIN_DELAY_RUNNING_SERVICES * 3600 * 24) - time()) < 0 && $this->statut < 5) $dateFin = "<table><tr><td class='ui-state-highlight' style='border-right: 0px;'><span class='ui-icon ui-icon-info'></span><td class='ui-state-highlight' style='border-left: 0px;'>".$dateFin."</span></table>";
        print '    <td width=25% align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-content">';
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print '<span id="dateFin">';
        print $dateFin;
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print "</span>";

        print '<tr><th align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-header ui-state-default">Facturation';
        print '    <td colspan=1 align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-content">';
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print '<span id="prorataEditable">';
        print ($this->prorata==1?"Prorata temporis":"P&eacute;riode commenc&eacute;e");
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print "</span>";
        print '    <th align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-header ui-state-default">TVA';
        print '    <td colspan=1 align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-content">';
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print '<span id="tvaEditable">';
        print $this->tva_tx ."%";
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print "</span>";

        print '<tr><th align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-header ui-state-default">Facturation automatique';
        print '    <td colspan=3 align=left style="padding: 5px 15px;" colspan=1 class="ui-widget-content">';
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print '<span id="facturationFreqEditable">';
        print ($this->facturation_freq=='m'?"Mensuelle":($this->facturation_freq=='y'?'Annuelle':($this->facturation_freq=='t'?'Trimestrielle':'Manuelle')));
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            print "</span>";


        print "<script>var contratId = ".$this->id.";</script>";
        $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
        print ' <script src="'.$jspath.'/jquery.jeditable.js" type="text/javascript"></script>';
        print <<<EOF
            <script>
                jQuery(document).ready(function(){

                    jQuery.editable.addInputType('datepicker', {
                        element: function(settings, original) {

                          var input = jQuery('<input size=8 />');

                          // Catch the blur event on month change
                          settings.onblur = function(e) {
                          };

                          input.datepicker({
                              dateFormat: 'yy-mm-dd',
                              onSelect: function(dateText, inst) {
                                  jQuery(this).parents("form").submit();
                              },
                              onClose: function(dateText, inst) {
                                  jQuery(this).parents("form").submit();
                              },
                              dateFormat: 'dd/mm/yy',
                              changeMonth: true,
                              yearRange: yearRange,
                              changeYear: true,
                              showButtonPanel: true,
                              buttonImage: 'calendar.png',
                              buttonImageOnly: false,
                              showTime: false,
                              duration: '',
                              constrainInput: true,
                              gotoCurrent: true

                          });

                          input.datepicker('option', 'showAnim', 'slide');

                          jQuery(this).append(input);
                          return (input);
                      }
                  });
                  jQuery('#facturationFreqEditable').editable(DOL_URL_ROOT+'/Babel_GA/ajax/contratLoc_fiche_ajax.php', {
                     type      : 'select',

                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: contratId, action:"editFactPer"},
                     data       : "{'m':'Mensuelle','t':'Trimestrielle','y':'Annuelle','':'Manuelle'}",
                     callback: function(a,b){
                        if (a != 'KO') location.href='fiche.php?id='+contratId;
                     }
                 });


                  jQuery('#prorataEditable').editable(DOL_URL_ROOT+'/Babel_GA/ajax/contratLoc_fiche_ajax.php', {
                     type      : 'select',

                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: contratId, action:"editProrata"},
                     data       : "{'1':'Prorata Temporis','0':'P&eacute;riode commenc&eacute;e'}",
                     callback: function(a,b){
                        if (a != 'KO') location.href='fiche.php?id='+contratId;
                     }
                 });

                  jQuery('#tvaEditable').editable(DOL_URL_ROOT+'/Babel_GA/ajax/contratLoc_fiche_ajax.php', {
                     type      : 'select',

                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: contratId, action:"edittva"},
                     data       : "{'19.6':'19,6%','5.5':'5,5%','0':'0%'}",
                     callback: function(a,b){
                        if (a != 'KO') location.href='fiche.php?id='+contratId;
                     }
                 });


                 jQuery('#dateDeb').editable(DOL_URL_ROOT+'/Babel_GA/ajax/contratLoc_fiche_ajax.php', {
                     type      : 'datepicker',
                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: contratId, action:"editDateDeb"},
                     data : function(value, settings) {
                          var retval = value; //Global var
                          return retval;
                     },
                     callback: function(a,b){
                        if (a != 'KO') location.href='fiche.php?id='+contratId;
                     }
                 });
                 jQuery('#dateFin').editable(DOL_URL_ROOT+'/Babel_GA/ajax/contratLoc_fiche_ajax.php?action=editDateFin', {
                     type      : 'datepicker',
                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     width: '95%',
                     height:"18em",
                     submitdata : {id: contratId},
                     data : function(value, settings) {
                          var retval = jQuery(value).text(); //Global var
                          return retval;
                     },
                     callback: function(a,b){
                        if (a != 'KO') location.href='fiche.php?id='+contratId;
                     }
                 });
            });
            </script>
EOF;
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
                $html .= '<table width=100% cellpadding=15>
                              <tr><th class="ui-state-default ui-widget-header" colspan=1>Date du renouvellement:</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dateRenew" class="requiredNoBR FRDateNoBR" name="dateRenew"></td> ';
                $html .= '    <tr><th class="ui-state-default ui-widget-header" colspan=1>Renouvellement pour (en mois)</th>
                                  <td class="ui-widget-content" align=center><input style="text-align:center" id="dureeRenew" class="requiredNoBR  nombreentierNoBR sup1NoBR" name="dureeRenew"></td> ';
                $html .= '</table>';
                $html .= "</form></div>";
            }

            //$html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=close">'.$langs->trans("Cloturer").'</a>';
            if ($this->statut == 0)
            {
                $html .=  '<button class="butAction" href="#" id="validateGA">'.$langs->trans("Validate").'</button>';
            }

            if ($this->statut > 0)
            {
                $html .=  '<button class="butActionDelete" href="#" id="clotureGA">'.$langs->trans("Cloturer").'</button>';
            }
            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $this->statut == 0) || $user->rights->contrat->supprimer || $user->rights->GA->contrat->Effacer)
            {
                $html .=  '<button class="butActionDelete" onclick=\'href="fiche.php?id='.$this->id.'&amp;action=delete"\'>'.$langs->trans("Delete").'</button>';
            }

            $html .=  "</div>";
            $html .=  '<br>';
        }
        return($html);
    }
    public function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0, $info_bits=0,$commandeDet=false,$durLoc="1m")
    {
        global $langs, $conf, $user;

        dol_syslog("Contrat::addline $desc, $pu_ht, $qty, $txtva, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits");
        if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
        {
            $this->db->begin();

            // Clean parameters
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            if (! $qty) $qty=1;
            if (! $ventil) $ventil=0;
            if (! $info_bits) $info_bits=0;
            $pu_ht=price2num($pu_ht);
            $pu_ttc=price2num($pu_ttc);
            $txtva=price2num($txtva);

            if ($price_base_type=='HT'||$price_base_type."x"=='x')
            {
                $pu=$pu_ht;
            } else {
                $pu=$pu_ttc;
            }
            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            // \TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;
            $price = price2num(round($pu, 2));
            if (strlen($remise_percent) > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }
            $hasDateEnd = false;
            $dateEndHasInterval = false;
            if (preg_match('/\+([0-9]*)/',$date_end,$arr)){
                $hasDateEnd=true;
                $dateEndHasInterval=$arr[1];
            }else if ($date_end > 0){
                $hasDateEnd=true;
            }

            //rang
            $requete = "SELECT max(line_order) as mx FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat = ".$this->id;
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            $cnt = $res->mx + 1;
            $lineOrder = ($res->mx."x"=="x"?1:$cnt);
            // Insertion dans la base
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
            $sql.= " (line_order, fk_contrat, label, description, fk_product, qty, tva_tx,";
            $sql.= " remise_percent, subprice,fk_user_author, ";
            $sql.= " total_ht, total_tva, total_ttc,prod_duree_loc,";
            $sql.= " info_bits,";
            $sql.= " price_ht, remise";                                // \TODO A virer
            if ($commandeDet) { $sql .= ",fk_commande_ligne ";}
            if ($date_start > 0) { $sql.= ",date_ouverture_prevue"; }
            if ($hasDateEnd)  { $sql.= ",date_fin_validite"; }
            $sql.= ") VALUES ($lineOrder, $this->id, '" . addslashes($label) . "','" . addslashes($desc) . "',";
            $sql.= ($fk_product>0 ? $fk_product : "null").",";
            $sql.= " '".$qty."',";
            $sql.= " '".$txtva."',";
            $sql.= " ".price2num($remise_percent).",".price2num($pu).",".$user->id .",";
            $sql.= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_ttc).",'".$durLoc."',";
            $sql.= " '".$info_bits."',";
            $sql.= " ".price2num($price).",".price2num( $remise);    // \TODO A virer
            if ($commandeDet){ $sql .= ",".$commandeDet; }
            if ($date_start > 0) { $sql.= ",".$this->db->idate($date_start); }
            if ($hasDateEnd) {
                if($dateEndHasInterval)
                {
                    if (preg_match('/m/i',$date_end)){
                        $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." MONTH)";
                    } else if (preg_match('/d/i',$date_end)){
                        $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." DAY)";
                    } else if (preg_match('/w/i',$date_end)){
                        $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." WEEK)";
                    } else if (preg_match('/y/i',$date_end)){
                        $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." YEAR)";
                    }
                } else {
                     $sql.= ",".$this->db->idate($date_end);
                }
                 }
            $sql.= ")";
            dol_syslog("Contrat::addline sql=".$sql);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $lastid = $this->db->last_insert_id("".MAIN_DB_PREFIX."contratdet");
                $result=$this->update_total_contrat();
                if ($result > 0)
                {

                    $label="cont-".trim($this->id).'-opt'.$lastid;
                    $requet = "UPDATE ".MAIN_DB_PREFIX."contratdet SET label ='".$label."' WHERE rowid = ".$lastid;
                    $this->db->query($requet);


                    $this->db->commit();
                    //Modif post GLE 1.0
                    $this->newContractLigneId =  $lastid;
                    //fin Modif
                    return $lastid;
                } else {
                    dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
                    $this->db->rollback();
                    return -1;
                }
            } else {
                $this->db->rollback();
                $this->error=$this->db->error()." sql=".$sql;
                dol_syslog("Contrat::addline ".$this->error,LOG_ERR);
                return -2;
            }
        } else {
            dol_syslog("Contrat::addline ErrorTryToAddLineOnValidatedContract", LOG_ERR);
            return -3;
        }
    }

    function active_line($user, $line_id, $date, $duree='')
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
    function validate($data=array())
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
                        if ($conf->global->ContratLocProdGAVALIDATE_ENTER_STOCK && $conf->global->BABELGA_STOCKLOC."x" !='x')
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
    function renewList()
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

    public function updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
         $date_start='', $date_end='', $tvatx,
         $date_debut_reel='', $date_fin_reel='',$fk_prod=false,$fk_commandedet=false,$duration="1m")
    {
        // Nettoyage parametres
        $qty=trim($qty);
        $desc=trim($desc);
        $price = price2num($pu);
        $tvatx = price2num($tvatx);
        $subprice = $price;
        $remise = 0;
        if (strlen($remise_percent) > 0)
        {
            $remise = round(($pu * $remise_percent / 100), 2);
            $price = $pu - $remise;
        } else {
            $remise_percent=0;
        }
            if (! $info_bits) $info_bits=0;
            $pu_ht=price2num($pu);
            $pu_ttc=price2num($pu_ttc);
            $txtva=price2num($tvatx);

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, "HT", $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];

            // \TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;


        dol_syslog("Contrat::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_debut_reel, $date_fin_reel, $tvatx");

        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".addslashes($desc)."'";
        $sql .= ",price_ht='" .     price2num($price)."'";
        $sql .= ",subprice='" .     price2num($price)."'";
        $sql .= ",total_ht='" .     price2num($total_ht)."'";
        $sql .= ",total_tva='" .     price2num($total_tva)."'";
        $sql .= ",total_ttc='" .     price2num($total_ttc)."'";
        $sql .= ",remise='" .       price2num($remise)."'";
        $sql .= ",remise_percent='".price2num($remise_percent)."'";
        $sql .= ", prod_duree_loc= '".$duration."'";
        $sql .= ",qty='$qty'";
        if($fk_commandedet) { $sql .= ",fk_commande_ligne=".$fk_commandedet." "; }
        $sql .= ",tva_tx='".        price2num($tvatx)."'";
        if ($fk_prod && $fk_prod != 0) { $sql .= ", fk_product=".$fk_prod; }
        else if ($fk_prod == 0){ $sql .= ", fk_product = NULL ";}
        if ($date_start > 0) { $sql.= ",date_ouverture_prevue=".$this->db->idate($date_start); }
        else { $sql.=",date_ouverture_prevue=null"; }
        if ($date_end > 0) { $sql.= ",date_fin_validite=".$this->db->idate($date_end); }
        else { $sql.=",date_fin_validite=null"; }
        if ($date_debut_reel > 0) { $sql.= ",date_ouverture=".$this->db->idate($date_debut_reel); }
        else { $sql.=",date_ouverture=null"; }
        if ($date_fin_reel > 0) { $sql.= ",date_cloture=".$this->db->idate($date_fin_reel); }
        else { $sql.=",date_cloture=null"; }
        $sql .= " WHERE rowid = ".$rowid;
//print $sql;
        dol_syslog("Contrat::UpdateLine sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $result=$this->update_total_contrat();
            if ($result >= 0)
            {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                dol_syslog("Contrat::UpdateLigne Erreur -2");
                return -2;
            }
        } else {
            $this->db->rollback();
            $this->error=$this->db->error();
            dol_syslog("Contrat::UpdateLigne Erreur -1");
            return -1;
        }
    }

    function create_from_propale($user,$propale_id)
    {
        //Contrat de financement
        dol_syslog("ContratLocProdGA::create_from_propale propale_id=$propale_id");
        global $langs,$conf;

        require_once(DOL_DOCUMENT_ROOT."/Babel_GA/PropalGA.class.php");
        $propal = new PropalGA($this->db);
        $res = $propal->fetch($propale_id);

        //Creer le contrat
        $this->commercial_signature_id = ($conf->global->CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE>0?$conf->global->CONTRAT_DEFAULT_COMMERCIAL_SIGNATAIRE:($propal->commercial_signataire_refid>0?$propal->commercial_signataire_refid:$propal->user_author_id));
        $this->commercial_suivi_id = $propal->user_author_id; // Le créateur de la propal

        $this->socid = $propal->socid;
        $soc = new Societe($this->db);
        $soc->id = $this->socid;
        $soc->fetch($soc->id);
        $this->date_contrat = time();
        $this->ref = $this->getNextNumRef($soc);
        $this->linkedTo = "p".$propale_id;
        $contrat->typeContrat = 5;
        $this->fk_projet = $propal->projetidp;

        $result = $this->create($user,$langs,$conf);
        $contratId = $result;

        if ($result > 0)
        {
            //ajoute le signataire
            //
            if ($this->client_signataire_refid > 0){
                $result=$this->add_contact($this->client_signataire_refid,'BILLING','external');
            }
            //liaison GA contrat -> propale

            $requete = 'INSERT INTO Babel_GA_li_cont_pr (propal_refid, contrat_refid) VALUES ('.$propale_id.','.$this->id.')';
            $this->db->query($requete);


            //Creer les lignes de contrat
            foreach ($propal->lignes as $key=>$val)
            {
                //On recupere la ligne
                if ($val->fk_product > 0){
                    $tmpId = $this->addline($val->desc, $val->subprice,
                                   $val->qty, $val->tva_tx,
                                   $val->fk_product, 0,
                                   time(), "+".$val->dureeLoc, 'HT',
                                   $val->subprice * (1+$val->tva_tx/100), 0,false,$val->dureeLoc);
                }
//    public function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0, $info_bits=0,$commandeDet=false,$durLoc="1m")

            }
            $requete = "SELECT subprice,
                               unix_timestamp(ifnull(date_ouverture,date_ouverture_prevue)) as debutLoc,
                               unix_timestamp(ifnull(date_cloture,date_fin_validite)) as finLoc,
                               prod_duree_loc,
                               qty,
                               rowid
                          FROM ".MAIN_DB_PREFIX."contratdet
                         WHERE fk_contrat = ".$contratId;
            $sql = $this->db->query($requete);
            while($res = $this->db->fetch_object($sql))
            {
                $total_ht = $this->getTotalLigne($res->subprice,$res->debutLoc,$res->finLoc,($res->prod_duree_loc."x"=="x"?'1m':$res->prod_duree_loc),$res->qty);
                $this->update_totalLigne($res->rowid,$total_ht);

            }



            return $contratId;
        } else {

            return (-1);
        }
    }
    public function create($user,$langs='',$conf='')
    {
        // Check parameters
        $paramsok=1;
        if ($this->commercial_signature_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeSignature"));
            $paramsok=0;

        }
        if ($this->commercial_suivi_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=($this->error?"<br>":'');
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeFollowUp"));
            $paramsok=0;
        }

        if (! $paramsok) return -5;

        $this->db->begin();
        $this->verifyNumRef();
        // Insert contract
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (type,datec, fk_soc, fk_user_author, date_contrat";
        $sql.= ", fk_commercial_signature, fk_commercial_suivi,ref, linkedTo";
        if ($this->fk_projet ."x" != "x")
        {
            $sql .= ", fk_projet";
        }
        if ($this->is_financement ."x" != "x")
        {
            $sql .= ", is_financement, cessionnaire_refid, fournisseur_refid ";
        }
        $sql.= " )";
        $sql.= " VALUES (".$this->typeContrat.",now(),".$this->socid.",".$user->id;
        $sql.= ",".$this->db->idate($this->date_contrat);
        $sql.= ",".($this->commercial_signature_id>0?$this->commercial_signature_id:"NULL");
        $sql.= ",".($this->commercial_suivi_id>0?$this->commercial_suivi_id:"NULL");
        $sql .= ", " . (strlen($this->ref)<=0 ? "null" : "'".$this->ref."'");
        $sql.= ", '".$this->linkedTo."'";
        if ($this->fk_projet ."x" != "x")
        {
            $sql .= " ,  ".$this->fk_projet;
        }
        if ($this->is_financement ."x" != "x")
        {
            $sql .= " ,  ".$this->is_financement;
            if ($this->cessionnaire_refid > 0)
            {
                $sql .= " ,  ".$this->cessionnaire_refid;
            } else {
                $sql .= " ,  null";
            }
            if ($this->fournisseur_refid > 0)
            {
                $sql .= " ,  ".$this->fournisseur_refid;
            } else {
                $sql .= " ,  null";
            }

        }
        $sql.= " )";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $error=0;

            $this->id = $this->db->last_insert_id("".MAIN_DB_PREFIX."contrat");

            // Insere contacts commerciaux ('SALESREPSIGN','contrat')
            $result=$this->add_contact($this->commercial_signature_id,'SALESREPSIGN','internal');
            if ($result < 0) $error++;

            // Insere contacts commerciaux ('SALESREPFOLL','contrat')
            $result=$this->add_contact($this->commercial_suivi_id,'SALESREPFOLL','internal');
            if ($result < 0) $error++;


            if ($conf->global->MAIN_MODULE_BABELGA == 1 && $this->client_signataire_refid >0)
            {
                $resultNoCount=$this->add_contact($this->client_signataire_refid,'BILLING','external');
                //no need if from propal
            }
            $requete = false;
            //Si type contrat = 2 3 4 7
            if ($this->typeContrat == 2){
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms,qte,hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),-1,0,0,0,0)";
            } else if ($this->typeContrat == 3)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms,hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,1,0)";
            }else if ($this->typeContrat == 4)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms, hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,0,1)";
            }else if ($this->typeContrat == 7)
            {
                $requete = "INSERT INTO Babel_GMAO_contrat_prop
                                        (contrat_refid,tms, hotline,telemaintenance,maintenance,isSAV)
                                 VALUES (".$this->id.",now(),0,0,0,0)";
            }
            $sql = $this->db->query($requete);
            if (! $error || $error == 0)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('CONTRACT_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

                if (! $error)
                {
                    $this->db->commit();
                    return $this->id;
                } else {
                    $this->error=$interface->error;
                    dol_syslog("Contrat::create - 30 - ".$this->error);

                    $this->db->rollback();
                    return -3;
                }
            } else {
                $this->error="Failed to add contract extra data";
                dol_syslog("Contrat::create - 20 - ".$this->error);
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error=$langs->trans("UnknownError: ".$this->db->error()." - sql=".$sql);
            dol_syslog("Contrat::create - 10 - ".$this->error);
            $this->db->rollback();
            return -1;
        }
    }
}

?>