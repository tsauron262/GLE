<?php
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contrat.class.php");

class contratSAV extends Synopsis_Contrat{
    public function getExtraHeadTab($head)
    {
        global $langs;
        $h = count($head);
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/savByContrat.php?id='.$this->id;
        $head[$h][1] = $langs->trans("SAV");
        $head[$h][2] = 'SAV';
        $h++;
        return($head);
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
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">SAV par d&eacute;faut
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">Dur&eacute;e de l\'extension
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">Date de d&eacute;but
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">Date de fin
                                  <td width=100 align=center style="min-width: 100px;max-width: 100px;width: 100px;">Produit-Contrat
                                  <td width=100 align=right style="min-width: 100px;max-width: 100px;width: 100px;">Statut
                                  <td width=100 align=right style="min-width: 100px;max-width: 100px;width: 100px;">Prix&nbsp;';
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
//    public function display1Line($val)
//    {
//
//        global $user, $conf, $lang;
//        $html = "<li id='".$val->id."' class='ui-state-default'>";
//        $html .= "<table  width=100%><tr class='ui-widget-content'><td width=16 rowspan=3>";
//        if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
//        {
//            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
//        }
//        if ($val->product)
//        {
//            $contratProd = "-";
//            $price = price($val->total_ht * $val->qty,2);
//            if ($this->lineTkt[$val->id]['fk_contrat_prod'])
//            {
//                $tmpcontratProd = new Product($this->db);
//                $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
//                $contratProd = $tmpcontratProd->getNomUrl(1);
//                $price = price($tmpcontratProd->price,2);
//            }
//            $spanExtra = "";
//            $spanExtraFin = "";
//            if ($val->statut != 5)
//            {
//            if ( time() > $this->lineTkt[$val->id]['dfin'])
//            {
//                $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
//                $spanExtraFin = "</span>";
//            } else if (time() > $this->lineTkt[$val->id]['dfin'] - 10 * 24 * 3600)
//            {
//                $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
//                $spanExtraFin = "</span>";
//            }
//            }
//            $li = new ContratLigne($this->db);
//            $li->fetch($val->id);
//            $html .= "<td title='Num&eacute;ro de s&eacute;rie : ".$this->lineTkt[$val->id]['serial_number']."' align=left>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle .'<br/>'.$val->desc."</font>
//                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>" . ($val->product->durSav>0?$val->product->durSav:0)."
//                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$this->lineTkt[$val->id]['durVal']. "
//                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$spanExtra.date("d/m/Y",$this->lineTkt[$val->id]['ddeb']).$spanExtraFin ."
//                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$spanExtra.date('d/m/Y',$this->lineTkt[$val->id]['dfin']).$spanExtraFin. "
//                   <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$contratProd. "
//                   <td nowrap=1  valign=top align=right style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$li->getLibStatut(5). "
//                   <td nowrap=1  valign=top align=right style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$price."&nbsp;";
////        require_once('Var_Dump.php');
////        $val1 = $val;
////        $val1->db="";
////        $val1->product->db="";
////        Var_Dump::Display($this->lineTkt);
//            if ($this->statut!=2)
//            {
//                $html .= $this->displayStatusIco($val);
//            }
//        }
//         $html .= "</table>";
//        $html .= "</li>";
//        return($html);
//    }
//    
    
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
        return $html;
    }
  /*  public function displayDialog($type='add',$mysoc,$objp)
    {
        global $conf, $form, $db;
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

        $html .=  '<table style=" width: 900px; border: 1px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $html .=  '<tr>';
        $html .=  '<th style="border-bottom: 1px Solid #0073EA !important; " colspan="8"  class="ui-widget-header">Prix de l\'extension de garantie</th></tr><tr style="padding: 10px; ">';
        $html .=  "<td colspan=3>
                        <table width=100%>
                            <tr><td colspan=2 align=center>OU
                            <tr>
                                <td width=50% align=center>
                                    <table width=100%>
                                        <tr>";
        $html .=  '                         <td align=right>Prix (&euro;)</td><td align=left>';
        $html .=  "                             <input id='".$type."Price' name='".$type."Price' style='width: 100px; text-align: center;'/>";
        $html .=  '                         </td>';
        $html .=  '                         <td align=right>TVA<td align=left width=180>';
        $html .= $form->load_tva($type."Linetva_tx","19.6",$mysoc,$this->societe,"",0,false);
        $html .=  '                         </td>';
        $html .=  '                     </tr>';
        $html .=  '                 </table>';
        $html .=  '             <td width=50% align=center style="border-left: 4px #0073EA double; ">';
        $html .=  '                 <table width="100%">';
        $html .=  '                     <tr>';
        $html .=  '                         <td style="padding-left: 40px">';
        $filter="2";
        if($conf->global->PRODUIT_MULTIPRICES == 1)
            $html .= $form->select_produits('','pcontrat_idprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
        else
            $html .= $form->select_produits('','pcontrat_idprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
        if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
        $html .=  '<div class="nocellnopadd" id="ajdynfieldpcontrat_idprod_'.$type.'"></div>';

        $html .=  '                 </table>';
        $html .=  '         </tr>';

        $html .=  '     </table>';
        $html .=  '</table>';

        $html .=  '<table style="width: 900px;  border-collapse: collapse; margin-top: 5px;"  cellpadding=10>';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA; ">';
        $html .=  '<th colspan=10" class="ui-widget-header" style=" border-bottom: 1px Solid #0073EA;" >Chronologie</th>';
        $html .=  '</tr>';
        $html .=  "<tr  class='ui-state-default' style='border: 1px Solid #0073EA; '>";
        $html .=  '<td>Date de d&eacute;but pr&eacute;vue</td>';
        $html .=  '<td>
                        <input value="'. date('d').'/'.date('m').'/'.date('Y') .'" style="text-align: center;" type="text" name="dateDeb'.$type.'" id="dateDeb'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left;margin-right: 3px; margin-top: 1px;"').'</td>';
        $html .=  '<td>Dur&eacute;e<br/><em><small>(en mois)</small></em></td>';
//        calendar.png
        $html .=  '<td><input type="text" id="'.$type.'Dur" name="'.$type.'Dur"></td>';
        $html .=  "</table>";

        $html .=  '</form>';
        $html .=  '</div>';
        return ($html);

    }
   
   */
    public function validate($user, $force_number='', $notrigger = 0)
    {
        global $langs, $conf;
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
        $this->sumSav();
        print "<br/>";
        print "<div class='titre'>R&eacute;sum&eacute;</div>";
        print "<table cellpadding=15 width=300>";
//        countTkt[$res->rowid]=array('total'=>$res->qty , 'restant'=> 10,'consomme' => 10)
        $total = 0;
        $restant = 0;
        $consome = 0;
        foreach($this->countTkt as $key=>$val)
        {
            $consome += $val["consomme"];
            $restant += $val["restant"];
            $total += $val["total"];
        }
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>Total HT<td width=150 class='ui-widget-content'>".price($this->totalHT)." &euro;";
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TVA<td width=150 class='ui-widget-content'>".price($this->totalTVA)." &euro;";
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TTC<td width=150 class='ui-widget-content'>".price($this->totalTTC)." &euro;";


        print "</table>";
    }
    public function sumSav()
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat =".$this->id;
        $sql = $this->db->query($requete);
        $total = 0;
        $tva=0;
        $ttc = 0;
        while ($res = $this->db->fetch_object($sql))
        {
            $this->sumSAV[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);
            $total += $res->total_ht ;
            $tva += $res->total_tva;
            $ttc += $res->total_ttc;
        }
        $this->totalHT = $total;
        $this->totalTVA = $tva;
        $this->totalTTC = $ttc;
        return($this->sumSAV);
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
                if ($user->rights->contrat->creer) $html .=  '<a class="butAction" href="card.php?id='.$this->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
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
                    $html .=  '<a class="butAction" href="card.php?id='.$this->id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a>';
            }

//             $html .=  "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$this->id."&action=generatePdf>G&eacute;n&eacute;rer</a>";
            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if ($this->statut != 2 && (($user->rights->contrat->creer && $this->statut == 0) || $user->rights->contrat->supprimer))
            {
                $html .=  '<a class="butActionDelete" href="card.php?id='.$this->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
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