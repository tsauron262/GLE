<?php

require_once DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php";

class Synopsis_Contrat extends Contrat {
    
    public $db;
    public $product;
    public $societe;
    public $user_service;
    public $user_cloture;
    public $client_signataire;

    //Special maintenance

    public $sumDInterByStatut=array();
    public $sumDInterByUser=array();
    public $sumDInterCal=array();
    public $totalDInter = 0;
    public $totalFInter = 0;
    
    
    public function _construct($db){
        $this->db = $db ;
        $this->product = new Product($this->db);
        $this->societe = new Societe($this->db);
        $this->user_service = new User($this->db);
        $this->user_cloture = new User($this->db);
        $this->client_signataire = new User($this->db);        
    }

    public function getTypeContrat_noLoad($id) {
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->extraparams);
    }

    public function fetch($id, $ref = '') {
        $ret = parent::fetch($id, $ref);
        $requete = "SELECT durValid,
                           unix_timestamp(DateDeb) as DateDebU,
                           fk_prod,
                           reconductionAuto,
                           qte,
                           hotline,
                           telemaintenance,
                           maintenance,
                           SLA,unix_timestamp(dateAnniv) as dateAnnivU,
                           isSAV
                      FROM ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO
                     WHERE contrat_refid =".$id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql)>0)
        {
            $res = $this->db->fetch_object($sql);

            $this->durValid = $res->durValid;
            $this->DateDeb = $res->DateDebU;
            $this->dateAnniv = $res->dateAnnivU;
            $this->dateAnnivFR = date('d/m/Y',$res->dateAnnivU);
            $this->DateDebFR = date('d/m/Y',$res->DateDebU);
            $this->fk_prod = $res->fk_prod;
            if ($this->fk_prod > 0)
            {
                require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
                $prodTmp = new Product($this->db);
                $prodTmp->fetch($this->fk_prod);
                $this->prod = $prodTmp;
            }
            $this->reconductionAuto = $res->reconductionAuto;
            $this->qte = $res->qte;
            $this->hotline = $res->hotline;
            $this->telemaintenance = $res->telemaintenance;
            $this->maintenance = $res->maintenance;
            $this->SLA = $res->SLA;
            $this->isSAV = $res->isSAV;

            $requete = "SELECT unix_timestamp(date_add(date_add(".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.DateDeb, INTERVAL ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.durValid month), INTERVAL ifnull(".MAIN_DB_PREFIX."product_extrafields.0dureeSav,0) MONTH)) as dfinprev,
                               unix_timestamp(date_add(date_add(".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.DateDeb, INTERVAL ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.durValid month), INTERVAL ifnull(".MAIN_DB_PREFIX."product_extrafields.0dureeSav,0) MONTH)) as dfin,
                               unix_timestamp(".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.DateDeb) as ddeb,
                               unix_timestamp(".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.DateDeb) as ddebprev,
                               ".MAIN_DB_PREFIX."contratdet.qty,
                               ".MAIN_DB_PREFIX."contratdet.rowid,
                               ".MAIN_DB_PREFIX."contratdet.subprice as pu,
                               ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.durValid as durVal,
                               ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.fk_contrat_prod,
                               ".MAIN_DB_PREFIX."product_extrafields.0dureeSav,
                               ".MAIN_DB_PREFIX."Synopsis_product_serial_cont.serial_number
                          FROM ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO, ".MAIN_DB_PREFIX."contratdet
                     LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields ON fk_object = ".MAIN_DB_PREFIX."contratdet.fk_product
                     LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_product_serial_cont ON ".MAIN_DB_PREFIX."Synopsis_product_serial_cont.element_id = ".MAIN_DB_PREFIX."contratdet.rowid AND ".MAIN_DB_PREFIX."Synopsis_product_serial_cont.element_type LIKE 'contrat%'
                         WHERE ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid
                           AND fk_contrat =".$id;
            $sql = $this->db->query($requete);
            while ($res=$this->db->fetch_object($sql))
            {
                $this->lineTkt[$res->rowid]=array(
                    'serial_number'=>$res->serial_number ,
                    'fk_contrat_prod' => ($res->fk_contrat_prod>0?$res->fk_contrat_prod:false),
                    'durVal' => $res->durVal,
                    'durSav' => $res->durSav,
                    'qty'=>$res->qty,
                    'pu'=>$res->pu,
                    'dfinprev'=>$res->dfinprev,
                    'dfin'=>$res->dfin,
                    'ddeb'=>$res->ddeb,
                    'ddebprev'=>$res->ddebprev);
            }
        }
        $this->type = $this->extraparams;
        return($ret);
    }

    public function displayExtraInfoCartouche() {
        return "";
    }

    public function displayDialog($type = 'add', $mysoc, $objp) {
        global $conf, $form, $db;
        $html .= '<div id="' . $type . 'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='" . $type . "Form' method='POST' onsubmit='return(false);'>";

//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .= '<table style="width: 900px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .= '<tr style="border-bottom: 1px Solid #0073EA !important">';
        $html .= '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Recherche de produits & financement</th></tr>';
        $html .= '<tr style="border-top: 1px Solid #0073EA !important">';
        $html .= '<td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Produits</td>
                   <td style=" padding-top: 5px; padding-bottom: 3px;">';
        // multiprix
        $filter = "";
        switch ($this->type) {
            case 1:
                //SAV
                $filter = "1";
                break;
        }
        if ($conf->global->PRODUIT_MULTIPRICES == 1)
            $html .= $form->select_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, $this->societe->price_level, 1, true, false, false);
        else
            $html .= $form->select_produits('', 'p_idprod_' . $type, $filter, $conf->produit->limit_size, false, 1, true, true, false);
        if (!$conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
            $html .= '<br>';



        $html .= '</td><td  style=" padding-top: 5px; padding-bottom: 3px;border-right: 1px Solid #0073EA;">&nbsp;</td>';
        $html .= '<tr>';
        $html .= ' <td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">Financement ? ';
        $html .= ' </td>
                    <td style="width: 30px;">
                        <input type="checkbox" id="addFinancement' . $type . '"  name="addFinancement' . $type . '" /></td>
                    <td style="border-right: 1px Solid #0073EA; padding-top: 5px; padding-bottom: 3px;">&nbsp;</td>';
        $html .= '</tr>';
        $html .= "</table>";
        $html .= '<table style="width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $html .= '<tr>';
        $html .= '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Description ligne / produit</th></tr>';
        $html .= '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .= '<td style="border-right: 1px Solid #0073EA;">';
        $html .= 'Description libre<br/>';
        $html .= '<div class="nocellnopadd" id="ajdynfieldp_idprod_' . $type . '"></div>';
        $html .= "<textarea style='width: 600px; height: 3em' name='" . $type . "Desc' id='" . $type . "Desc'></textarea>";
        $html .= '</td>';
        $html .= '</tr>';
        $html .= "</table>";

        $html .= '<table style=" width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $html .= '<tr>';
        $html .= '<th style="border-bottom: 1px Solid #0073EA !important; " colspan="8"  class="ui-widget-header">Prix & Quantit&eacute;</th></tr><tr style="padding: 10px; ">';
        $html .= '<td align=right>Prix (&euro;)</td><td align=left>';
        $html .= "<input id='" . $type . "Price' name='" . $type . "Price' style='width: 100px; text-align: center;'/>";
        $html .= '</td>';
        $html .= '<td align=right>TVA<td align=left width=180>';
        $html .= $form->load_tva($type . "Linetva_tx", "19.6", $mysoc, $this->societe, "", 0, false);

        $html .= '</td>';
        $html .= '<td align=right>Qt&eacute;</td><td align=left>';
        $html .= "<input id='" . $type . "Qty' value=1 name='" . $type . "Qty' style='width: 20px;  text-align: center;'/>";
        $html .= '</td>';
        $html .= '<td align=right>Remise (%)</td><td align=left>';
        $html .= "<input id='" . $type . "Remise' value=0 name='" . $type . "Remise' style='width: 20px; text-align: center;'/>";
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '<table style="width: 900px;  border-collapse: collapse; margin-top: 5px;"  cellpadding=10>';
        $html .= '<tr style="border-bottom: 1px Solid #0073EA; ">';
        $html .= '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Chronologie</th>';
        $html .= '</tr>';
        $html .= "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; '>";
        $html .= '<td>Date de d&eacute;but pr&eacute;vue</td>';
        $html .= '<td>
                        <input value="' . date('d') . '/' . date('m') . '/' . date('Y') . '" style="text-align: center;" type="text" name="dateDeb' . $type . '" id="dateDeb' . $type . '"/>' . img_picto('calendrier', 'calendar.png', 'style="float: left;margin-right: 3px; margin-top: 1px;"') . '</td>';
        $html .= '<td>Date de fin pr&eacute;vue</td>';
//        calendar.png
        $html .= '<td style="border-right: 1px Solid #0073EA;">
                        <input style="text-align: center;" type="text" name="dateFin' . $type . '" id="dateFin' . $type . '"/>' . img_picto('calendrier', 'calendar.png', 'style="float: left; margin-right: 3px; margin-top: 1px;"') . '</td>';
        $html .= '</tr>';
        $html .= "</table>";

        $html .= '<div id="financementLigne' . $type . '" style="display: none; margin-top: 5px;">';
        $html .= '<table style="width: 900px;  border-collapse: collapse; "  cellpadding=10>';
        $html .= '<tr style="border-bottom: 1px Solid #0073EA; ">';
        $html .= '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Financement</th>';
        $html .= '</tr>';
        $html .= "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; border-top: 1px Solid #0073EA;'>";
        $html .= '<td align=right>Nombre de p&eacute;riode</td>';
        //TODO ds conf
        $html .= '<td align=left><input style="text-align: center;width: 35px;" type="text" name="nbPeriode' . $type . '" id="nbPeriode' . $type . '"/></td>';
        $html .= '<td align=right>Type de p&eacute;riode</td>';
        $html .= '<td align=left><select id="typePeriod' . $type . '">';
        $requete = "SELECT * FROM Babel_financement_period ORDER BY id";
        $sqlPeriod = $db->query($requete);
        while ($res = $db->fetch_object($sqlPeriod)) {
            $html .= "<option value='" . $res->id . "'>" . $res->Description . "</option>";
        }
        $html .= '</select>';
        $html .= '</td>';
        //TODO dans conf taux par défaut configurable selon droit ++ droit de choisir le taux
        $html .= '<td align=right>Taux achat</td>';
        $html .= '<td align=left><input style="text-align: center; width: 35px;" name="' . $type . 'TauxAchat" id="' . $type . 'TauxAchat"/></td>';
        //TODO dans conf taux par défaut configurable selon droit + droit de choisir le taux
        $html .= '<td align=right>Taux vente</td>';
        $html .= '<td align=left style="border-right: 1px Solid #0073EA;">
                        <input style="text-align: center;width: 35px;" name="' . $type . 'TauxVente" id="' . $type . 'TauxVente"/></td>';
        $html .= '</tr>';
        $html .= "</table>";
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';
        return ($html);
    }

    public function getHtmlLinked($tabLiked) {
        global $langs, $conf;
        $db = $this->db;
        foreach ($tabLiked as $elem) {
            if (1) {
                print '<tr><th class="ui-widget-header ui-state-default"><table class="nobordernopadding" style="width:100%;">';
                print '<tr><th style="border:0" class="ui-widget-header ui-state-default">Contrat associ&eacute; &agrave; ';
                $val1 = $elem['s'];
                switch ($elem['ts']) {
                    case 'commande':
                        print 'la commande<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                        $comm = new Commande($db);
                        $comm->fetch($val1);
                        if ($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE == 1) {
                            $comm2 = new Synopsis_Commande($db);
                            $comm2->fetch($val1);
                            print "</table><td colspan=1 class='ui-widget-content'>" . $comm->getNomUrl(1);
                            print "<th class='ui-widget-header ui-state-default'>Prepa. commande";
                            print "<td colspan=1 class='ui-widget-content'>" . $comm2->getNomUrl(1);
                        } else {
                            print "</table><td colspan=3 class='ui-widget-content'>" . $comm->getNomUrl(1);
                        }

                        break;
                    case 'propal':
                        print 'la proposition<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                        $prop = new Propal($db);
                        $prop->fetch($val1);
                        print "</table><td colspan=3 class='ui-widget-content'>" . $prop->getNomUrl(1);
                        break;
                }
                $val1 = $elem['d'];
                switch ($elem['td']) {
                    case 'facture':
                        print 'la facture<td>';
                        print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=chSrc&amp;id=' . $id . '">' . img_edit($langs->trans("Change la source")) . '</a>';
                        require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                        $fact = new Facture($db);
                        $fact->fetch($val1);
                        print "</table><td colspan=3 class='ui-widget-content'>" . $fact->getNomUrl(1);
                        break;
                }
            }
        }
    }

//    public function contratCheck_link() {
//        $this->linkedArray['co'] = array();
//        $this->linkedArray['pr'] = array();
//        $this->linkedArray['fa'] = array();
//        $db = $this->db;
//        //check si commande ou propale ou facture
//        if (preg_match('/^([c|p|f]{1})([0-9]*)/', $this->linkedTo, $arr)) {
//            //si commande check si propal lie a la commande / facture etc ...
//            switch ($arr[1]) {
//                case "p":
//                    //test si commande facture
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_propale = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['co'], $res->fk_commande);
//                        }
//                    }
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_propale = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['fa'], $res->fk_facture);
//                        }
//                    }
//                    break;
//                case "c":
//                    //test si commande propal ...
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_pr WHERE fk_commande = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['pr'], $res->fk_propale);
//                        }
//                    }
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_fa WHERE fk_commande = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['fa'], $res->fk_facture);
//                        }
//                    }
//                    break;
//                case "f":
//                    //test si propal facture ...
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "co_fa WHERE fk_facture = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['co'], $res->fk_commande);
//                        }
//                    }
//                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "fa_pr WHERE fk_facture = " . $arr[2];
//                    if ($resql = $db->query($requete)) {
//                        while ($res = $db->fetch_object($resql)) {
//                            array_push($this->linkedArray['pr'], $res->fk_propal);
//                        }
//                    }
//                    break;
//            }
//        }
//        //ajoute donnees dans les tables
////        var_dump($this->linkedArray);
//    }

    public function getTypeContrat() {
        $array[0]['type'] = "Simple";
        $array[0]['Nom'] = "Simple";
        $array[1]['type'] = "Service";
        $array[1]['Nom'] = "Service";
        $array[2]['type'] = "Ticket";
        $array[2]['Nom'] = "Au ticket";
        $array[3]['type'] = "Maintenance";
        $array[3]['Nom'] = "Maintenance";
        $array[4]['type'] = "SAV";
        $array[4]['Nom'] = "SAV";
        $array[5]['type'] = "Location";
        $array[5]['Nom'] = "Location de produits";
        $array[6]['type'] = "LocationFinanciere";
        $array[6]['Nom'] = "Location Financi&egrave;re";
        $array[7]['type'] = "Mixte";
        $array[7]['Nom'] = "Mixte";
        return ($array[$this->typeContrat]);
    }

    public function getExtraHeadTab($head) {
        return $head;
    }

    public function list_all_valid_contacts() {
        return array();
    }
    
    
    public function intervRestant($val){
        $tickets = $val->GMAO_Mixte['tickets'];

        $qteTempsPerDuree  = $val->GMAO_Mixte['qteTempsPerDuree'];
        $qteTktPerDuree   = $val->GMAO_Mixte['qteTktPerDuree'];


        $requete = "SELECT fd.rowid, fd.duree
                      FROM ".MAIN_DB_PREFIX."Synopsis_fichinter as f,
                           ".MAIN_DB_PREFIX."Synopsis_fichinterdet as fd,
                           ".MAIN_DB_PREFIX."Synopsis_fichinter_c_typeInterv as b
                     WHERE b.id = fd.fk_typeinterv
                       AND fd.fk_fichinter = f.rowid
                       AND b.decountTkt = 1
                       AND fd.fk_contratdet = " . $val->id;
         $consomme = 0;
         $sqlCnt = $this->db->query($requete);
         while($resCnt = $this->db->fetch_object($sqlCnt)){
            if ($qteTempsPerDuree==0){
                $consomme += $qteTktPerDuree;
            } else {
                for($i=0;$i<$resCnt->duree;$i+=$qteTempsPerDuree){
                    $consomme += $qteTktPerDuree;
                }
            }
         }
         $restant = $tickets - $consomme;
         return(array('restant' => $restant,'consomme' => $consomme));
    }

    function display1Line($object, $objL) {
        global $langs;
        $langs->load("contracts");
        $langs->load("bills");
        $idLigne = $objL->id;
        $db = $this->db;
        $productstatic = new Product($db);
        $return = '';
//        $return .= '<tr height="16" ' . $bc[false] . '>';
//        $return .= '<td class="liste_titre" width="90" style="border-left: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';">';
//            $return .= $langs->trans("ServiceNb",$cursorline).'</td>';
//        $return .= '<td class="tab" style="border-right: 1px solid #' . $colorb . '; border-top: 1px solid #' . $colorb . '; border-bottom: 1px solid #' . $colorb . ';" rowspan="2">';
        // Area with common detail of line
        $return .= '<table class="notopnoleft" width="100%">';

        $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
        $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
        $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
        $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
        $sql.= " cd.commentaire as comment,";
        $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
        $sql.= " FROM " . MAIN_DB_PREFIX . "contratdet as cd";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON cd.fk_product = p.rowid";
        $sql.= " WHERE cd.rowid = " . $idLigne;

        $result = $db->query($sql);
        if ($result) {
            $total = 0;

            $return .= '<tr class="liste_titre">';
            $return .= '<td>' . $langs->trans("Service") . '</td>';
            $return .= '<td width="50" align="center">' . $langs->trans("VAT") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("PriceUHT") . '</td>';
            $return .= '<td width="30" align="center">' . $langs->trans("Qty") . '</td>';
            $return .= '<td width="50" align="right">' . $langs->trans("ReductionShort") . '</td>';
            $return .= '<td width="30">&nbsp;</td>';
            $return .= "</tr>\n";

            $var = true;

            $objp = $db->fetch_object($result);

            $var = !$var;

            if ($action != 'editline' || GETPOST('rowid') != $objp->rowid) {
                $return .= '<tr ' . $bc[$var] . ' valign="top">';
                // Libelle
                if ($objp->fk_product > 0) {
                    $return .= '<td>';
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    if ($objp->description)
                        $return .= '<br>' . dol_nl2br($objp->description);
                    $return .= '</td>';
                }
                else {
                    $return .= "<td>" . nl2br($objp->description) . "</td>\n";
                }
                // TVA
                $return .= '<td align="center">' . vatrate($objp->tva_tx, '%', $objp->info_bits) . '</td>';
                // Prix
                $return .= '<td align="right">' . price($objp->subprice) . "</td>\n";
                // Quantite
                $return .= '<td align="center">' . $objp->qty . '</td>';
                // Remise
                if ($objp->remise_percent > 0) {
                    $return .= '<td align="right">' . $objp->remise_percent . "%</td>\n";
                } else {
                    $return .= '<td>&nbsp;</td>';
                }
                // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                $return .= '<td align="right" nowrap="nowrap">';
                if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=move&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_picto($langs->trans("MoveToAnotherContract"), 'uparrow');
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=editline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                } else {
                    $return .= '&nbsp;';
                }
                if ($user->rights->contrat->creer && ($object->statut >= 0)) {
                    $return .= '&nbsp;';
                    $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=deleteline&amp;rowid=' . $objp->rowid . '">';
                    $return .= img_delete();
                    $return .= '</a>';
                }
                $return .= '</td>';

                $return .= "</tr>\n";

                // Dates de en service prevues et effectives
                if ($objp->subprice >= 0) {
                    $return .= '<tr ' . $bc[$var] . '>';
                    $return .= '<td colspan="6">';

                    // Date planned
                    $return .= $langs->trans("DateStartPlanned") . ': ';
                    if ($objp->date_debut) {
                        $return .= dol_print_date($db->jdate($objp->date_debut));
                        // Warning si date prevu passee et pas en service
                        if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        $return .= $langs->trans("Unknown");
                    $return .= ' &nbsp;-&nbsp; ';
                    $return .= $langs->trans("DateEndPlanned") . ': ';
                    if ($objp->date_fin) {
                        $return .= dol_print_date($db->jdate($objp->date_fin));
                        if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) {
                            $return .= " " . img_warning($langs->trans("Late"));
                        }
                    }
                    else
                        $return .= $langs->trans("Unknown");

                    $return .= '</td>';
                    $return .= '</tr>';
                }
            }
            // Ligne en mode update
            else {
                $return .= '<form name="update" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post">';
                $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $return .= '<input type="hidden" name="action" value="updateligne">';
                $return .= '<input type="hidden" name="elrowid" value="' . GETPOST('rowid') . '">';
                // Ligne carac
                $return .= "<tr $bc[$var]>";
                $return .= '<td>';
                if ($objp->fk_product) {
                    $productstatic->id = $objp->fk_product;
                    $productstatic->type = $objp->ptype;
                    $productstatic->ref = $objp->pref;
                    $return .= $productstatic->getNomUrl(1, '', 20);
                    $return .= $objp->label ? ' - ' . dol_trunc($objp->label, 56) : '';
                    $return .= '<br>';
                } else {
                    $return .= $objp->label ? $objp->label . '<br>' : '';
                }
                $return .= '<textarea name="eldesc" cols="70" rows="1">' . $objp->description . '</textarea></td>';
                $return .= '<td align="right">';
                $return .= $form->load_tva("eltva_tx", $objp->tva_tx, $mysoc, $object->thirdparty);
                $return .= '</td>';
                $return .= '<td align="right"><input size="5" type="text" name="elprice" value="' . price($objp->subprice) . '"></td>';
                $return .= '<td align="center"><input size="2" type="text" name="elqty" value="' . $objp->qty . '"></td>';
                $return .= '<td align="right" nowrap="nowrap"><input size="1" type="text" name="elremise_percent" value="' . $objp->remise_percent . '">%</td>';
                $return .= '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="' . $langs->trans("Modify") . '">';
                $return .= '<br><input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
                $return .= '</td>';
                // Ligne dates prevues
                $return .= "<tr $bc[$var]>";
                $return .= '<td colspan="5">';
                $return .= $langs->trans("DateStartPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_debut), "date_start_update", $usehm, $usehm, ($db->jdate($objp->date_debut) > 0 ? 0 : 1), "update");
                $return .= '<br>' . $langs->trans("DateEndPlanned") . ' ';
                $form->select_date($db->jdate($objp->date_fin), "date_end_update", $usehm, $usehm, ($db->jdate($objp->date_fin) > 0 ? 0 : 1), "update");
                $return .= '</td>';
                $return .= '</tr>';

                $return .= "</form>\n";
            }

            $db->free($result);
        } else {
            dol_print_error($db);
        }

        if ($object->statut > 0) {
            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td colspan="6"><hr></td>';
            $return .= "</tr>\n";
        }

        $return .= "</table>";


        /*
         * Confirmation to delete service line of contract
         */
        if ($action == 'deleteline' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("DeleteContractLine"), $langs->trans("ConfirmDeleteContractLine"), "confirm_deleteline", '', 0, 1);
            if ($ret == 'html')
                $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation to move service toward another contract
         */
        if ($action == 'move' && !$_REQUEST["cancel"] && $user->rights->contrat->creer && $idLigne == GETPOST('rowid')) {
            $arraycontractid = array();
            foreach ($arrayothercontracts as $contractcursor) {
                $arraycontractid[$contractcursor->id] = $contractcursor->ref;
            }
            //var_dump($arraycontractid);
            // Cree un tableau formulaire
            $formquestion = array(
                'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&lineid=" . GETPOST('rowid'), $langs->trans("MoveToAnotherContract"), $langs->trans("ConfirmMoveToAnotherContract"), "confirm_move", $formquestion);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation activation
         */
        if ($action == 'active' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("ActivateService"), $langs->trans("ConfirmActivateService", dol_print_date($dateactstart, "%A %d %B %Y")), "confirm_active", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($action == 'closeline' && !$_REQUEST["cancel"] && $user->rights->contrat->activer && $idLigne == GETPOST('ligne')) {
            $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            $comment = GETPOST('comment');
            $form->form_confirm($_SERVER["PHP_SELF"] . "?id=" . $object->id . "&ligne=" . GETPOST('ligne') . "&date=" . $dateactstart . "&dateend=" . $dateactend . "&comment=" . urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService", dol_print_date($dateactend, "%A %d %B %Y")), "confirm_closeline", '', 0, 1);
            $return .= '<table class="notopnoleftnoright" width="100%"><tr ' . $bc[false] . ' height="6"><td></td></tr></table>';
        }


        // Area with status and activation info of line
        if ($object->statut > 0) {
            $return .= '<table class="notopnoleft" width="100%">';

            $return .= '<tr ' . $bc[false] . '>';
            $return .= '<td>' . $langs->trans("ServiceStatus") . ': ' . $objL->getLibStatut(4) . '</td>';
            $return .= '<td width="30" align="right">';
            if ($user->societe_id == 0) {
                if ($object->statut > 0 && $action != 'activateline' && $action != 'unactivateline') {
                    $tmpaction = 'activateline';
                    if ($objp->statut == 4)
                        $tmpaction = 'unactivateline';
                    $return .= '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=' . $tmpaction . '">';
                    $return .= img_edit();
                    $return .= '</a>';
                }
            }
            $return .= '</td>';
            $return .= "</tr>\n";

            $return .= '<tr ' . $bc[false] . '>';

            $return .= '<td>';
            // Si pas encore active
            if (!$objp->date_debut_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                if ($objp->date_debut_reelle)
                    $return .= dol_print_date($objp->date_debut_reelle);
                else
                    $return .= $langs->trans("ContractStatusNotRunning");
            }
            // Si active et en cours
            if ($objp->date_debut_reelle && !$objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
            }
            // Si desactive
            if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                $return .= $langs->trans("DateStartReal") . ': ';
                $return .= dol_print_date($objp->date_debut_reelle);
                $return .= ' &nbsp;-&nbsp; ';
                $return .= $langs->trans("DateEndReal") . ': ';
                $return .= dol_print_date($objp->date_fin_reelle);
            }
            if (!empty($objp->comment))
                $return .= "<br>" . $objp->comment;
            $return .= '</td>';

            $return .= '<td align="center">&nbsp;</td>';

            $return .= '</tr>';
            $return .= '</table>';
        }

        if ($user->rights->contrat->activer && $action == 'activateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Activer la ligne de contrat
             */
            $return .= '<form name="active" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . GETPOST('ligne') . '&amp;action=active" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';
            //$return .= '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';
            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("DateServiceActivate") . '</td><td>';
            $return .= $form->select_date($dateactstart, '', $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td>' . $langs->trans("DateEndPlanned") . '</td><td>';
            $return .= $form->select_date($dateactend, "end", $usehm, $usehm, '', "active");
            $return .= '</td>';

            $return .= '<td align="center" rowspan="2" valign="middle">';
            $return .= '<input type="submit" class="button" name="activate" value="' . $langs->trans("Activate") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td>';

            $return .= '</tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td colspan="3"><input size="80" type="text" name="comment" value="' . GETPOST('comment') . '"></td></tr>';

            $return .= '</table>';

            $return .= '</form>';
        }

        if ($user->rights->contrat->activer && $action == 'unactivateline' && $idLigne == GETPOST('ligne')) {
            /**
             * Desactiver la ligne de contrat
             */
            $return .= '<form name="closeline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;ligne=' . $idLigne . '&amp;action=closeline" method="post">';
            $return .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

            $return .= '<table class="noborder" width="100%">';

            // Definie date debut et fin par defaut
            $dateactstart = $objp->date_debut_reelle;
            if (GETPOST('remonth'))
                $dateactstart = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
            elseif (!$dateactstart)
                $dateactstart = time();

            $dateactend = $objp->date_fin_reelle;
            if (GETPOST('endmonth'))
                $dateactend = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
            elseif (!$dateactend) {
                if ($objp->fk_product > 0) {
                    $product = new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                }
            }
            $now = dol_now();
            if ($dateactend > $now)
                $dateactend = $now;

            $return .= '<tr ' . $bc[$var] . '><td colspan="2">';
            if ($objp->statut >= 4) {
                if ($objp->statut == 4) {
                    $return .= $langs->trans("DateEndReal") . ' ';
                    $form->select_date($dateactend, "end", $usehm, $usehm, ($objp->date_fin_reelle > 0 ? 0 : 1), "closeline");
                }
            }
            $return .= '</td>';

            $return .= '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="' . $langs->trans("Close") . '"><br>';
            $return .= '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
            $return .= '</td></tr>';

            $return .= '<tr ' . $bc[$var] . '><td>' . $langs->trans("Comment") . '</td><td><input size="70" type="text" class="flat" name="comment" value="' . GETPOST('comment') . '"></td></tr>';
            $return .= '</table>';

            $return .= '</form>';
        }

//        $return .= '</td>'; // End td if line is 1
//        $return .= '</tr>';
//        $return .= '<tr><td style="border-right: 1px solid #' . $colorb . '">&nbsp;';
        return $return;
    }

}

?>
