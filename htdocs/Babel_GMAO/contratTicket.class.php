<?php
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");


//TODO process et test
//Renouvellement
//Ajuster le nombre de tickets
//Ajouter un ticket existant à un contrat
//TODO bouton renouveller
//TODO renouvellement par anniversaire (ajuster) OK date aniv => faire boutons ajuster
//RT
//TODO count consom / restant
//RT (tabs)
//TODO lié contrat aux tickets
//Interventions (tabs)
//TODO affiche les interventios lié :> faire fonction affichaage + liste dans contrat.class.php
//RT + interventions
//TODO modif interventions pour les liés à un contrat / ticket
//Autre
//TODO affiche si maintenance hotline ...
//TODO Modif mini Fiche contrat
//TODO entrée contrat dans prepacommande


class contratTicket extends Synopsis_contrat{
    public $lineTkt=array();
    public $countTkt=array();
        public $durValid;
        public $DateDeb;
        public $DateDebFR;
        public $fk_prod;
        public $prod;
        public $reconductionAuto;
        public $qte = 0;
        public $hotline = 0;
        public $telemaintenance = 0;
        public $SLA="";
        public $isSAV=0;

    function contratTicket($db) {
        $this->db = $db ;
        $this->product = new Product($this->db);
        $this->societe = new Societe($this->db);
        $this->user_service = new User($this->db);
        $this->user_cloture = new User($this->db);
        $this->client_signataire = new User($this->db);
    }

//    public function fetch($id)
//    {
//
//        $ret = parent::fetch($id);
//        $requete = "SELECT durValid,
//                           unix_timestamp(DateDeb) as DateDebU,
//                           fk_prod,
//                           reconductionAuto,
//                           qte,
//                           hotline,
//                           telemaintenance,
//                           maintenance,
//                           SLA,unix_timestamp(dateAnniv) as dateAnnivU,
//                           isSAV
//                      FROM ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO
//                     WHERE contrat_refid =".$id;
//        $sql = $this->db->query($requete);
//        if ($this->db->num_rows($sql)>0)
//        {
//            $res = $this->db->fetch_object($sql);
//
//            $this->durValid = $res->durValid;
//            $this->DateDeb = $res->DateDebU;
//            $this->dateAnniv = $res->dateAnnivU;
//            $this->dateAnnivFR = date('d/m/Y',$res->dateAnnivU);
//            $this->DateDebFR = date('d/m/Y',$res->DateDebU);
//            $this->fk_prod = $res->fk_prod;
//            if ($this->fk_prod > 0)
//            {
//                require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
//                $prodTmp = new Product($this->db);
//                $prodTmp->fetch($this->fk_prod);
//                $this->prod = $prodTmp;
//            }
//            $this->reconductionAuto = $res->reconductionAuto;
//            $this->qte = $res->qte;
//            $this->hotline = $res->hotline;
//            $this->telemaintenance = $res->telemaintenance;
//            $this->SLA = $res->SLA;
//            $this->isSAV = $res->isSAV;
//            $requete = "SELECT unix_timestamp(date_add(DateDeb, INTERVAL ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.durValid month)) as dfinprev,
//                               unix_timestamp(date_add(DateDeb, INTERVAL ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.durValid month)) as dfin,
//                               unix_timestamp(DateDeb) as ddeb,
//                               unix_timestamp(DateDeb) as ddebprev,
//                               qty,
//                               rowid,
//                               subprice as pu
//                          FROM ".MAIN_DB_PREFIX."contratdet, ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO
//                         WHERE ".MAIN_DB_PREFIX."Synopsis_contratdet_GMAO.contratdet_refid = ".MAIN_DB_PREFIX."contratdet.rowid
//                           AND fk_contrat =".$id;
//            $sql = $this->db->query($requete);
//            while ($res=$this->db->fetch_object($sql))
//            {
//                $this->lineTkt[$res->rowid]=array(
//                    'qty'=>$res->qty,
//                    'pu'=>$res->pu,
//                    'dfinprev'=>$res->dfinprev,
//                    'dfin'=>$res->dfin,
//                    'ddeb'=>$res->ddeb,
//                    'ddebprev'=>$res->ddebprev);
//            }
//        }
//        return($ret);
//    }
    public function updateProp()
    {
        $this->countTicket();
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_contrat_GMAO SET qte=".$this->countTotalTkt." WHERE contrat_refid =  ".$this->id;
        $sql = $this->db->query($requete);
    }
    public function countTicket()
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat =".$this->id;
        $sql = $this->db->query($requete);
        $this->countTotalTkt = 0;
        while ($res = $this->db->fetch_object($sql))
        {
            $qty = $res->qty;
            if ($res->fk_product > 0)
            {
                $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$res->fk_product;
                $sql1 = $this->db->query($requete);
                $res1 = $this->db->fetch_object($sql1);
                $qty = $res->qty * $res1->qte;
            }
            $this->countTotalTkt += $qty;
            $this->countTkt[$res->rowid]=array('total'=>$qty , 'restant'=> 10,'consomme' => 10);
        }
        return($this->countTkt);
    }
    public function sumTicket()
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat =".$this->id;
        $sql = $this->db->query($requete);
        $total = 0;
        $tva=0;
        $ttc = 0;
        while ($res = $this->db->fetch_object($sql))
        {
            $this->sumTkt[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);
            $total += $res->total_ht ;
            $tva += $res->total_tva;
            $ttc += $res->total_ttc;
        }
        $this->totalHT = $total;
        $this->totalTVA = $tva;
        $this->totalTTC = $ttc;
        return($this->sumTkt);
    }

    public function getExtraHeadTab($head)
    {
        global $langs;
        $h = count($head);
        $head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$this->id;
        $head[$h][1] = $langs->trans("Tickets");
        $head[$h][2] = 'Tickets';
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
                                            <tr><td>
                                                <td>Description
                                                <td align=center style="width: 150px;">Qt&eacute; initiale
                                                <td align=center style="width: 150px;">PU HT
                                                <td align=center style="width: 150px;">Total HT
                                                <td align=center style="width: 150px;">Restants
                                                <td align=center style="width: 150px;">Consomm&eacute;s
                                                <td align=center style="width: 150px;">D&eacute;but de validit&eacute;
                                                <td align=center style="width: 150px;">Fin de validit&eacute;
                                                <td align=center style="width: 50px;">Action</tr></table></li>';
            foreach($this->lignes as $key => $val)
            {
                $html .= $this->display1line($val);
            }
            //TODO Display Tot
            $html .= "</ul>";
            return ($html);

    }
    public function displayAddLine($mysoc,$objp)
    {
        $html = '<right>';
        $html .= "     <span class='butAction' id='AddLineBut' style='margin: 25px;'>Ajouter des tickets</span>";
        $html .= '</right>';
        return($html);
    }
    public function display1Line($val)
    {

        global $user, $conf, $lang;
        $html = "<li id='".$val->id."' class='ui-state-default'>";
        $html .= "<table  width=100%><tr class='ui-widget-content'><td width=15 rowspan=1>";
        if ( ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }

        $qteInit = $val->qty;
        $Description='Tickets divers';
        if ($val->fk_product > 0)
        {
            $tmpProd = new Product($this->db);
            $tmpProd->fetch($val->fk_product);
            $qteInit .= ' x '.$tmpProd->qte." = ".$tmpProd->qte * $val->qty;
            $Description = $tmpProd->libelle . ' ('.$tmpProd->ref.')';
        }
        $html .= '<td>'.$Description;
        $arr=$this->countTicket();
        $arr1=$this->sumTicket();
//            $this->sumTkt[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);

        $html .= '<td align=center style="width: 150px;">'.$qteInit;
        $html .= '<td align=center style="width: 150px;">'.price($arr1[$val->id]['pu_ht']);
        $html .= '<td align=center style="width: 150px;">'.price($arr1[$val->id]['total']);
        $html .= '<td align=center style="width: 150px;">'.$arr[$val->id]['consomme'];
        $html .= '<td align=center style="width: 150px;">'.$arr[$val->id]['restant'];
        if (($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $warnDelay=10;
            $spanExtra='';
            $spanExtraEnd='';
            if ($val->statut < 5)
            {
                if ($this->lineTkt[$val->id]['dfin'] < time())
                {
                    $spanExtra = "<span class='ui-state-error' style='width:95px;display:block; border: 0px;'><span style='float:left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraEnd='</span>';
                } else if ($this->lineTkt[$val->id]['dfin'] < time() + $warnDelay * 24 * 3600)
                {
                    $spanExtra = "<span class='ui-state-highlight' style='width:95px;display:block;  border: 0px;'><span style='float:left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraEnd='</span>';
                }
            }
            $html .= '<td align=center style="width: 150px;" >'.($this->lineTkt[$val->id]['ddebprev']>0?date('d/m/Y',$this->lineTkt[$val->id]['ddebprev']):"-");
            $html .= '<td align=center style="width: 150px;" >'.$spanExtra." ".($this->lineTkt[$val->id]['dfinprev']>0?date('d/m/Y',$this->lineTkt[$val->id]['dfinprev']):"-").$spanExtraEnd;
        } else {
            $warnDelay=10;
            $spanExtra='';
            if ($val->statut < 5)
            {
                if ($this->lineTkt[$val->id]['dfin'] < time())
                {
                    $spanExtra = "<span class='ui-state-error' style='width:95px;display:block; border: 0px;'><span style='float:left;' class='ui-icon ui-icon-alert'></span>";
                } else if ($this->lineTkt[$val->id]['dfin'] < time() + $warnDelay * 24 * 3600)
                {
                    $spanExtra = "<span class='ui-state-highlight' style='width:95px;display:block; border: 0px;'><span style='float:left;' class='ui-icon ui-icon-alert'></span>";
                }
            }
                $html .= '<td align=center style="width: 150px;" >'.($this->lineTkt[$val->id]['ddeb']>0?date('d/m/Y',$this->lineTkt[$val->id]['ddeb']):"-");
            $html .= '<td align=center style="width: 150px;" >'.$spanExtra." ".($this->lineTkt[$val->id]['dfin']>0?date('d/m/Y',$this->lineTkt[$val->id]['dfin']):"-").$spanExtraEnd;
        }
        $html .= $this->displayStatusIco($val);
//        $html .= '<td align=center style="width: 50px;">'.img_edit()."&nbsp;".img_delete().'</tr>';


        $html .= "</table>";
        $html .= "</li>";
        return($html);
    }
    public function displayExtraStyle()
    {
        $this->countTicket();
        $this->sumTicket();
        print "<br/>";
        print "<div class='titre'>R&eacute;sum&eacute;</div>";
        print "<table cellpadding=15 width=600>";
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
        print "    <th width=150 class='ui-state-default ui-widget-header'>Tickets<td width=150 class='ui-widget-content'>".$total;
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TVA<td width=150 class='ui-widget-content'>".price($this->totalTVA)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Restant<td width=150 class='ui-widget-content'>".$restant;
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TTC<td width=150 class='ui-widget-content'>".price($this->totalTTC)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Consomm&eacute;<td width=150 class='ui-widget-content'>".$consome;


        print "</table>";


        $html .= "<style>";
        $html .= ".ui-placeHolder { background-color: #eee05d; opacity: 0.9; border:1px Dashed #999; min-height: 2em;}
               #ui-datepicker-div { z-index: 2000;  }
               #sortable li span img{ cursor: pointer; } ";
        if (($contrat->statut == 0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && $user->rights->contrat->creer)
        {
            $html .= "  #sortable li { cursor: move; }";
        }
        $html .= ".ui-dialog-buttonpane button { padding: 5px 10px; }";
        $html .= "</style>";

        $html .= <<<EOF
<script>
EOF;
        $html .= "var DOL_URL_ROOT='".DOL_URL_ROOT."';";
        $html .= "var fk_soc='".$this->socid."';";
        $html .= "var DOL_DOCUMENT_ROOT='".DOL_DOCUMENT_ROOT."';";
        $html .= <<<EOF
jQuery(document).ready(function(){
    jQuery('#addfk_prod').change(function(){
        var val = jQuery(this).find(':selected').val();
        if (val > 0)
        {
            jQuery.ajax({
                url: DOL_URL_ROOT+'/Babel_GMAO/ajax/getProdContratProd-xml_response.php',
                datatype: "xml",
                data: "prod="+val+"&fk_soc="+fk_soc,
                type: "POST",
                cache: false,
                success: function(msg){
                    var qte = jQuery(msg).find('qte').text();
                    var tva_tx = jQuery(msg).find('tva').text().replace(/0*$/,'');
                    var durValid = jQuery(msg).find('durValid').text();
                    var price = jQuery(msg).find('price').text();
                    jQuery('#addQtyTxt').find('#txtQty').text(qte);
                    jQuery('#addQtyTxt').css('display','inline');
                    jQuery('#addpu_ht').val(price);
                    jQuery('#addDur').val(durValid);
                    jQuery('#addLinetva_tx').selectmenu("value",2);
//TODO Si droit de modifier le prix
  //                jQuery('#addpu_ht').attr('disabled','disabled');
                    jQuery('#addDur').attr('disabled','disabled');
                    jQuery('#addLinetva_tx').selectmenu("disable");
                }
            });
        } else {
//TODO Si droit de modifier le prix
//            jQuery('#addpu_ht').attr('disabled','');
            jQuery('#addDur').attr('disabled','');
            jQuery('#addLinetva_tx').selectmenu("enable");
            jQuery('#addQtyTxt').css('display','none');
        }
    });
    jQuery('#modfk_prod').change(function(){
        var val = jQuery(this).find(':selected').val();
        if (val > 0)
        {
            jQuery.ajax({
                url: DOL_URL_ROOT+'/Babel_GMAO/ajax/getProdContratProd-xml_response.php',
                datatype: "xml",
                data: "prod="+val,
                type: "POST",
                cache: true,
                success: function(msg){
                    var qte = jQuery(msg).find('qte').text();
                    var qte = jQuery(msg).find('qte').text();
                    var tva_tx = jQuery(msg).find('tva').text().replace(/0*$/,'');
                    var durValid = jQuery(msg).find('durValid').text();
                    var price = jQuery(msg).find('price').text();
                    jQuery('#modQtyTxt').find('#txtQty').text(qte);
                    jQuery('#modQtyTxt').css('display','inline');
                    jQuery('#modpu_ht').val(price);
                    jQuery('#modDur').val(durValid);
                    jQuery('#modLinetva_tx').selectmenu("value",2);
//TODO Si droit de modifier le prix
  //                jQuery('#modpu_ht').attr('disabled','disabled');
                    jQuery('#modDur').attr('disabled','disabled');
                    jQuery('#modLinetva_tx').selectmenu("disable");
                }
            });
        }else {
            jQuery('#modDur').attr('disabled','');
            jQuery('#modLinetva_tx').selectmenu("enable");
            jQuery('#modQtyTxt').css('display','none');
        }
    });
});
</script>
EOF;

        return($html);

    }
    public function displayExtraInfoCartouche()
    {
        $html = "";
        global $user,$langs;
        if ($this->SLA ."x" != 'x')
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>SLA<td class='ui-widget-content'>".$this->SLA;
        }
        $html .= "<tr><th class='ui-widget-header ui-state-default'>Date de renouvellement";
        if ($_REQUEST["action"] != "setAnniv" && $user->rights->contrat->creer)
            $html .= '<span style="float:right;"><a href="'.$_SERVER["PHP_SELF"].'?action=setAnniv&amp;id='.$this->id.'">'.img_edit($langs->trans("SetRenew")).'</a></span>';
        if ($_REQUEST['action'] == "setAnniv")
        {
            $checked='valid';
            if ($this->dateAnnivFR == $this->societe->dateAnnivFR)
            {
                $checked = 'soc';
            } else if ($this->dateAnniv > 0)
            {
                $checked = 'custom';
            }
            $html .= "    <td class='ui-widget-content'>";
            $html .= "    <form action='".$_SERVER['PHP_SELF']."?id=".$this->id."' method='POST'>";
            $html .= '     <input type="hidden" name="action" value="setDateAnniv"></input>';
            $html .= "     <table class='noborder'><tr><td> ";
            $html .= "     <table class='noborder'><tr><td> ";
            $html .= "      <input ".($checked=='soc'?'checked':'')." name='selectAnniv' type='radio' value='".$this->societe->dateAnnivFR."'><td colspan=2>Date du tiers :".$this->societe->dateAnnivFR;
            $html .= "     <tr><td> ";
            $html .= "      <input ".($checked=='valid'?'checked':'')." name='selectAnniv' type='radio' value='-1'><td colspan=2>Date de validation (automatique)";
            $html .= "     <tr><td> ";
            $html .= "      <input ".($checked=='custom'?'checked':'')." name='selectAnniv' type='radio' value='custom'><td>Autre:<td><input id='dateAnniv' value='".($checked=='custom'?$this->dateAnnivFR:'')."' name='dateAnniv' class='datepicker'>";
            $html .= "    </table>";
            $html .= "     <td> ";
            $html .= "<button class='butAction'>OK</button></form>";
            $html .= "    </table>";
        } else {
            $html .= "    <td class='ui-widget-content'>".($this->dateAnniv>0?$this->dateAnnivFR:"Date de validation (automatique)");
        }
        $html .= <<<EOF
<script>
            jQuery(document).ready(function(){
                jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);


                  jQuery("#dateAnniv").datepicker({dateFormat: 'dd/mm/yy',
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

            });
</script>
EOF;
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
    public function displayDialog($type='add',$mysoc,$objp)
    {
        global $conf, $form, $db;
        $html .=  '<div id="'.$type.'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='".$type."Form' method='POST' onsubmit='return(false);'>";

//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .=  '<table class="ui-state-default" style="width: 600px; border: 0px Solid; border-collapse: collapse; margin-top: 5px; " cellpadding=10>';
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE qte > 0 AND fk_product_type = 2";
        $sql = $this->db->query($requete);
        $optProdContrat = "<OPTION value='-1'>S&eacute;lectionner-></OPTION>";
        while ($res = $this->db->fetch_object($sql))
        {
            $optProdContrat .= "<OPTION value='".$res->rowid."'>".$res->label." (".$res->ref.")</OPTION>";
        }
        $html .=  "<tr  class='ui-state-default'>";
        $html .= '<td align="left">Produit<td width=160><SELECT name="'.$type.'fk_prod" id="'.$type.'fk_prod">';
        $html .= $optProdContrat;
        $html .= "</SELECT>";

        $html .=  '<td align=right>Qt&eacute;</td><td align=left colspan=1>';
        $html .=  "<input id='".$type."Qty' style='text-align:center;' value=1 name='".$type."Qty' style='width: 20px;  text-align: center;'/>";
        $html .= "<br/><span style='display: none;' id='".$type."QtyTxt'>Qt&eacute; par contrat: <span id='txtQty'></span></span>";
        $html .=  '</td>';

        $html .=  "<tr  class='ui-state-default'>";
        $html .=  '<td>Date de d&eacute;but pr&eacute;vue</td>';
        $html .=  '<td width=160><input value="'. date('d').'/'.date('m').'/'.date('Y') .'" style="text-align: center;" type="text" name="dateDeb'.$type.'" id="dateDeb'.$type.'"/>'.img_picto('calendrier','calendar.png','style="float: left;margin-right: 3px; margin-top: 1px;"').'</td>';
        $html .=  '<td align=right>Dur&eacute;e de validit&eacute;<br/>(en mois)</td><td align=left colspan=1>';
        $html .=  "<input id='".$type."Dur' value='".$this->durVal."' name='".$type."Dur' style='width: 20px;  text-align: center;'/>";
        $html .=  '</td>';

        $html .=  '</tr>';

        $html .=  '<tr style=" ">';
        $html .= '<td align="left">PU HT (&euro;)<td><INPUT  style="margin-left: 20px; text-align:center;" name="'.$type.'pu_ht" id="'.$type.'pu_ht" value="0">';
        $html .=  '<td align=right>TVA<td align=left width=180>';
        $html .= $form->load_tva($type."Linetva_tx","19.6",$mysoc,$this->societe,"",0,false);




        $html .=  "</table>";
        $html .=  '</form>';
        $html .=  '</div>';
        return ($html);

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