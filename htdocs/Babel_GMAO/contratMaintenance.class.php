<?php
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
//TODO : dialog add
//TODO : dialog mod
//TODO : ajustement par date anniversaire
//TODO liste des interventions (tabs)
//TODO bouton programmer les Interventions
//TODO le contrat à un prix, pas les lignes de contrats !!!
//TODO renouvellement ou ajout de matos = changement de prix
//TODO la date est au contrat, pas au produit
//TODO changement du nombre d'intervention / mois

class contratMaintenance extends contrat {

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

    public function contratMaintenance($db) {
        $this->db = $db ;
        $this->product = new Product($this->db);
        $this->societe = new Societe($this->db);
        $this->user_service = new User($this->db);
        $this->user_cloture = new User($this->db);
        $this->client_signataire = new User($this->db);
    }
    public function fetch($id)
    {

        $ret = parent::fetch($id);
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
                      FROM Babel_GMAO_contrat_prop
                     WHERE contrat_refid =".$id;
        $sql = $this->db->query($requete);
//        print $requete;
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
//TODO Prob pas le serial_number

            $requete = "SELECT unix_timestamp(date_add(date_add(Babel_GMAO_contratdet_prop.DateDeb, INTERVAL Babel_GMAO_contratdet_prop.durValid month), INTERVAL ifnull(".MAIN_DB_PREFIX."product.durSav,0) MONTH)) as dfinprev,
                               unix_timestamp(date_add(date_add(Babel_GMAO_contratdet_prop.DateDeb, INTERVAL Babel_GMAO_contratdet_prop.durValid month), INTERVAL ifnull(".MAIN_DB_PREFIX."product.durSav,0) MONTH)) as dfin,
                               unix_timestamp(Babel_GMAO_contratdet_prop.DateDeb) as ddeb,
                               unix_timestamp(Babel_GMAO_contratdet_prop.DateDeb) as ddebprev,
                               ".MAIN_DB_PREFIX."contratdet.qty,
                               ".MAIN_DB_PREFIX."contratdet.rowid,
                               ".MAIN_DB_PREFIX."contratdet.subprice as pu,
                               Babel_GMAO_contratdet_prop.durValid as durVal,
                               Babel_GMAO_contratdet_prop.fk_contrat_prod,
                               Babel_product_serial_cont.serial_number
                          FROM Babel_GMAO_contratdet_prop, ".MAIN_DB_PREFIX."contratdet
                     LEFT JOIN ".MAIN_DB_PREFIX."product ON ".MAIN_DB_PREFIX."product.rowid = ".MAIN_DB_PREFIX."contratdet.fk_product
                     LEFT JOIN Babel_product_serial_cont ON Babel_product_serial_cont.element_id = ".MAIN_DB_PREFIX."contratdet.rowid AND Babel_product_serial_cont.element_type = 'contratSAV'
                         WHERE Babel_GMAO_contratdet_prop.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid
                           AND fk_contrat =".$id;
            $sql = $this->db->query($requete);
            while ($res=$this->db->fetch_object($sql))
            {
                $this->lineTkt[$res->rowid]=array(
                    'serial_number'=>$res->serial_number ,
                    'fk_contrat_prod' => ($res->fk_contrat_prod>0?$res->fk_contrat_prod:false),
                    'durVal' => $res->durVal,
                    'qty'=>$res->qty,
                    'pu'=>$res->pu,
                    'dfinprev'=>$res->dfinprev,
                    'dfin'=>$res->dfin,
                    'ddeb'=>$res->ddeb,
                    'ddebprev'=>$res->ddebprev);
            }
        }
        return($ret);
    }
    public function display1Line($val)
    {

        global $user, $conf, $lang;
        $html = "<li id='".$val->id."' class='ui-state-default'>";
        $html .= "<table  width=100%><tr class='ui-widget-content'><td width=16 rowspan=1>";
        if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }
        if ($val->product)
        {
            $contratProd = "-";
            $price = price($val->total_ht * $val->qty,2);
            if ($this->lineTkt[$val->id]['fk_contrat_prod'])
            {
                $tmpcontratProd = new Product($this->db);
                $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
                $contratProd = $tmpcontratProd->getNomUrl(1);
                $price = price($tmpcontratProd->price,2);
            }
            $spanExtra = "";
            $spanExtraFin = "";
            if ($val->statut != 5)
            {
            if ( time() > $this->lineTkt[$val->id]['dfin'])
            {
                $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                $spanExtraFin = "</span>";
            } else if (time() > $this->lineTkt[$val->id]['dfin'] - 10 * 24 * 3600)
            {
                $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                $spanExtraFin = "</span>";
            }
            }
            $li = new ContratLigne($this->db);
            $li->fetch($val->id);
            $html .= "<td align=left>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle .'<br/>'.$val->desc."</font>
                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$this->lineTkt[$val->id]['serial_number']. "
                   <td nowrap=1  valign=top align=right style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>". $li->getLibStatut(5);
//        require_once('Var_Dump.php');
//        $val1 = $val;
//        $val1->db="";
//        $val1->product->db="";
//        Var_Dump::Display($this->lineTkt);
            if ($this->statut!=2)
            {
                $html .= $this->displayStatusIco($val);
            }
        }
         $html .= "</table>";
        $html .= "</li>";
        return($html);
    }
    public function displayLine()
    {
        global $user, $conf, $lang;
            $html = "";
            $html .= "<ul id='sortable' style='list-style: none; padding-left: 0px; padding-top:0; margin-top: 0px;'>";
            $html .= '<li class="titre ui-state-default ui-widget-header">
                          <table width=100%>
                              <tr><td width=16>&nbsp;
                                  <td >Produit / Description
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">Num. s&eacute;rie
                                  <td width=100 align=right style="min-width: 100px;max-width: 100px;width: 100px;">Statut';
            if ($this->statut!=2)
            {
                $html .= '            <td width=50 align=center style="min-width: 50px;max-width: 50px;width: 50px;">Action';
            }
            $html .= '        </tr>
                          </table>
                      </li>';
            foreach($this->lignes as $key => $val)
            {
                $html .= $this->display1line($val);
            }
            $html .= "</ul>";
            return ($html);

    }


    public function getExtraHeadTab($head)
    {
        global $langs;
        $h = count($head);
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/intervByContrat.php?id='.$this->id;
        $head[$h][1] = $langs->trans("Interv.");
        $head[$h][2] = 'Interv';
        $h++;
        return($head);
    }

    public function displayStatusIco($val)
    {
        global $conf, $user, $langs;
        $html .= '<td nowrap=1  align="center" valign=top  nowrap="nowrap" style="width:50px;padding-top: 0.5em;">';

        if ($this->statut < 2  && $user->rights->contrat->creer && ($val->statut < 4 || $val->statut == 4 && $conf->global->CONTRAT_EDITWHENVALIDATED ))
        {
            $html .= '<div style="width: 48px;">';
        } else if ($val->statut == 4 && $this->statut == 1 && $user->rights->contrat->activer)
        {
            $html .= '<div style="width: 32px;">';
        } else {
            $html .= '<div style="width: 16px;">';
        }
            //Si $contrat->statut==1 => edition possible de la date effective et la date de fin effective + commentaire + activer
            //var_dump($user->rights->contrat);
            if ($this->statut == 1 && $user->rights->contrat->activer )
            {
                if ($val->statut == 0)
                {
                    $html .= '<span onclick="activateLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= img_tick('Activer');
                    $html .= '&nbsp;</span>';
                } else if ($val->statut == 4)
                {
//                    $html .= '<span title="D&eacute;sactiver" class="ui-icon ui-icon-arrowrefresh-1-n" style="float: left; width:16px; cursor: pointer;" onclick="unactivateLine(this,'.$this->id.','.$val->id.');" >';
//                    $html .= '</span>';

                    $html .= '<span class="ui-icon ui-icon-arrowreturnthick-1-e" title="Cloturer" style="float: left; width:16px;cursor: pointer;" onclick="closeLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= '</span>';
                }
            }
            if ($this->statut==1 &&  !$conf->global->CONTRAT_EDITWHENVALIDATED){
                $html .= '&nbsp;';
            } else  if ($this->statut < 2  && $user->rights->contrat->creer && ($val->statut < 4 || $val->statut == 4 && $conf->global->CONTRAT_EDITWHENVALIDATED ))
            {
                if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)
                    && $user->rights->contrat->creer )
                {
                    $html .= '<span onclick="editLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= img_edit();
                    $html .= '</span>';
                } else {
                    $html .= '&nbsp;';
                }
            } else {
                $html .= '&nbsp;';
            }
            if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
                && $user->rights->contrat->creer&& $val->statut < 4)
            {
                $html .= '&nbsp;';
                $html .= '<span onclick="deleteLine(this,'.$this->id.','.$val->id.');" >';
                $html .= img_delete();
                $html .= '</span>';
            }
            $html .= '</div></td>';
        return ($html);
    }

    public function displayExtraInfoCartouche()
    {

        $html = "";
        if ($this->qte > 0 )
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>Intervention";
            $html .= "    <td class='ui-widget-content'>".$this->qte . " par mois ";
        }
        if ($this->hotline > 0)
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>Hotline";
            $html .= "    <td class='ui-widget-content'>Oui";
        }
        if ($this->telemaintenance > 0)
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>T&eacute;l&eacute;maintenance";
            $html .= "    <td class='ui-widget-content'>Oui";
        }
        if ($this->maintenance > 0)
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>Maintenance sur site";
            $html .= "    <td class='ui-widget-content'>Oui";
        }

        return $html;
    }
    public function displayDialog($type='add',$mysoc,$objp)
    {
        global $conf, $form;
        $html .=  '<div id="'.$type.'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='".$type."Form' method='POST' onsubmit='return(false);'>";

//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .=  '<table style="width: 900px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA !important">';
        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important" colspan="4"  class="ui-widget-header">Recherche de produits</th></tr>';
        $html .=  '<tr style="border-top: 1px Solid #0073EA !important">';
        $html .=  '<td style="width: 300px; padding-top: 5px; padding-bottom: 3px;">';
            // multiprix
            $filter="0";
            if($conf->global->PRODUIT_MULTIPRICES == 1)
                $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
            else
                $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
            if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
        $html .= '                   <td style=" padding-top: 5px; padding-bottom: 3px;">';
        $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idprod_'.$type.'"></div>';


        $html .=  '</td><td  style=" padding-top: 5px; padding-bottom: 3px;border-right: 1px Solid #0073EA;">&nbsp;</td>';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .=  'Description<br/>';
        $html .=  "<textarea style='width: 600px; height: 3em' name='".$type."Desc' id='".$type."Desc'></textarea>";
        $html .=  '</td>';
        $html .=  '</tr>';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=1>Num&eacute;ro de s&eacute;rie';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=2><input type="text" style="width: 300px" name="'.$type.'serial" id="'.$type.'serial">';
        $html .=  "</table>";


        $html .=  '</form>';
        $html .=  '</div>';
        return ($html);

    }
    public function validate($user,$langs,$conf)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 1 ,date_valid=now()";
        $sql .= " WHERE rowid = ".$this->id. " AND statut = 0";
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);
            $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4 WHERE fk_contrat =".$this->id;
            $resql = $this->db->query($requete);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_VALIDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        } else {
            $this->error=$this->db->error();
            return -1;
        }
    }

    public function displayExtraStyle()
    {
        $this->sumMnt();
        print "<br/>";
        print "<div class='titre'>R&eacute;sum&eacute;</div>";
        print '<div id="tabs">';
        print "<table cellpadding=15 width=600>";
//        countTkt[$res->rowid]=array('total'=>$res->qty , 'restant'=> 10,'consomme' => 10)
//TODO lister les interventions (Demande et fiche)
//    public $sumDInterByStatut=array();
//    public $sumDInterByUser=array();
//    public $sumDInterCal=array();
//    public $totalDInter = 0;


        print "<tr><th width=150 class='ui-state-default ui-widget-header'>Total HT<td align=right width=150 class='ui-widget-content'>".price($this->totalHT)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Demande Interv.<td align=right width=150 class='ui-widget-content'>".$this->totalDInter."";
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TVA<td align=right width=150 class='ui-widget-content'>".price($this->totalTVA)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Fiche Interv.<td align=right width=150 class='ui-widget-content'>".$this->totalFInter."";
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TTC<td align=right width=150 class='ui-widget-content'>".price($this->totalTTC)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Restant<td align=right width=150 class='ui-widget-content'>".intval($this->totalDInter - $this->totalFInter);


        print "</table>";
    }
    public function sumMnt()
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat =".$this->id;
        $sql = $this->db->query($requete);
        $total = 0;
        $tva=0;
        $ttc = 0;
        while ($res = $this->db->fetch_object($sql))
        {
            $this->sumMNT[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);
            $total += $res->total_ht ;
            $tva += $res->total_tva;
            $ttc += $res->total_ttc;
        }
        $this->totalHT = $total;
        $this->totalTVA = $tva;
        $this->totalTTC = $ttc;

        //Par statut d'intervention

        $requete = " SELECT DISTINCT fk_statut FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." ORDER by fk_statut";
        $sql = $this->db->query($requete);
        while($res=$this->db->fetch_object($sql))
        {
            $this->sumDInterByStatut[$res->fk_statut]=0;
        }

        $requete = " SELECT DISTINCT ifnull(fk_user_target, fk_user_prisencharge) as fk_user FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." ORDER by fk_statut";
        $sql = $this->db->query($requete);
        while($res=$this->db->fetch_object($sql))
        {
            $this->sumDInterByUser[$res->fk_user]=0;
        }
        $requete = "SELECT min(datei) as mini, max(datei) as maxi FROM FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." GROUP BY fk_contrat";
        $sql = $this->db->query($requete);
        $res=$this->db->fetch_object($sql);
        $mini = strtotime($res->mini);
        while (date('w',$mini) != 1)
        {
            $mini -= 3600 * 24;
        }
        $maxi = strtotime($res->maxi);
        while (date('w',$maxi) != 5)
        {
            $maxi += 3600 * 24;
        }
        for ($i=$mini; $i<=$maxi;$i+= 3600 * 24)
        {
            if (date('w',$i) == 0) continue;
            if (date('w',$i) == 6) continue;
            $this->sumDInterCal[$i]=0;
        }

        $requete = "SELECT fk_statut, datei, ifnull(fk_user_target, fk_user_prisencharge) as fk_user  FROM ".MAIN_DB_PREFIX."Synopsis_demandeInterv WHERE fk_contrat = ".$this->id.' ORDER BY datei DESC';
        $sql = $this->db->query($requete);
        $this->totalDInter = 0;
        while ($res = $this->db->fetch_object($sql))
        {
            //$this->sumInterv[$res->rowid]=array('ref' => $res->ref , 'datei' => date('d/m/Y',strtotime($res->datei)), 'dateiEpoch' => strtotime($res->datei) , 'duree' => $res->duree );
            $this->sumDInterByStatut[$res->fk_statut]++;
            $this->sumDInterCal[strtotime($res->datei)]++;
            $this->sumDInterByUser[$res->fk_user]++;
            $this->totalDInter++;
        }

        //Fiche intervention
        //Par statut
        //Par Intervenant
        //Celandaire

        return($this->sumMNT);
    }
    public function displayButton($nbofservices)
    {
        global $langs, $conf, $user;
        $html = "";
        if ($user->societe_id == 0)
        {
            $html .=  '<div class="tabsAction">';

            if ($this->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) $html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
//                else $html .=  '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a>';
            }

/*            if ($conf->facture->enabled && $this->statut > 0)
            {
                $langs->load("bills");
                if ($user->rights->facture->creer) $html .=  '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;contratid='.$this->id.'&amp;socid='.$this->societe->id.'">'.$langs->trans("CreateBill").'</a>';
                else $html .=  '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
            }
*/
            if ($this->nbofservicesclosed < $nbofservices)
            {
                    $html .=  '<a class="butAction" href="fiche.php?id='.$this->id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a>';
            }

//             $html .=  "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$this->id."&action=generatePdf>G&eacute;n&eacute;rer</a>";
            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if ($this->statut != 2 && (($user->rights->contrat->creer && $this->statut == 0) || $user->rights->contrat->supprimer))
            {
                $html .=  '<a class="butActionDelete" href="fiche.php?id='.$this->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            $html .=  "</div>";
            $html .=  '<br>';
        }
        return($html);
    }
   public function cloture($user,$langs='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 2";
        $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 1";

        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);
            $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 5 WHERE fk_contrat =".$this->id;
            $resql = $this->db->query($requete);

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CONTRACT_CLOSE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

}
?>