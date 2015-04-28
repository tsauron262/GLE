<?php

require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");

class PropalGA extends Propal{

    public $db;
    public $totAFin=0;
    private $countEmp = 0;
    public $FinancementPropalDet=array();

    public function PropalGA($db, $socid="", $propalid=0) {
        $this->db=$db;

        global $langs;

      $this->socid = $socid;
      $this->id = $propalid;
      $this->products = array();
      $this->remise = 0;
      $this->remise_percent = 0;
      $this->remise_absolue = 0;

      $langs->load("propals");
      $this->labelstatut[0]=$langs->trans("PropalStatusDraft");
      $this->labelstatut[1]=$langs->trans("PropalStatusValidated");
      $this->labelstatut[2]=$langs->trans("PropalStatusSigned");
      $this->labelstatut[3]=$langs->trans("PropalStatusNotSigned");
      $this->labelstatut[4]=$langs->trans("PropalStatusBilled");
      $this->labelstatut[5]=$langs->trans("PropalStatusWaitingValidation");
      $this->labelstatut_short[0]=$langs->trans("PropalStatusDraftShort");
      $this->labelstatut_short[1]=$langs->trans("Opened");
      $this->labelstatut_short[2]=$langs->trans("PropalStatusSignedShort");
      $this->labelstatut_short[3]=$langs->trans("PropalStatusNotSignedShort");
      $this->labelstatut_short[4]=$langs->trans("PropalStatusBilledShort");
      $this->labelstatut_short[5]=$langs->trans("PropalStatusWaitingValidationShort");

    }
    private function drawMontantSimple()
    {
        $html="";
        if ($this->statut == 0 )
        {
            $html = "<tr><th class='ui-state-default ui-widget-header'>Montant &agrave; financer (&euro;)";
            $html .= "    <td class='ui-widget-content'><input type=text  id='Materiel' value=".$this->totAFin."  name='Materiel' />";
            $html .= "    <input type=hidden  id='Logiciel' value=0  name='Logiciel' />";
            $html .= "    <input type=hidden  id='Service' value=0 name='Service' />";
        } else {
            $html  = "<tr><th class='ui-state-default ui-widget-header'>Montant &agrave; financer (&euro;)";
            $html .= "    <td class='ui-widget-content'><span  id='Materiel'   name='Materiel'>".$this->totAFin."</span>";

        }
        return($html);

    }
    private function drawMontantDetaille()
    {
        $html ="";
        if ($this->statut == 0 )
        {
            $html  = "<tr><th class='ui-state-default ui-widget-header'>Montant mat&eacute;riel (&euro;)";
            $html .= "    <td class='ui-widget-content'><input type=text  id='Materiel' value=".floatval($this->FinancementPropalDet[0]['totHT'])."  name='Materiel' />";
            $html .= "<tr><th class='ui-state-default ui-widget-header'>Montant logiciel (&euro;)";
            $html .= "    <td class='ui-widget-content'><input type=text  id='Logiciel' value=".floatval($this->FinancementPropalDet[1]['totHT'])."  name='Logiciel' />";
            $html .= "<tr><th class='ui-state-default ui-widget-header'>Montant service (&euro;)";
            $html .= "    <td class='ui-widget-content'><input type=text  id='Service' value=".floatval($this->FinancementPropalDet[2]['totHT'])." name='Service' />";
        } else {
            $html  = "<tr><th class='ui-state-default ui-widget-header'>Montant mat&eacute;riel (&euro;)";
            $html .= "    <td class='ui-widget-content' align='center'><span  id='Materiel'  name='Materiel' >".price(floatval($this->FinancementPropalDet[0]['totHT']))."</span>";
            $html .= "<tr><th class='ui-state-default ui-widget-header'>Montant logiciel (&euro;)";
            $html .= "    <td class='ui-widget-content' align='center'><span  id='Logiciel'  name='Logiciel' >".price(floatval($this->FinancementPropalDet[1]['totHT']))."</span>";
            $html .= "<tr><th class='ui-state-default ui-widget-header'>Montant service (&euro;)";
            $html .= "    <td class='ui-widget-content' align='center'><span  id='Service' name='Service' >".price(floatval($this->FinancementPropalDet[2]['totHT']))."</span>";

        }
        return($html);

    }
    public function drawMontant($displayMode)
    {
        global $conf;
        if (($conf->global->BABELGA_MEDIUM_MONTANTTOT==0 && $displayMode==1)|| $displayMode==0)
        {
            //retourne le mode simple
            return($this->drawMontantSimple());
        } else if (($conf->global->BABELGA_MEDIUM_MONTANTTOT==1 && $displayMode==1)|| $displayMode==2){
            //retourne le mode avance
            return($this->drawMontantDetaille());
        }
        $this->countEmp ++;
    }
    public function getFinDet()
    {

        $requete = "SELECT ".MAIN_DB_PREFIX."propaldet.rowid as pid,
                           ".MAIN_DB_PREFIX."propaldet.total_ht as tht,
                           Babel_GA_propale.tauxFinancement,
                           Babel_GA_propale.financement_period_refid,
                           Babel_GA_propale.tauxMarge,
                           Babel_GA_propale.montantTotHTAFinancer,
                           Babel_GA_propale.isTx0,
                           Babel_GA_propale.duree,
                           Babel_GA_propale.echu,
                           Babel_financement_period.NbIterAn,
                           Babel_financement_period.Description2
                      FROM ".MAIN_DB_PREFIX."propaldet,
                           Babel_GA_propale,
                           Babel_financement_period
                     WHERE fk_propal =".$this->id. "
                       AND ".MAIN_DB_PREFIX."propaldet.rowid = Babel_GA_propale.propaldet_refid
                       AND Babel_GA_propale.financement_period_refid = Babel_financement_period.id ";
        $sql = $this->db->query($requete);

        dol_syslog("PropalGA::Fetch Error sql=$sql ".$this->error,LOG_DEBUG);

        $this->FinancementPropalDet = array();
        $iter=0;
        $totAFin = 0;
        while ($res=$this->db->fetch_object($sql))
        {
            $this->FinancementPropalDet[$iter]['totHT'] = ($res->tht."x" != "x"?$res->tht:0);
            $this->FinancementPropalDet[$iter]['id'] = $res->pid;
            $this->FinancementPropalDet[$iter]['isTx0'] = $res->isTx0;
            $this->FinancementPropalDet[$iter]['montantTotHTAFinancer'] = $res->montantTotHTAFinancer;
            $this->FinancementPropalDet[$iter]['tauxMarge'] = $res->tauxMarge;
            $this->FinancementPropalDet[$iter]['tauxFinancement'] = $res->tauxFinancement;
            $this->FinancementPropalDet[$iter]['financement_period_refid'] = $res->financement_period_refid;
            $this->FinancementPropalDet[$iter]['duree'] = $res->duree;
            $this->FinancementPropalDet[$iter]['echu'] = $res->echu;
            $this->FinancementPropalDet[$iter]['NbIterAn'] = $res->NbIterAn;
            $this->FinancementPropalDet[$iter]['Description2'] = $res->Description2;
            $iter++;
            $this->totAFin += floatval($res->tht);
        }
        return($this->totAFin);
    }
    private function drawSimpleDurFin()
    {
        $html = '';
        if ($this->statut==0)
        {

            $html .=  '<tr><th class="ui-state-default ui-widget-header">Type de financement';
            $html .=  '    <td class="ui-state-default ui-widget-content">';

            $requete = "SELECT * FROM Babel_financement_period  WHERE active=1 order by NbIterAn DESC";
            $sql = $this->db->query($requete);
            $html .=  '<SELECT id="simpleModeSel">';
            $arrOptSimple=array();
            $firstId=false;
            while ($res = $this->db->fetch_object($sql))
            {
                $requete1 = "SELECT *
                               FROM Babel_GA_period_simple
                              WHERE financement_period_refid = ".$res->id. "
                           ORDER BY duree ASC";
                //Affiche la periode de financement simple dispo
                $sql1 = $this->db->query($requete1);
                while ($res1 = $this->db->fetch_object($sql1))
                {
                    $nbiteran = $res->NbIterAn;
                    $dureeTot = $res1->duree;
                    $anTot = $dureeTot / 12;
                    $count = $anTot * $nbiteran;
                    $echu = 'Terme &agrave; &eacute;choir';
                    if ($res1->echu == 0) $echu = 'A terme &eacute;chu';
                    $html .= '<option value="'.$res1->id.'">'.$count.' '.$res->Description2;
                    $html .= '&nbsp;'.$echu;
                    if ($dureeTot == $count)
                    {
                        $html .= "</option>";
                    } else {
                        $html .= '    &nbsp;(Soit : '.$dureeTot.' Mois) </option>';
                    }
                    $arrOptSimple[$res1->id]['echu']=$res1->echu;
                    $arrOptSimple[$res1->id]['amortperiod']=$res1->duree;
                    $arrOptSimple[$res1->id]['paymentsperyear']=$res->NbIterAn;
                    if (!$firstId) { $firstId = $res1->id; }
                }
            }
            $html .=  "</select>";
            $duree = ($this->FinancementPropalDet[0]['duree']."x" =="x"?$arrOptSimple[$firstId]['amortperiod']:$this->FinancementPropalDet[0]['duree']);
            $html .=  '<input type="hidden" name="amortperiod" id="amortperiod"  value="'.$duree.'" />';
            $NbIterAn = ($this->FinancementPropalDet[0]['NbIterAn'].'x' == "x"?$arrOptSimple[$firstId]['paymentsperyear']:$this->FinancementPropalDet[0]['NbIterAn']);
            $html .=  ' <input type="hidden" name="paymentsperyear" id="paymentsperyear" value="'.$NbIterAn .'" />';
            $echu = ($this->FinancementPropalDet[0]['echu']."x" =="x"?$arrOptSimple[$firstId]['echu']:$this->FinancementPropalDet[0]['echu']);
            $html .=  ' <input type="hidden" name="echu" id="echu" value="'.$echu.'">';

            $html.= "<script>";
            $html .= ' var jsonArr = '.json_encode($arrOptSimple).' ; ';
            $html.= "</script>";

        } else {
            $html .=  '<tr><th class="ui-state-default ui-widget-header">Type de financement';
            $html .=  '    <td class="ui-widget-content">';
            $tmpnbiteran = $this->FinancementPropalDet[0]['NbIterAn'];
            $tmpanTot = $this->FinancementPropalDet[0]['duree'] / 12;
            $count = $tmpanTot * $tmpnbiteran;
            $echu = 'Terme &agrave; &eacute;choir';
            if ($this->FinancementPropalDet[0]['echu'] == 0) $echu = 'A terme &eacute;chu';

            $html .=  '         <span id="simpleModeSel">'.$count.' '. $this->FinancementPropalDet[0]['Description2'] .'  '.$echu.'</span>';

            $duree = $this->FinancementPropalDet[0]['duree'];
            $html .=  '<input type="hidden" name="amortperiod" id="amortperiod"  value="'.$duree.'" />';
            $NbIterAn = $this->FinancementPropalDet[0]['NbIterAn'];
            $html .=  ' <input type="hidden" name="paymentsperyear" id="paymentsperyear" value="'.$NbIterAn .'" />';

            $echu = $this->FinancementPropalDet[0]['echu'];
            $html .=  ' <input type="hidden" name="echu" id="echu" value="'.$echu.'"/>';

        }

        return($html);
    }
    private function drawAdvancedDurFin($displayMode)
    {
        global $conf;
        $html = '';
        if ($this->statut==0)
        {
            $html .=  '<tr>';
            $html .=  '<td class="ui-state-default ui-widget-header"><label for="amortperiod">Dur&eacute;e <small>(en mois)</small></label></td>';
            $duree = ($this->FinancementPropalDet[0]['duree']."x" =="x"?36:$this->FinancementPropalDet[0]['duree']);
            $html .=  '<td class="ui-widget-content"><input type="text" name="amortperiod" id="amortperiod" size="20" value="'.$duree.'" /></td>';
            $html .=  '</tr>';
            $html .=  '<tr>';
            $html .=  '<td class="ui-state-default ui-widget-header"><label for="paymentsperyear">Nombre de loyers par an</label></td>';
            $NbIterAn = ($this->FinancementPropalDet[0]['NbIterAn'].'x' == "x"?12:$this->FinancementPropalDet[0]['NbIterAn']);
            $html .=  '<td nowrap class="ui-widget-content" style="padding: 6px 15px 6px 15px;">';
//var_dump($this->FinancementPropalDet[0]['echu']);
            $html .= ' <input type="text" name="paymentsperyear" id="paymentsperyear" size="20" value="'.$NbIterAn .'" />';
            if ($displayMode == 2 || ($displayMode == 1 && $conf->global->BABELGA_MEDIUM_CHOIXTERME == 1))
            {
                if ($this->FinancementPropalDet[0]['echu']==0 )
                {
                    $html .=  '  <br/><input type="checkbox" name="echu" id="echu" />';
                    $html .=  '<span id="echuTxt" style="font-size: 10px;">&nbsp;Terme &agrave; &eacute;choir</span></td>';
                } else {
                    $html .=  '  <br/><input type="checkbox" checked name="echu" id="echu" />';
                    $html .=  '<span id="echuTxt" style="font-size: 10px;">&nbsp;Terme &eacute;chu</span></td>';
                }
            }
            $html .=  '</tr>';
        } else {
         $html .=  '<tr>';
            $html .=  '<td class="ui-state-default ui-widget-header"><label for="amortperiod">Dur&eacute;e <small>(en mois)</small></label></td>';
            $duree = ($this->FinancementPropalDet[0]['duree']."x" =="x"?36:$this->FinancementPropalDet[0]['duree']);
            $html .=  '<td class="ui-widget-content"><span>'.$duree.'</span></td>';
            $html .=  '</tr>';
            $html .=  '<tr>';
            $html .=  '<td class="ui-state-default ui-widget-header"><label for="paymentsperyear">Nombre de loyers par an</label></td>';
            $NbIterAn = ($this->FinancementPropalDet[0]['NbIterAn'].'x' == "x"?12:$this->FinancementPropalDet[0]['NbIterAn']);
            $html .=  '<td nowrap class="ui-widget-content" style="padding: 6px 15px 6px 15px;">
                    <span>'.$NbIterAn .'</span>';
            if ($this->FinancementPropalDet[0]['echu']==1)
            {
                $html .=  '  <br/>';
                $html .=  '<span style="font-size: 10px;">&nbsp;Terme &agrave; &eacute;choir</span></td>';
            } else {
                $html .=  '  <br/>';
                $html .=  '<span style="font-size: 10px;">&nbsp;Terme &eacute;chu</span></td>';
            }
            $html .=  '</tr>';

            $duree = $this->FinancementPropalDet[0]['duree'];
            $html .=  '<input type="hidden" name="amortperiod" id="amortperiod"  value="'.$duree.'" />';
            $NbIterAn = $this->FinancementPropalDet[0]['NbIterAn'];
            $html .=  ' <input type="hidden" name="paymentsperyear" id="paymentsperyear" value="'.$NbIterAn .'" />';
            $echu = $this->FinancementPropalDet[0]['echu'];
            $html .=  ' <input type="hidden" name="echu" id="echu" />';


        }


        return($html);
    }
    public function drawDurFin($displayMode,$panel=1)
    {
        $html = '';
        global $conf;
        if (($conf->global->BABELGA_MEDIUM_DURSIMPLE==1 && $displayMode==1)|| $displayMode==0)
        {
            if ($panel == 1)
            {
                $html = $this->drawSimpleDurFin();
            }

        } else if(($conf->global->BABELGA_MEDIUM_DURSIMPLE==0 && $displayMode==1)|| $displayMode==2)
        {
            if ($panel == 2)
            {
                $html = $this->drawAdvancedDurFin($displayMode);
                $this->countEmp ++;
            }
        }
        return($html);

    }
    public function drawTxMarge($displayMode)
    {
        global $conf,$user;
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/BabelGA.class.php');
        $tauxObj = new BabelGA($this->db);
        $tauxMargeDeflt = $tauxObj->fetch_taux_marge($this,'propal');

        if ($this->statut == 0)
        {

            $tauxMarge = ($this->FinancementPropalDet[0]['tauxMarge']."x"=="x"?$tauxMargeDeflt:floatval($this->FinancementPropalDet[0]['tauxMarge']));
            if (($user->rights->GA->Taux->Admin || $user->rights->GA->MargeModifier ) && ($displayMode == 2
                || ($displayMode == 1 && $conf->global->BABELGA_MEDIUM_MARGEMODIFIER == 1 && $conf->global->BABELGA_MEDIUM_MARGEAFFICHE == 1)))
            {
                $html .=  '<td class="ui-state-default ui-widget-header"><label for="marge">Marge (%)</label></td>';
                $html .=  '<td class="ui-widget-content"><input type="text" name="marge" id="marge" size="20" value="'.$tauxMarge.'" /></td>';
                $html .=  '</tr>';

            } else if (($displayMode == 1 && $conf->global->BABELGA_MEDIUM_MARGEAFFICHE == 1 && $user->rights->GA->MargeAffiche) || ($displayMode == 2 && $user->rights->GA->MargeAffiche ) )
            {
                $html .=  '<td class="ui-state-default ui-widget-header"><label for="marge">Marge (%)</label></td>';
                $html .=  '<td class="ui-widget-content"><span name="marge" id="marge" />'.$tauxMarge.'</span>';
                $html .=  "<input type='hidden' name='marge' value='".$tauxMarge."' id='marge'>";
                $html .=  '</tr>';
            } else {
                $html .=  "<input type='hidden' name='marge' value='".$tauxMarge."' id='marge'>";
            }
        } else {
            $tauxMarge = ($this->FinancementPropalDet[0]['tauxMarge']."x"=="x"?$tauxMargeDeflt:floatval($this->FinancementPropalDet[0]['tauxMarge']));
            if ((($displayMode == 1 && $conf->global->BABELGA_MEDIUM_MARGEAFFICHE == 1)|| $displayMode == 2) && ( $user->rights->GA->MargeAffiche ||  $user->rights->GA->MargeModifier ||  $user->rights->Taux->Admin))
            {
                $html .=  '<td class="ui-state-default ui-widget-header"><label for="marge">Marge (%)</label></td>';
                $html .=  '<td class="ui-widget-content"><span name="marge" id="marge" />'.$tauxMarge.'</span>';
                $html .=  "<input type='hidden' name='marge' value='".$tauxMarge."' id='marge'>";
                $html .=  '</tr>';
            } else {
                $html .=  "<input type='hidden' name='marge' value='".$tauxMarge."' id='marge'>";
            }
        }
        return($html);

    }
    public $table22col=false;
    public function drawTotAFin($displayMode)
    {
        global $conf;
        $html = '';
        if ($displayMode == 2 || ($displayMode == 1 && $conf->global->BABELGA_MEDIUM_TOTAFFICHE == 1))
        {
            $html .= "<table id='tableFinancementResult'  style='float: none; clear: both; margin-top: 30px; border-collapse: collapse;' cellpadding=15>";
            $html .= "<tr><td width=400><table width=100% cellpadding=15 style='border-collapse: collapse;'>";

            $html .=  "<tr><th class='ui-widget-header ui-state-default'>Montant total &agrave; financer HT : </th>";
            $total=0;
            $html .=  "    <td class='ui-widget-content ui-state-default' align=center><span id='total'>".price($total)." &euro;</span></td>";
            $html .=  "<tr><th class='ui-widget-header ui-state-default black'>Montant total TVA : </th>";
            $totaltva=$total*(0.196);
            $html .=  "    <td class='ui-widget-content ui-state-default black' align=center><span id='totalTVA'>".price($totaltva)." &euro;</span></td>";

            $html .=  "<tr><th class='ui-widget-header ui-state-default black'>Montant total TTC : </th>";
            $totalttc=$total*(1.196);
            $html .=  "    <td class='ui-widget-content ui-state-default black' align=center><span id='totalTTC'>".price($totalttc)." &euro;</span></td>";
            $html .= "</table>";
            $this->table22col=true;
            $this->countEmp ++;

        }
        return ($html);
    }
    public function drawLoyerFin($displayMode)
    {
        $html = '';
        $this->countEmp ++;
        if($this->table22col)
        {
            $html .=  " <td width=300>";
            $html .=  "<table width=100% cellpadding=15 style='border-collapse: collapse;'>";
        } else {
            if ($this->countEmp == "1")
            {
                $html .=  " <tr><td width=300 style='padding:0; vertical-align: top;'>";
                $html .= "<table style='float: none; clear: left; width:400px; border-collapse: collapse;' cellpadding=15>";
            } else {
                $html .=  " <tr><td width=300>";
                $html .= "<table style='float: none; clear: left; margin-top: 30px;  width:400px; border-collapse: collapse;' cellpadding=15>";
            }

            $this->table22col=true;
        }
        $html .=  "<tr><th class='ui-widget-header ui-state-default'>Loyer HT</th>";
        $amountperperiod=0;
        $html .=  "    <td class='ui-widget-content ui-state-default' align=center><span id='payment'>".price($amountperperiod)." &euro;</span></td>";
        $html .=  "<tr><th class='ui-widget-header ui-state-default black'> TVA loyer : </th>";
        $amountperperiodtva=$amountperperiod*(0.196);
        $html .=  "    <td class='ui-widget-content ui-state-default black' align=center><span id='paymentTVA'>".price($amountperperiodtva)." &euro;</span></td>";

        $html .=  "<tr><th class='ui-widget-header ui-state-default black'>Loyer TTC </th>";
        $amountperperiodttc=$amountperperiod*(1.196);
        $html .=  "    <td class='ui-widget-content ui-state-default black' align=center><span id='paymentTTC'>".price($amountperperiodttc)." &euro;</span></td>";
        if($displayMode!=0)
        {
            $html .= "</table>";
        }

        return ($html);

    }
    public function drawTxFin($displayMode)
    {
        global $conf,$user;
        $html = "";
        $tauxFin = "";
        require_once(DOL_DOCUMENT_ROOT.'/Babel_GA/BabelGA.class.php');
        $tauxObj = new BabelGA($this->db);
        $tauxFinAdv = $tauxObj->fetch_taux_fin($this,'propal',$this->totAFin);
        if ($this->statut == 0)
        {

            if (($user->rights->GA->Taux->Admin ||$user->rights->GA->propale->modTaux) &&  $displayMode == 2 || ($displayMode == 1 && $conf->global->BABELGA_MEDIUM_TXBANKMODIFIE == 1 && $conf->global->BABELGA_MEDIUM_TXBANKAFFICHE))
            {
                $html .= '<tr>';
                $tauxFin = ($this->FinancementPropalDet[0]['tauxFinancement']."x"=="x"?$tauxFinAdv:floatval($this->FinancementPropalDet[0]['tauxFinancement']));
                $html .= '<td class="ui-state-default ui-widget-header"><label for="interest">Taux de financement (%)</label></td>';
                $html .= '<td class="ui-widget-content"><input type="text" name="interest" id="interest" size="20" value="'.$tauxFin.'" /><br/><small>Taux conseill&eacute;: <span id="interestAdv">'. round($tauxFinAdv*100)/100 . '</span>%</small></td>';
                $html .= '</tr>';
            } else if (($user->rights->GA->propale->voirTaux && $conf->global->BABELGA_MEDIUM_TXBANKAFFICHE == 1 && $displayMode == 1)
                      || ($user->rights->GA->propale->voirTaux && $conf->global->BABELGA_MEDIUM_TXBANKAFFICHE == 1 && $displayMode == 2 )){
                $html .= '<tr>';
                $tauxFin = ($this->FinancementPropalDet[0]['tauxFinancement']."x"=="x"?$tauxFinAdv:floatval($this->FinancementPropalDet[0]['tauxFinancement']));
                $html .= '<td class="ui-state-default ui-widget-header"><label for="interest">Taux de financement (%)</label></td>';
                $html .= '<td class="ui-widget-content"><span id="interestAdv">'. round($tauxFinAdv*100)/100 . '</span>%</td>';
                $html .= "<input type='hidden' name='interest' value='".$tauxFin."' id='interest'>";
                $html .= '</tr>';
            } else {
                $tauxFin = ($this->FinancementPropalDet[0]['tauxFinancement']."x"=="x"?$tauxFinAdv:floatval($this->FinancementPropalDet[0]['tauxFinancement']));
                $html .= "<input type='hidden' name='interest' value='".$tauxFin."' id='interest'>";
            }
        } else {
            if ((($conf->global->BABELGA_MEDIUM_TXBANKAFFICHE == 1 && $displayMode == 1) || $displayMode == 2 ) && ($user->rights->GA->propale->voirTaux ||$user->rights->GA->propale->modTaux || $user->rights->GA->Taux->Admin) ){
                $html .= '<tr>';
                $tauxFin = ($this->FinancementPropalDet[0]['tauxFinancement']."x"=="x"?$tauxFinAdv:floatval($this->FinancementPropalDet[0]['tauxFinancement']));
                $html .= '<td class="ui-state-default ui-widget-header"><label for="interest">Taux de financement (%)</label></td>';
                $html .= '<td class="ui-widget-content"><span id="interestAdv">'. round($tauxFinAdv*100)/100 . '</span>%</td>';
                $html .= "<input type='hidden' name='interest' value='".$tauxFin."' id='interest'>";
                $html .= '</tr>';
            } else {
                $tauxFin = ($this->FinancementPropalDet[0]['tauxFinancement']."x"=="x"?$tauxFinAdv:floatval($this->FinancementPropalDet[0]['tauxFinancement']));
                $html .= "<input type='hidden' name='interest' value='".$tauxFin."' id='interest'>";
            }        }
        return($html);

    }
    public function drawTEG($displayMode)
    {
        global $conf;



        if ($displayMode == 2)
        {
            print "</td><td width=300>";
            print "<table cellpadding=15 style='float:left; margin-left:15px;'>";
            print "<tr><th class='ui-widget-header ui-state-default'>T.E.G. proportionnel";
            print "    <td class='ui-widget-header ui-state-default '><span id='tegprop'></span>";
            print "<tr><th class='ui-widget-header ui-state-default black'>T.E.G.";
            print "    <td class='ui-widget-header ui-state-default black'><span id='teg'></span>";
            print "<tr><th class='ui-widget-header ui-state-default black'>T.E.G. actualis&eacute;";
            print "    <td class='ui-widget-header ui-state-default black'><span id='tegactu'></span>";
            print "</table>";
            $this->countEmp ++;
        } else if ($displayMode == 1)
        {
            if ($conf->global->BABELGA_MEDIUM_TEG ||$conf->global->BABELGA_MEDIUM_TEGACTU ||$conf->global->BABELGA_MEDIUM_TEGPROP)
            {
                $this->countEmp ++;
                if ($this->table22col)
                {
                        print "</td><td width=300 style='padding:0; vertical-align: top;'>";
                    if ($this->countEmp == 2){
                        print "<table cellpadding=15 style='float:left !important; margin-left:30px; width:300px; '>";
                    } else {
                        print "<table cellpadding=15 style='float:left !important;  margin-top:15px; margin-left:15px; width:300px;'>";
                    }
                } else {
                    print "</td><td width=300>";
                    $this->table22col=true;
                    print "<table cellpadding=15 style='float:none; clear: both; margin-left:15px;'>";
                }
                if($conf->global->BABELGA_MEDIUM_TEGPROP)
                {
                    print "<tr><th class='ui-widget-header ui-state-default'>T.E.G. proportionnel";
                    print "    <td class='ui-widget-header ui-state-default '><span id='tegprop'></span>";
                }
                if ($conf->global->BABELGA_MEDIUM_TEG)
                {
                    print "<tr><th class='ui-widget-header ui-state-default black'>T.E.G.";
                    print "    <td class='ui-widget-header ui-state-default black'><span id='teg'></span>";
                }
                if($conf->global->BABELGA_MEDIUM_TEGACTU)
                {
                    print "<tr><th class='ui-widget-header ui-state-default black'>T.E.G. actualis&eacute;";
                    print "    <td class='ui-widget-header ui-state-default black'><span id='tegactu'></span>";
                }

                print "</table>";

            }
        }
    }
    public function loadContrat()
    {
        require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
        $this->contrats = array();
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contrat WHERE is_financement = 1";
        dol_syslog("PropalGA::Fetch Error sql=$requete ".$this->db->error,LOG_DEBUG);
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql))
        {
            if (preg_match('/^p([0-9]*)/',$res->linkedTo,$arr))
            {
                if ($arr[1]==$this->id)
                {
                    $contrat = new ContratGA($this->db);
                    $contrat->fetch($res->rowid);
                    $this->contrats[]=$contrat;
                }
            } else if (preg_match('/^c([0-9]*)/',$res->linkedTo,$arr))
            {
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_commande = ".$arr[1];
                $sql1 = $this->db->query($requete);

                dol_syslog("PropalGA::Fetch Error sql=$requete ".$this->db->error,LOG_DEBUG);

                while ($res1 = $this->db->fetch_object($sql1))
                {
                    if ($res1->fk_propale==$this->id)
                    {
                        $contrat = new ContratGA($this->db);
                        $contrat->fetch($res->rowid);
                        $this->contrats[]=$contrat;
                    }
                }
            } else if (preg_match('/^f([0-9]*)/',$res->linkedTo))
            {
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_facture = ".$arr[1];

                dol_syslog("PropalGA::Fetch Error sql=$requete ".$this->db->error,LOG_DEBUG);

                $sql1 = $this->db->query($requete);
                while ($res1 = $this->db->fetch_object($sql1))
                {
                    if ($res1->fk_propal==$this->id)
                    {
                        $contrat = new ContratGA($this->db);
                        $contrat->fetch($res->rowid);
                        $this->contrats[]=$contrat;
                    }
                }
            }
        }


   }
}
?>