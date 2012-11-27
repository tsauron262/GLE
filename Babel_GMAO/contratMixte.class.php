<?php
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

//Objectif: contrat enveloppe :> 1 ligne par type de contrat

//TODO recond auto
//TODO ticket restant
//TODO fiche tickets

class contratMixte extends contrat {

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
    public function contratMixte($db) {
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

            $requete = "SELECT unix_timestamp(date_add(date_add(Babel_GMAO_contratdet_prop.DateDeb, INTERVAL Babel_GMAO_contratdet_prop.durValid month), INTERVAL ifnull(llx_product.durSav,0) MONTH)) as dfinprev,
                               unix_timestamp(date_add(date_add(Babel_GMAO_contratdet_prop.DateDeb, INTERVAL Babel_GMAO_contratdet_prop.durValid month), INTERVAL ifnull(llx_product.durSav,0) MONTH)) as dfin,
                               unix_timestamp(Babel_GMAO_contratdet_prop.DateDeb) as ddeb,
                               unix_timestamp(Babel_GMAO_contratdet_prop.DateDeb) as ddebprev,
                               ".MAIN_DB_PREFIX."contratdet.qty,
                               ".MAIN_DB_PREFIX."contratdet.rowid,
                               ".MAIN_DB_PREFIX."contratdet.subprice as pu,
                               Babel_GMAO_contratdet_prop.durValid as durVal,
                               Babel_GMAO_contratdet_prop.fk_contrat_prod,
                               Babel_product_serial_cont.serial_number
                          FROM Babel_GMAO_contratdet_prop, ".MAIN_DB_PREFIX."contratdet
                     LEFT JOIN llx_product ON llx_product.rowid = ".MAIN_DB_PREFIX."contratdet.fk_product
                     LEFT JOIN Babel_product_serial_cont ON Babel_product_serial_cont.element_id = ".MAIN_DB_PREFIX."contratdet.rowid AND Babel_product_serial_cont.element_type LIKE 'contrat%'
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
    public function display1LineSAV($val)
    {

        global $user, $conf, $lang;
        $html = "<li style='font-weight: normal!important; border:0px Solid !important;' id='".$val->id."' class='ui-state-default'>";
        $html .= "<table ><tr class='ui-widget-content'><td width=16 rowspan=2  class='ui-widget-content'>";
        if ( ($this->statut == 0 || $val->statut == 0) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }
            //var_dump($val->product);
        if ($val->product)
        {
            $contratProd = "-";
            $price = price($val->total_ht,2);
            if ($this->lineTkt[$val->id]['fk_contrat_prod'])
            {
                $tmpcontratProd = new Product($this->db);
                $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
                $contratProd = $tmpcontratProd->getNomUrl(1);
            }
            $spanExtra = "";
            $spanExtraFin = "";
            if ($val->statut != 5)
            {
                if ( time() > $this->lineTkt[$val->id]['dfin'])
                {
                    $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraFin = "</span>";
                } else if (time() > $this->lineTkt[$val->id]['dfin'] - intval($conf->global->MAIN_DELAY_RUNNING_SERVICES) * 24 * 3600)
                {
                    $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraFin = "</span>";
                }
            }
            $li = new ContratLigne($this->db);
            $li->fetch($val->id); //Hum $li = $val ??
            $html .= "
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style=' white-space: nowrap; width: 100px;padding-top: 0.5em;' rowspan=2>SAV";
            $extra="";
            $extraClass='';
            if ($val->statut <5 &&  $val->statut > 0)
            {
                if (preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$val->date_fin_prevue) - (3600*24*intval($conf->global->MAIN_DELAY_RUNNING_SERVICES)) > time()){
                    $extra="<span class='ui-icon-alert'></span>";
                    $extraClass=' ui-state-error ';
                }
            }
            $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au <span class='".$extraClass."'>".$extra.$val->date_fin_prevue."</span>";
            $requete = "SELECT durRenew,
                               unix_timestamp(date_renouvellement) as date_renouvellement
                          FROM Babel_contrat_renouvellement
                         WHERE contratdet_refid = ".$val->id
                   ." ORDER BY date_renouvellement DESC
                         LIMIT 1 ";
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            if ($res->date_renouvellement > 0)
            {
                $html .= '<br/>Renouv. le '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
            }
            if ($val->statut>4){
                $html .= '<br/>Cl&ocirc;turer le '.$val->date_fin_reel;
            }
            $html .= "
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>".$contratProd. "
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>SAV Init.
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>" . ($val->product->durSav>0?$val->product->durSav:0)." mois
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Dur&eacute;e Extension
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>" . $this->lineTkt[$val->id]['durVal']." mois
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Prix
                   <td nowrap=1  class='ui-widget-content'  valign=top align=right style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".price($price)." &euro;&nbsp;&nbsp;
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$li->getLibStatut(5). "&nbsp;
                    ";
            if ($this->statut!=2)
            {
                $html .= $this->displayStatusIco($val);
            }
            if($val->product)
            {
                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                               <td align=left class='ui-widget-content' colspan=4>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle ."<br/>".$val->desc."</font>
                               <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                               <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
                 $html .= "</tr>";
            } else {
                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                               <td align=left class='ui-widget-content' colspan=4>-<br/><br/>
                               <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                               <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
                 $html .= "</tr>";
            }
        } else if ($this->lineTkt[$val->id]['fk_contrat_prod'] > 0){
            $contratProd = "-";
            $price = price($val->total_ht,2);
            if ($this->lineTkt[$val->id]['fk_contrat_prod'])
            {
                $tmpcontratProd = new Product($this->db);
                $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
                $contratProd = $tmpcontratProd->getNomUrl(1);
            }
            $spanExtra = "";
            $spanExtraFin = "";
            if ($val->statut != 5)
            {
                if ( time() > $this->lineTkt[$val->id]['dfin'])
                {
                    $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraFin = "</span>";
                } else if (time() > $this->lineTkt[$val->id]['dfin'] - intval($conf->global->MAIN_DELAY_RUNNING_SERVICES) * 24 * 3600)
                {
                    $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                    $spanExtraFin = "</span>";
                }
            }
            $li = new ContratLigne($this->db);
            $li->fetch($val->id); //Hum $li = $val ??
            $html .= "
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style=' white-space: nowrap; width: 100px;padding-top: 0.5em;' rowspan=2>SAV";
            $extra="";
            $extraClass='';
            if ($val->statut <5 &&  $val->statut > 0)
            {
                if (preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$val->date_fin_prevue) - (3600*24*intval($conf->global->MAIN_DELAY_RUNNING_SERVICES)) > time()){
                    $extra="<span class='ui-icon-alert'></span>";
                    $extraClass=' ui-state-error ';
                }
            }
            $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au <span class='".$extraClass."'>".$extra.$val->date_fin_prevue."</span>";
            $requete = "SELECT durRenew,
                               unix_timestamp(date_renouvellement) as date_renouvellement
                          FROM Babel_contrat_renouvellement
                         WHERE contratdet_refid = ".$val->id
                   ." ORDER BY date_renouvellement DESC
                         LIMIT 1 ";
            $sql = $this->db->query($requete);
            $res = $this->db->fetch_object($sql);
            if ($res->date_renouvellement > 0)
            {
                $html .= '<br/>Renouv. le '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
            }
            if ($val->statut>4){
                $html .= '<br/>Cl&ocirc;turer le '.$val->date_fin_reel;
            }
            $html .= "
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>".$contratProd. "
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>SAV Init.
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>" . ($val->product->durSav>0?$val->product->durSav:0)." mois
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Dur&eacute;e Extension
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>" . $this->lineTkt[$val->id]['durVal']." mois
                   <th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Prix
                   <td nowrap=1  class='ui-widget-content'  valign=top align=right style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".price($price)." &euro;&nbsp;&nbsp;
                   <td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>".$li->getLibStatut(5). "&nbsp;
                    ";
            if ($this->statut!=2)
            {
                $html .= $this->displayStatusIco($val);
            }
            if($val->product)
            {
                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                               <td align=left class='ui-widget-content' colspan=4>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle ."<br/>".$val->desc."</font>
                               <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                               <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
                 $html .= "</tr>";
            } else {
                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                               <td align=left class='ui-widget-content' colspan=4>-<br/><br/>
                               <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                               <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
                 $html .= "</tr>";
            }

        }
         $html .= "</table>";
        $html .= "</li>";
        return($html);
    }
    public function display1LineTKT($val)
    {
        global $user, $conf, $lang;
        $html = "<li style='font-weight: normal!important; border:0px Solid !important;' id='".$val->id."' class='ui-state-default'>";
        $html .= "<table ><tr class='ui-widget-content pointerCursor'><td class='moveCursor' width=16 rowspan=2>";
        if ( ($this->statut == 0  || $val->statut == 0 ) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }

        $qteInit = $val->qty;
        $Description='Tickets divers';
        if ($val->fk_product > 0)
        {
            $tmpProd = new Product($this->db);
            $tmpProd->fetch($val->fk_product);
        }
        $price = price($val->total_ht * $val->qty,2);
        $contratProd=false;
        if ($this->lineTkt[$val->id]['fk_contrat_prod'])
        {
            $tmpcontratProd = new Product($this->db);
            $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
            $contratProd = $tmpcontratProd->getNomUrl(1);

            $qteInit .= ' x '.$tmpcontratProd->qte." = ".$tmpcontratProd->qte * $val->qty;
            if ($tmpcontratProd->qte == -1) $qteInit = 'Illimit&eacute;';
            $Description = $tmpcontratProd->libelle . ' ('.$tmpcontratProd->ref.')';
        }

        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;' rowspan=2>Tickets";
            $extra="";
            $extraClass='';
            if ($val->statut <5 &&  $val->statut > 0)
            {
                if (preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$val->date_fin_prevue) - (3600*24*intval($conf->global->MAIN_DELAY_RUNNING_SERVICES)) > time()){
                    $extra="<span class='ui-icon-alert'></span>";
                    $extraClass=' ui-state-error ';
                }
            }
            $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au <span class='".$extraClass."'>".$extra.$val->date_fin_prevue."</span>";
        $requete = "SELECT durRenew,
                           unix_timestamp(date_renouvellement) as date_renouvellement
                      FROM Babel_contrat_renouvellement
                     WHERE contratdet_refid = ".$val->id
               ." ORDER BY date_renouvellement DESC
                     LIMIT 1 ";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->date_renouvellement > 0)
        {
            $html .= '<br/>Renouv. '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
        }
        $html .= "<td nowrap=1  class='ui-widget-content'  valign=top align=center style='white-space: nowrap; width: 150px;padding-top: 0.5em;'>".($contratProd?$contratProd:"");


        $arr=$this->countTicket();
        $arr1=$this->sumTicket();
//            $this->sumTkt[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);

        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important; white-space: nowrap; width: 100px;padding-top: 0.5em;'>Qt&eacute; init.";
        $html .= '<td align=center class="ui-widget-content" style="width: 150px;">'.$qteInit;
//TODO qte tkt restant / consomme
        if ($qteInit == 'Illimit&eacute;'){
            $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Consomm&eacute;s";
            $html .= '<td align=center class="ui-widget-content" style="width: 150px;">'.$arr[$val->id]['consomme'];
        } else {
            $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Restants";
            $html .= '<td align=center class="ui-widget-content" style="width: 150px;">'.$arr[$val->id]['restant'];
        }
        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Prix";
        $html .= '<td align=right class="ui-widget-content" style="width: 100px;">'.price($arr1[$val->id]['total'])." &euro;";
        $html .= " <td nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>". $val->getLibStatut(5);
        $html .= $this->displayStatusIco($val);
//        $html .= '<td align=center style="width: 50px;">'.img_edit()."&nbsp;".img_delete().'</tr>';
        if($val->product)
        {
            $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                           <td align=left class='ui-widget-content' colspan=4>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle ."<br/>".$val->desc."</font>
                           <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                           <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
             $html .= "</tr>";
        } else {
            $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                           <td align=left class='ui-widget-content' colspan=4>-<br/><br/>
                           <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                           <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
             $html .= "</tr>";
        }

        $html .= "</table>";
        $html .= "</li>";
        return($html);
    }
    public function display1LineMNT($val)
    {
        global $user, $conf, $lang;
        $html = "<li style='font-weight: normal!important; border:0px Solid !important;' id='".$val->id."' class='ui-state-default'>";
        $html .= "<table ><tr class='ui-widget-content pointerCursor'><td class='moveCursor' width=16 rowspan=2>";
        if ( ($this->statut == 0 || $val->statut == 0) && $user->rights->contrat->creer)
        {
            $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
        }

        $qteInit = $val->GMAO_Mixte['nbVisiteAn'];
        $tickets = $val->GMAO_Mixte['tickets'];

        $Description='';
        if ($val->fk_product > 0)
        {
            $tmpProd = new Product($this->db);
            $tmpProd->fetch($val->fk_product);
        }
        $price = $val->total_ht;
        $contratProd=false;
        if ($this->lineTkt[$val->id]['fk_contrat_prod'])
        {
            $tmpcontratProd = new Product($this->db);
            $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
            $contratProd = $tmpcontratProd->getNomUrl(1);
            //$price = price($tmpcontratProd->price,2);

            //$qteInit = $val->qty;

            $Description = $tmpcontratProd->libelle . ' ('.$tmpcontratProd->ref.')';
        }

        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;' rowspan=2>Maintenance";
        $extra="";
        $extraClass='';
        if ($val->statut <5 &&  $val->statut > 0)
        {
            if (intval(strtotime(preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$val->date_fin_prevue))) < time()){
                $extra="<span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                $extraClass=' ui-state-error ';
            } else
            if (intval(strtotime(preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$val->date_fin_prevue))) - (3600*24*intval($conf->global->MAIN_DELAY_RUNNING_SERVICES)) < time()){
                $extra="<span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                $extraClass=' ui-state-highlight ';
            }
        }
        $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au <div style='width: 90px;' class='".$extraClass."'>".$extra.$val->date_fin_prevue."</div>";

        $requete = "SELECT durRenew,
                           unix_timestamp(date_renouvellement) as date_renouvellement
                      FROM Babel_contrat_renouvellement
                     WHERE contratdet_refid = ".$val->id
               ." ORDER BY date_renouvellement DESC
                     LIMIT 1 ";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        if ($res->date_renouvellement > 0)
        {
            $html .= '<br/>Renouv. '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
        }

        $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 150px;'>".($contratProd?$contratProd:"");


//            $this->sumTkt[$res->rowid]=array('total'=>$res->total_ht , 'tva'=> $res->total_tva,'ttc' => $res->total_ttc, 'qty' => $res->qty, 'pu_ht' => $res->price_ht);
        if ($qteInit == 0)
        {
            $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;'>Pas d'interv.<br/>sur site";
            $html .= '<td align=center class="ui-widget-content" style="width: 150px;">';
            if($tickets .'x' != "x")
            {
                $arrIntervRestant = $this->intervRestant($val);
                $consomme=$arrIntervRestant['consomme'];
                $restant = $arrIntervRestant['restant'];
                //Quantité restant soit total - (interv type telemaintenance ou hotline ou ticket)
                if ($tickets == -1) $html .= 'Tickets&nbsp;:&nbsp;Illimit&eacute; <br/>('.$consomme.' consomm&eacute;es)';
                if ($tickets > 0){
                    global $conf;
                    if ($restant < 0)
                    {
                         $html .= 'Tickets&nbsp;:&nbsp;'.$tickets. "<br/><table class=noborder style='padding:0'><tr><td align=right><span class='ui-state-error' style='border:0;'><span class='ui-icon ui-icon-alert'></span></span><td class'ui-state-error'><b>(".$restant." restants)</b></table>";
                    } else if ($restant < $conf->global->GMAO_TKT_RESTANT_WARNING){
                         $html .= 'Tickets&nbsp;:&nbsp;'.$tickets. "<br/><table class=noborder style='padding:0'><tr><td align=right><span class='ui-state-highlight' style='border:0;'><span class='ui-icon ui-icon-alert'></span></span><td class'ui-state-highlight'><b>(".$restant." restants)</b></table>";
                    } else {
                         $html .= 'Tickets&nbsp;:&nbsp;'.$tickets. "<br/>(".$restant." restants)";
                    }
                }
                if ($tickets == 0) $html .= 'Pas de tickets';
            }

        } else {
            $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;'>Nb visite / an";
            $html .= '<td align=center class="ui-widget-content" style="width: 150px;">'.$qteInit;
        }
        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;'>Options";
        $tt = array();
        $maint = false;
        $telemnt = false;
        $hotline = false;
        //require_once('Var_Dump.php');
        //var_dump::display($val->GMAO_Mixte);
        if ($val->GMAO_Mixte['maintenance'] == 1 && $qteInit > 0)
        {
            $tt[] = "<b>Maintenance&nbsp;:&nbsp;</b>OUI";
            $maint = true;
        } else {
            $tt[] = "<b>Maintenance&nbsp;:&nbsp;</b>NON";
        }
        if ($val->GMAO_Mixte['telemaintenance'] == 1)
        {
            $tt[] = "<b>T&eacute;l&eacute;maintenance&nbsp;:&nbsp;</b>OUI";
            $telemnt = true;
        } else {
            $tt[] = "<b>T&eacute;l&eacute;maintenance&nbsp;:&nbsp;</b>NON";
        }
        if ($val->GMAO_Mixte['hotline'] == 1)
        {
            $tt[] = "<b>Hotline&nbsp;:&nbsp;</b>OUI";
            $hotline = true;
        } else {
            $tt[] = "<b>Hotline&nbsp;:&nbsp;</b>NON";
        }
        if($tickets .'x' != "x")
        {
            $arrIntervRestant = $this->intervRestant($val);
            $consomme=$arrIntervRestant['consomme'];
            $restant = $arrIntervRestant['restant'];

            if ($tickets == -1) $tt[] = '<b>Tickets&nbsp;:&nbsp;</b>Illimit&eacute; <br/>('.$consomme.' consomm&eacute;es)';
            if ($tickets > 0) $tt[] = '<b>Tickets&nbsp;:&nbsp;</b>'.$tickets." (".$restant." restants)";
        }
        $html .= '<td align=center valign=middle class="ui-widget-content" style="width: 150px;"><a  id="tt'.$val->id.'" href="#" title="'.join('<br/>',$tt).'">H: '.($hotline?'OUI':'NON').' M: '.($maint?'OUI':'NON').' T: '.($telemnt?'OUI':'NON').'</a>';
        $html .= '<script>';
        $html .= 'jQuery(document).ready(function(){';
        $html .= '    jQuery("a#tt'.$val->id.'").tooltip({';
        $html .= <<<EOF
        track: true,
        delay: 0,
        showURL: false,
        showBody: " - ",
        fade: 250
    });
});
</script>
<style>
#tooltip { position: absolute; background-color: #FEFFC1; border:1px Solid #CCCCCC; padding: 0px 19px; font-size: smaller;  }
</style>
EOF;
        $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=middle align=center style='font-weight:normal!important;white-space: nowrap; width: 100px;'>Prix";
        $html .= '<td align=right valign=middle class="ui-widget-content" style="width: 100px;">'.price($price,2)."&euro;&nbsp;&nbsp;";
        $html .= " <td nowrap=1  valign=middle align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>". $val->getLibStatut(5);
        $html .= $this->displayStatusIco($val);
//        $html .= '<td align=center style="width: 50px;">'.img_edit()."&nbsp;".img_delete().'</tr>';
        if($val->product)
        {
            $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                           <td align=left class='ui-widget-content' colspan=4>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle ."<br/>".$val->desc."</font>
                           <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                           <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
             $html .= "</tr>";
        } else {
            $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Produit concern&eacute;
                           <td align=left class='ui-widget-content' colspan=4>-<br/><br/>
                           <th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie
                           <td  style='font-weight: 0!important;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number'];
             $html .= "</tr>";
        }
        $html .= "</table>";
        $html .= "</li>";
        return($html);
    }
    public function intervRestant($val){
        $tickets = $val->GMAO_Mixte['tickets'];

        $qteTempsPerDuree  = $val->GMAO_Mixte['qteTempsPerDuree'];
        $qteTktPerDuree   = $val->GMAO_Mixte['qteTktPerDuree'];


        $requete = "SELECT fd.rowid, fd.duree
                      FROM ".MAIN_DB_PREFIX."fichinter as f,
                           ".MAIN_DB_PREFIX."fichinterdet as fd,
                           llx_Synopsis_fichinter_c_typeInterv as b
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
    public function display1Line($val)
    {
        global $user, $conf, $lang;
        if($val->type==4)
            $html=$this->display1LineSAV($val);
        else if($val->type==3)
            $html=$this->display1LineMNT($val);
        else if($val->type==2)
            $html=$this->display1LineTKT($val);
        else {
            $html = "<li style='font-weight: normal!important; border:0px Solid !important;' id='".$val->id."' class='ui-state-default'>";
            $html .= "<table><tr class='ui-widget-content pointerCursor'><td class='moveCursor' width=16 rowspan=2>";
            if ( ($this->statut == 0 || $val->statut == 0) && $user->rights->contrat->creer)
            {
                $html .= "<span class='ui-icon ui-icon-carat-2-n-s'></span>";
            }
            if ($val->product)
            {
                $contratProd = "-";
                $price = price($val->subprice * $val->qty,2);

                if ($this->lineTkt[$val->id]['fk_contrat_prod'])
                {
                    $tmpcontratProd = new Product($this->db);
                    $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
                    $contratProd = $tmpcontratProd->getNomUrl(1);
                    //$price = price($tmpcontratProd->price,2);
                }

                $spanExtra = "";
                $spanExtraFin = "";
                if ($val->statut != 5)
                {
                    if ( time() > $this->lineTkt[$val->id]['dfin'])
                    {
                        $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                        $spanExtraFin = "</span>";
                    } else if (time() > $this->lineTkt[$val->id]['dfin'] - intval($conf->global->MAIN_DELAY_RUNNING_SERVICES) * 24 * 3600)
                    {
                        $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                        $spanExtraFin = "</span>";
                    }
                }
                $li = new ContratLigne($this->db);
                $li->fetch($val->id);
                $html .= '<th class="ui-widget-header ui-state-default" width=100 rowspan=2>Libre';
                $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au ".$val->date_fin_prevue."";
                $requete = "SELECT durRenew,
                                   unix_timestamp(date_renouvellement) as date_renouvellement
                              FROM Babel_contrat_renouvellement
                             WHERE contratdet_refid = ".$val->id
                       ." ORDER BY date_renouvellement DESC
                             LIMIT 1 ";
                $sql = $this->db->query($requete);
                $res = $this->db->fetch_object($sql);
                if ($res->date_renouvellement > 0)
                {
                    $html .= '<br/>Renouv. '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
                }
                $html .= "<td class='ui-widget-content' rowspan=2 width=662 align=left>".$val->product->getNomUrl(1) ."<font style='font-weight: normal;'> " .$val->product->libelle .'<br/>'.$val->desc."</font>";
                $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight: normal;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Prix";
                $html .= '<td align=right class="ui-widget-content" style="width: 100px;">'.price($price)."&euro;&nbsp;&nbsp;";
                $html .= "<td class='ui-widget-content' nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>". $li->getLibStatut(5);
                if ($this->statut!=2)
                {
                    $html .= $this->displayStatusIco($val);
                }

                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie";
                $html .= " <td style='font-weight: 0!important; height:3em; padding-left: 5px;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number']."";

    //        require_once('Var_Dump.php');
    //        $val1 = $val;
    //        $val1->db="";
    //        $val1->product->db="";
    //        Var_Dump::Display($this->lineTkt);
            } else {
                $contratProd = "-";
                $price = price($val->subprice * $val->qty,2);

                if ($this->lineTkt[$val->id]['fk_contrat_prod'])
                {
                    $tmpcontratProd = new Product($this->db);
                    $tmpcontratProd->fetch($this->lineTkt[$val->id]['fk_contrat_prod']);
                    $contratProd = $tmpcontratProd->getNomUrl(1);
                    //$price = price($tmpcontratProd->price,2);
                }


                $spanExtra = "";
                $spanExtraFin = "";
                if ($val->statut != 5)
                {
                    if ( time() > $this->lineTkt[$val->id]['dfin'])
                    {
                        $spanExtra = "<span style='border: 0px;' class='ui-state-error'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                        $spanExtraFin = "</span>";
                    } else if (time() > $this->lineTkt[$val->id]['dfin'] - intval($conf->global->MAIN_DELAY_RUNNING_SERVICES) * 24 * 3600)
                    {
                        $spanExtra = "<span style='border: 0px;' class='ui-state-highlight'><span style='float: left;' class='ui-icon ui-icon-alert'></span>";
                        $spanExtraFin = "</span>";
                    }
                }
                $li = new ContratLigne($this->db);
                $li->fetch($val->id);
                $html .= '<th class="ui-widget-header ui-state-default" width=100 rowspan=2>Libre';
                $html .= "<td nowrap=1  class='ui-widget-content'  valign=middle align=center style='white-space: nowrap; width: 125px;padding-top: 0.5em;' rowspan=2>Du ".$val->date_debut_reel."<br/>au ".$val->date_fin_prevue."";
                $requete = "SELECT durRenew,
                                   unix_timestamp(date_renouvellement) as date_renouvellement
                              FROM Babel_contrat_renouvellement
                             WHERE contratdet_refid = ".$val->id
                       ." ORDER BY date_renouvellement DESC
                             LIMIT 1 ";
                $sql = $this->db->query($requete);
                $res = $this->db->fetch_object($sql);
                if ($res->date_renouvellement > 0)
                {
                    $html .= '<br/>Renouv. '.date('d/m/Y',$res->date_renouvellement). "<br/>pour ".$res->durRenew."m";
                }
                $html .= "<td class='ui-widget-content' rowspan=2 width=662 align=left> - <font style='font-weight: normal;'> - </font>";
                $html .= "<th class='ui-widget-header ui-state-default' nowrap=1  valign=top align=center style='font-weight: normal;white-space: nowrap; width: 100px;padding-top: 0.5em;'>Prix";
                $html .= '<td align=right class="ui-widget-content" style="width: 100px;">'.price($price)."&euro;&nbsp;&nbsp;";
                $html .= "<td class='ui-widget-content' nowrap=1  valign=top align=center style='white-space: nowrap; width: 100px;padding-top: 0.5em;'>". $li->getLibStatut(5);
                if ($this->statut!=2)
                {
                    $html .= $this->displayStatusIco($val);
                }

                $html .= " <tr><th style='font-weight:normal!important;' class='ui-widget-header ui-state-default' colspan=1>Num de s&eacute;rie";
                $html .= " <td style='font-weight: 0!important; height:3em; padding-left: 5px;' class='ui-widget-content' colspan=2>".$val->GMAO_Mixte['serial_number']."";

    //        require_once('Var_Dump.php');
    //        $val1 = $val;
    //        $val1->db="";
    //        $val1->product->db="";
    //        Var_Dump::Display($this->lineTkt);

            }
            $html .= "</table>";
            $html .= "</li>";
        }
        return($html);
    }
    public function fetch_lignes($byid=false)
    {
        $this->nbofserviceswait=0;
        $this->nbofservicesopened=0;
        $this->nbofservicesclosed=0;
        $this->lignes=array();
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
                       g.type,
                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfinprev,
                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfin,
                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
                       g.durValid as GMAO_durVal,
                       g.hotline as GMAO_hotline,
                       g.telemaintenance as GMAO_telemaintenance,
                       g.maintenance as GMAO_maintenance,
                       g.SLA as GMAO_sla,
                       g.clause as GMAO_clause,
                       g.isSAV as GMAO_isSAV,
                       g.qte as GMAO_qte,
                       g.nbVisite as GMAO_nbVisite,
                       g.fk_prod as GMAO_fk_prod,
                       g.reconductionAuto as GMAO_reconductionAuto,
                       g.maintenance as GMAO_maintenance,
                       g.prorataTemporis as GMAO_prorata,
                       g.prixAn1 as GMAO_prixAn1,
                       g.prixAnDernier as GMAO_prixAnDernier,
                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
                       g.qteTempsPerDuree as GMAO_qteTempsPerDuree,
                       g.qteTktPerDuree as GMAO_qteTktPerDuree,
                       sc.serial_number as GMAO_serial_number,
                       d.total_ht,
                       d.qty,
                       d.remise_percent,
                       d.subprice,
                       d.info_bits,
                       d.fk_product,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture,
                       UNIX_TIMESTAMP(d.date_valid) as dateCompare,
                       ifnull(d.avenant,9999999999) as avenant
                  FROM ".MAIN_DB_PREFIX."contratdet as d
             LEFT JOIN llx_product as p ON  d.fk_product = p.rowid
             LEFT JOIN Babel_GMAO_contratdet_prop as g ON g.contratdet_refid = d.rowid
             LEFT JOIN Babel_product_serial_cont as sc ON sc.element_id = d.rowid AND sc.element_type LIKE 'contrat%'
                 WHERE d.fk_contrat = ".$this->id ."
              ORDER BY avenant, line_order";
//date_debut_prevue = $objp->date_ouverture_prevue;
//print $sql;
//                //$ligne->date_debut_reel   = $objp->date_ouverture;
        dol_syslog("Contrat::fetch_lignes sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->lignes=array();
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($objp = $this->db->fetch_object($result))
            {

                $ligne                 = new ContratLigne($this->db);
                $ligne->id             = $objp->rowid;
                $ligne->desc           = $objp->description;  // Description ligne
                $ligne->description    = $objp->description;  // Description ligne
                $ligne->qty            = $objp->qty;
                $ligne->fk_contrat     = $this->id;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->statut         = $objp->statut;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht       = $objp->total_ht;
                $ligne->fk_product     = $objp->fk_product;
                $ligne->type           = $objp->type;           //contrat Mixte
                $ligne->avenant        = $objp->avenant;
               require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
               $tmpProd = new Product($this->db);
               $tmpProd1 = new Product($this->db);
               ($objp->fk_product>0?$tmpProd1->fetch($objp->fk_product):$tmpProd1=false);
               ($objp->GMAO_fk_contrat_prod>0?$tmpProd->fetch($objp->GMAO_fk_contrat_prod):$tmpProd=false);
               $ligne->product = $tmpProd1;
//LineTkt
//                    'serial_number'=>$res->serial_number ,
               $ligne->GMAO_Mixte=array();
               $ligne->GMAO_Mixte = array(
                    'fk_contrat_prod'   => ($objp->GMAO_fk_contrat_prod>0?$objp->GMAO_fk_contrat_prod:false),
                    'contrat_prod'      => $tmpProd,
                    'durVal'            => $objp->GMAO_durVal,
                    'tickets'           => $objp->GMAO_qte,
                    'qty'               => $objp->qty,
                    'pu'                => $objp->subprice,
                    'dfinprev'          => $objp->GMAO_dfinprev,
                    'dfin'              => $objp->GMAO_dfin,
                    'ddeb'              => $objp->GMAO_ddeb,
                    'hotline'           => $objp->GMAO_hotline,
                    'telemaintenance'   => $objp->GMAO_telemaintenance,
                    'maintenance'       => $objp->GMAO_maintenance,
                    'SLA'               => $objp->GMAO_sla,
                    'nbVisiteAn'        => $objp->GMAO_nbVisite * intval(($objp->qty>0?$objp->qty:1)),
                    'isSAV'             => $objp->GMAO_isSAV,
                    'fk_prod'           => $objp->GMAO_fk_prod,
                    'reconductionAuto'  => $objp->GMAO_reconductionAuto,
                    'maintenance'       => $objp->GMAO_maintenance,
                    'serial_number'     => $objp->GMAO_serial_number,
                    'ddebprev'          => $objp->GMAO_ddebprev,
                    "clause"            => $objp->GMAO_clause,
                    "prorata"           => $objp->GMAO_prorata,
                    "prixAn1"           => $objp->GMAO_prixAn1,
                    "prixAnDernier"     => $objp->GMAO_prixAnDernier,
                    "qteTempsPerDuree"  => $objp->GMAO_qteTempsPerDuree,
                    "qteTktPerDuree"    => $objp->GMAO_qteTktPerDuree,

               );




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
                $ligne->product_desc   = $objp->product_desc; // Description produit

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;
                $ligne->dateCompare    = $objp->dateCompare;
                if($byid){
                        $this->lignes[$objp->rowid]               = $ligne;
                } else {
                    if ($objp->line_order != 0)
                    {
                        $this->lignes[$objp->line_order]        = $ligne;
                    } else {
                        $this->lignes[]        = $ligne;
                    }
                }
                //dol_syslog("1 ".$ligne->desc);
                //dol_syslog("2 ".$ligne->product_desc);

                if ($ligne->statut == 0) $this->nbofserviceswait++;
                if ($ligne->statut == 4) $this->nbofservicesopened++;
                if ($ligne->statut == 5) $this->nbofservicesclosed++;

                $i++;
            }
            $this->db->free($result);
//            require_once('Var_Dump.php');
//            Var_Dump::Display($this->lignes);
        } else {
            dol_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liees aux produits");
            return -3;
        }
        // Selectionne les lignes contrat liees a aucun produit
        $sql = "SELECT p.label,
                       p.description as product_desc,
                       p.ref,
                       d.rowid,
                       d.statut,
                       d.description,
                       d.price_ht,
                       d.tva_tx,
                       d.line_order,
                       g.type,
                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfinprev,
                       unix_timestamp(date_add(date_add(g.DateDeb, INTERVAL g.durValid month), INTERVAL ifnull(p.durSav,0) MONTH)) as GMAO_dfin,
                       unix_timestamp(g.DateDeb) as GMAO_ddeb,
                       unix_timestamp(g.DateDeb) as GMAO_ddebprev,
                       g.durValid as GMAO_durVal,
                       g.hotline as GMAO_hotline,
                       g.telemaintenance as GMAO_telemaintenance,
                       g.maintenance as GMAO_maintenance,
                       g.SLA as GMAO_sla,
                       g.clause as GMAO_clause,
                       g.isSAV as GMAO_isSAV,
                       g.fk_prod as GMAO_fk_prod,
                       g.reconductionAuto as GMAO_reconductionAuto,
                       g.maintenance as GMAO_maintenance,
                       g.prorataTemporis as GMAO_prorata,
                       g.prixAn1 as GMAO_prixAn1,
                       g.prixAnDernier as GMAO_prixAnDernier,
                       g.fk_contrat_prod as GMAO_fk_contrat_prod,
                       sc.serial_number as GMAO_serial_number,
                       d.total_ht,
                       d.qty,
                       d.remise_percent,
                       d.subprice,
                       d.info_bits,
                       d.fk_product,
                       date_format(d.date_ouverture_prevue,'%d/%m/%Y') as date_ouverture_prevue,
                       date_format(d.date_ouverture ,'%d/%m/%Y') as date_ouverture,
                       date_format(d.date_fin_validite,'%d/%m/%Y') as date_fin_validite,
                       date_format(d.date_cloture,'%d/%m/%Y') as date_cloture,
                       UNIX_TIMESTAMP(d.date_valid) as dateCompare,
                       ifnull(d.avenant,9999999999) as avenant
                  FROM ".MAIN_DB_PREFIX."contratdet as d
             LEFT JOIN llx_product as p ON  d.fk_product = p.rowid
             LEFT JOIN Babel_GMAO_contratdet_prop as g ON g.contratdet_refid = d.rowid
             LEFT JOIN Babel_product_serial_cont as sc ON sc.element_id = d.rowid AND sc.element_type LIKE 'contrat%'
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
                $ligne->type       = $objp->type;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->total_ht;
                $ligne->total_ht          = $objp->total_ht;
                $ligne->fk_product     = 0;

                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;

                if ($ligne->statut == 0) $this->nbofserviceswait++;
                if ($ligne->statut == 4) $this->nbofservicesopened++;
                if ($ligne->statut == 5) $this->nbofservicesclosed++;

               require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
               $tmpProd = new Product($this->db);
               $tmpProd1 = new Product($this->db);
               ($objp->fk_product>0?$tmpProd1->fetch($objp->fk_product):$tmpProd1=false);
               ($objp->GMAO_fk_contrat_prod>0?$tmpProd->fetch($objp->GMAO_fk_contrat_prod):$tmpProd=false);
               $ligne->product = $tmpProd1;
//LineTkt
//                    'serial_number'=>$res->serial_number ,
               $ligne->GMAO_Mixte=array();
               $ligne->GMAO_Mixte = array(
                    'fk_contrat_prod'   => ($objp->GMAO_fk_contrat_prod>0?$objp->GMAO_fk_contrat_prod:false),
                    'contrat_prod'      => $tmpProd,
                    'durVal'            => $objp->GMAO_durVal,
                    'qty'               => $objp->qty,
                    'pu'                => $objp->subprice,
                    'dfinprev'          => $objp->GMAO_dfinprev,
                    'dfin'              => $objp->GMAO_dfin,
                    'ddeb'              => $objp->GMAO_ddeb,
                    'hotline'           => $objp->GMAO_hotline,
                    'telemaintenance'   => $objp->GMAO_telemaintenance,
                    'maintenance'       => $objp->GMAO_maintenance,
                    'SLA'               => $objp->GMAO_sla,
                    'isSAV'             => $objp->GMAO_isSAV,
                    'fk_prod'           => $objp->GMAO_fk_prod,
                    'reconductionAuto'  => $objp->GMAO_reconductionAuto,
                    'maintenance'       => $objp->GMAO_maintenance,
                    'serial_number'     => $objp->GMAO_serial_number,
                    'ddebprev'          => $objp->GMAO_ddebprev,
                    "clause"            => $objp->GMAO_clause,
                    "prorata"           => $objp->GMAO_prorata,
                    "prixAn1"           => $objp->GMAO_prixAn1,
                    "prixAnDernier"     => $objp->GMAO_prixAnDernier
               );


                if($byid){
                        $this->lignes[$objp->rowid]        = $ligne;
                } else {
                    if ($objp->line_order != 0)
                    {
                        $this->lignes[$objp->line_order]        = $ligne;
                    } else {
                        $this->lignes[]        = $ligne;
                    }
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
    public function displayLine()
    {
        global $user, $conf, $lang;
            $html = "";
            if ($this->statut != 0)
            {
                $html .= "<ul style='list-style: none; padding-left: 0px; padding-top:0; margin-bottom:0; margin-top: 0px;'>";
            } else {
                $html .= "<ul id='sortable' style='list-style: none; padding-left: 0px; margin-bottom:0; padding-top:0; margin-top: 0px;'>";
            }
            $cspan=4;
            if ($this->statut!=2)
            {
                $cspan=6;
            }
            $html .= '<li class="titre ui-state-default ui-widget-header">
                          <table width=100% cellpadding=15 style="font-size:12pt;">
                              <tr><td width=16>&nbsp;
                                  <td colspan='.$cspan.'>Contrat initial';
            $html .= '        </tr>
                          </table>
                      </li>';
            $has_avenant=false;
            $iter=1;
            $this->lignes = array();
//            require_once('Var_Dump.php');
//            var_dump::display($this->lignes);
            $this->fetch_lignes(true);
            $remAv = false;
            foreach($this->lignes as $key => $val)
            {
                //date_validation
//                if( ($this->statut > 0 && $this->date_validation <  $val->dateCompare) || ($this->statut >0 && $val->statut == 0 && $remAv != $val->avenant) ){
                if( ($this->statut >0 && $val->statut == 0 && $remAv != $val->avenant) || ($this->statut >0 && $val->statut > 0 && $remAv != $val->avenant) ){
                    $has_avenant=true;
                    $html .= '<li class="titre ui-state-default ui-widget-header">
                                  <table width=100% cellpadding=15 style="font-size:12pt;">
                                      <tr><td width=16>&nbsp;
                                          <td colspan='.$cspan.'>Avenant '.$iter;
                    if ($val->avenant > 0)
                    {
                        $remAv = $val->avenant;
                        if($val->avenant != 9999999999){
                            $requete = "SELECT unix_timestamp(date_avenant)  as da FROM Babel_contrat_avenant WHERE id = ".$val->avenant;
                            $sql = $this->db->query($requete);
                            $res = $this->db->fetch_object($sql);
                            $html .= " du ". date('d/m/Y',$res->da);
                        }
                    }
                    $iter++;
                    $html .= '        </tr>
                                  </table>
                              </li>';
                    if ($this->statut >0 && $val->statut == 0){
                        $html .= "</ul><ul id='sortable' style='list-style: none; margin-bottom:0; padding-left: 0px; padding-top:0; margin-top: 0px;'>";
                    }

                }
                $html .= $this->display1line($val);
            }
            $html .= "</ul>";
            return ($html);

    }
    public function getExtraHeadTab($head)
    {
        global $langs;
        $h = count($head);
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/annexes.php?id='.$this->id;
        $head[$h][1] = $langs->trans("Annexe PDF");
        $head[$h][2] = 'Annexes';
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/intervByContrat.php?id='.$this->id;
        $head[$h][1] = $langs->trans("Interv.");
        $head[$h][2] = 'Interv';
        $h++;
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/tktByContrat.php?id='.$this->id;
        $head[$h][1] = $langs->trans("Tickets");
        $head[$h][2] = 'Tickets';
        $h++;
        $head[$h][0] = DOL_URL_ROOT.'/Babel_GMAO/savByContrat.php?id='.$this->id;
        $head[$h][1] = $langs->trans("SAV");
        $head[$h][2] = 'SAV';
        $h++;
        return($head);
    }
    public function displayStatusIco($val)
    {
        global $conf, $user, $langs;
        $html .= '<td nowrap=1 rowspan=2  align="center" valign=middle class="ui-widget-content"  nowrap="nowrap" style="width:50px;padding-top: 0.5em;">';

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
                if ($val->statut == 4)
                {
//                    $html .= '<span title="D&eacute;sactiver" class="ui-icon ui-icon-arrowrefresh-1-n" style="float: left; width:16px; cursor: pointer;" onclick="unactivateLine(this,'.$this->id.','.$val->id.');" >';
//                    $html .= '</span>';
                    $html .= '<span class="ui-icon ui-icon-cancel" title="Cloturer" style="float: left; width:16px;cursor: pointer;" onclick="closeLine(this,'.$this->id.','.$val->id.');" >';
                    $html .= '</span>';

//                    $html .= '<span class="ui-icon ui-icon-refresh" title="Renouveller" style="float: left; width:16px;cursor: pointer;" onclick="renewLine(this,'.$this->id.','.$val->id.');" >';
//                    $html .= '</span>';
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
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        $form = new Form($this->db);
        global $langs;
        if($_GET['action']=="setCondReg")
        {
            $requete= "UPDATE ".MAIN_DB_PREFIX."contrat
                          SET condReg_refid =".$_REQUEST['cond_reglement_id']. "
                        WHERE rowid = ".$this->id;
            $sql = $this->db->query($requete);
            if($sql) $this->condReg_refid =  $_REQUEST['cond_reglement_id'];
        }
        if($_GET['action']=="setModeReg")
        {
            $requete= "UPDATE ".MAIN_DB_PREFIX."contrat
                          SET modeReg_refid =".$_REQUEST['mode_reglement_id']. "
                        WHERE rowid = ".$this->id;
//print $requete;
            $sql = $this->db->query($requete);
            if($sql) $this->modeReg_refid =  $_REQUEST['mode_reglement_id'];
        }
        if($_REQUEST['action'] == 'changeCondReg')
        {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>Condition de r&egrave;glement";
            $html .= "    <td class='ui-widget-content'>".$form->form_conditions_reglement("fiche.php?action=setCondReg&amp;id=".$this->id, $this->condReg_refid , 'cond_reglement_id', 0,$display=false);
        } else {
            $html .= "<tr><th class='ui-widget-header ui-state-default'>Condition de r&egrave;glement";
            $html .= '<span style="float:right;"><a href="'.$_SERVER["PHP_SELF"].'?action=changeCondReg&amp;id='.$this->id.'">'.img_edit($langs->trans("Condition de r&egrave;glement")).'</a></span>';
            $html .= "    <td class='ui-widget-content'>".$form->form_conditions_reglement("fiche.php?action=setCondReg&amp;id=&amp;id=".$this->id, $this->condReg_refid , 'none', 0,$display=false);
        }

        if($_REQUEST['action'] == 'changeModeReg')
        {
            $html .= "    <th class='ui-widget-header ui-state-default'>Mode de r&egrave;glement";
            $html .= "    <td class='ui-widget-content'>".$form->form_modes_reglement("fiche.php?action=setModeReg&amp;id=".$this->id, $this->modeReg_refid , 'mode_reglement_id',false);
        } else {
            $html .= "    <th class='ui-widget-header ui-state-default'>Mode de r&egrave;glement";
            $html .= '<span style="float:right;"><a href="'.$_SERVER["PHP_SELF"].'?action=changeModeReg&amp;id='.$this->id.'">'.img_edit($langs->trans("Mode de r&egrave;glement")).'</a></span>';
            $html .= "    <td class='ui-widget-content'>".$form->form_modes_reglement("fiche.php?action=setModeReg&amp;id=".$this->id, $this->modeReg_refid , 'none',false);
        }

        return $html;
    }
    public function initDialog($mysoc,$objp)
    {
        global $user, $conf;
        $html = "";
        if ($user->rights->contrat->creer || ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) ))
        {
            $html .= '<div id="addDialog" class="ui-state-default ui-corner-all" style="">';
            $html .= $this->displayDialog('add',$mysoc,$objp);
            $html .= '</div>';
        }

        if ($user->rights->contrat->supprimer && ($this->statut ==0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)))
        {
            $html .=  '<div id="delDialog"><span id="delDialog-content"></span></div>';
        }
        if ($user->rights->contrat->creer || ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED) ))
        {
            $html .=  '<div id="modDialog"><span id="modDialog-content">';
            $html .=  $this->displayDialog('mod',$mysoc,$objp);
            $html .=  '</span></div>';
        }

        if ($user->rights->contrat->activer && $this->statut !=0)
        {
            $html .=  '<div id="activateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<table width=450><tr><td>Date de d&eacute;but effective du service<td>";
            $html .=  "<input type='text' name='dateDebEff' id='dateDebEff'>";
            $html .=  "<tr><td>Date de fin effective du service<td>";
            $html .=  "<input type='text' name='dateFinEff' id='dateFinEff'>";
            $html .=  "</table>";
            $html .=  '</div>';
        }
        if ($user->rights->contrat->desactiver && $this->statut !=0)
        {
            $html .=  '<div id="unactivateDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<p>&Ecirc;tes vous sur de vouloir d&eacute;sactiver cette ligne&nbsp;?</p>";
            $html .=  '</div>';
        }
        //var_dump($user->rights->contrat);
        if ($user->rights->contrat->activer && $this->statut != 0)
        {
            $html .=  '<div id="closeLineDialog" class="ui-state-default ui-corner-all" style="">';
            $html .=  "<p>&Ecirc;tes vous sur de vouloir cl&ocirc;turer cette ligne&nbsp;?</p>";
            $html .=  '</div>';
        }


        return($html);
    }
    public function displayDialog($type='add',$mysoc,$objp=false)
    {
        global $conf, $form;


        $html .= '<div id="'.$type.'Line" class="ui-state-default ui-corner-all" style="">';
        $html .= "<form id='".$type."Form' method='POST' onsubmit='return(false);'>";
        $html .= "<div id='".$type."dialogTab'>";
        $html .= "<ul>";
        $html .= "    <li><a href='#".$type."general'><span>G&eacute;n&eacute;ral</span></a></li>";
        $html .= "    <li><a href='#".$type."price'><span>Prix</span></a></li>";
        $html .= "    <li><a href='#".$type."produit'><span>Produit</span></a></li>";
        $html .= "    <li><a href='#".$type."detail'><span>D&eacute;tail</span></a></li>";
        $html .= "    <li><a href='#".$type."clause'><span>Condition</span></a></li>";
        $html .= "</ul>";

        $html .= "<div id='".$type."clause'>";
        $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr style="border-bottom: 1px Solid #0073EA !important">';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .=  'Clauses juridiques<br/>';
        $html .=  "<textarea style='width: 600px; height: 6em' name='".$type."Clause' id='".$type."Clause'></textarea>";
        $html .=  '</td>';
        $html .=  '</tr>';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .=  'Clauses produit (rappel)<br/>';
        $html .=  "<div style='width: 600px; height: 6em border:1px Solid;padding: 5px;'  class='ui-widget-content'   name='".$type."ClauseProd' id='".$type."ClauseProd'></div>";
        $html .=  '</td>';
        $html .=  '</tr>';
        $html .=  '<tr class="ui-state-default ui-widget-content" style="border: 1px Solid #0073EA;">';
        $html .=  '<td style="border-right: 1px Solid #0073EA;" colspan=3>';
        $html .=  'Clauses contrat (rappel)<br/>';
        $html .=  "<div style='width: 600px; height: 6em border:1px Solid;padding: 5px;' class='ui-widget-content' name='".$type."ClauseProdCont' id='".$type."ClauseProdCont'></div>";
        $html .=  '</td>';
        $html .=  '</tr>';

        $html .=  "</table>";
        $html .= "</div>";


        $html .= "<div id='".$type."general'>"."\n";
//        $html .=  '<span class="ui-state-default ui-widget-header ui-corner-all" style="margin-top: -4px; padding: 5px 35px 5px 35px;">Ajout d\'une ligne</span>';
        $html .=  '<table style="width: 870px;" cellpadding=10 >'."\n";
        $html .=  '<tr>'."\n";
        $html .=  '<th class="ui-widget-header ui-state-default" colspan=1>Description</th>'."\n";
        $html .=  "<td class='ui-widget-content' colspan=2><textarea style='width: 600px; height: 3em' name='".$type."Desc' id='".$type."Desc'></textarea>"."\n";
        $html .=  '</td>'."\n";
        $html .=  '</tr>'."\n";
        $html .=  '<tr>'."\n";
        $html .=  '<th class="ui-widget-header ui-state-default" width=150 colspan=1>Date de d&eacute;but'."\n";
        $html .=  '<td class="ui-widget-content" colspan=2><input type="text" style="width: 100px" name="dateDeb'.$type.'" id="dateDeb'.$type.'">'."\n";
        $html .=  '</td>'."\n";
        $html .=  '</tr>'."\n";
        $html .=  '<tr>'."\n";
        $html .=  '<th class="ui-widget-header ui-state-default" colspan=1>SLA'."\n";
        $html .=  '<td class="ui-widget-content" colspan=2><input type="text" style="width: 100px" name="'.$type.'SLA" id="'.$type.'SLA">'."\n";
        $html .=  '</td>'."\n";
        $html .=  '</tr>'."\n";
        $html .=  '<tr>'."\n";
        $html .=  '<th class="ui-widget-header ui-state-default" colspan=1>Reconduction automatique'."\n";
        $html .=  '<td class="ui-widget-content" colspan=2><input type="checkbox" name="'.$type.'recondAuto" id="'.$type.'recondAuto">'."\n";
        $html .=  '</td>'."\n";
        $html .=  '</tr>'."\n";
        $html .=  '<tr>'."\n";
        $html .=  '<th class="ui-widget-header ui-state-default" colspan=1>Commande'."\n";

        $html .=  '<td class="ui-widget-content" width=200>'."\n";
        $html .= "\n".'<SELECT name="'.$type.'Commande" id="'.$type.'Commande"> '."\n";
        $html .= "<OPTION value='-1'>S&eacute;lectionner -></OPTION>"."\n";

        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."commande WHERE fk_soc = ".$this->societe->id;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)){
            $html .= "<OPTION value='".$res->rowid."'>".$res->ref. " ". $res->date_commande ."</OPTION>\n";
        }
        $html .= "</SELECT>\n";

        $html .= "<script>\n";
        $html .= "       var fk_soc = ".$this->societe->id.";";
        $html .= "   jQuery(document).ready(function(){\n";
        $html .= "       jQuery('#".$type."Commande').change(function(){\n";
        $html .= "       var type = '".$type."';\n";
        $html .= <<<EOF
          var seekId = jQuery(this).find(':selected').val();
          if (seekId > 0){

              jQuery.ajax({
                  url: DOL_URL_ROOT+"/Babel_GMAO/ajax/listCommandeDet-xml_response.php",
                  data: "id="+seekId,
                  datatype:"xml",
                  type: "POST",
                  cache: true,
                  success: function(msg){
                    var longHtml = '<span id="'+type+'commandeDet">';
                        longHtml += "<SELECT name='"+type+"LigneCom'  name='"+type+"LigneCom'><OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                    jQuery(msg).find('commandeDet').each(function(){
                        var idLigne = jQuery(this).attr('id');
                        var valLigne = jQuery(this).text();
                        longHtml += "<OPTION value='"+idLigne+"'>"+valLigne+"</OPTION>";
                    });
                    longHtml += "</SELECT></span>";
                    jQuery('#'+type+'commandeDet').replaceWith(longHtml);
                    jQuery('#'+type+'commandeDet').find('SELECT').selectmenu({style: 'dropdown', maxHeight: 300 });
                  }
              });
          } else {
              jQuery('#'+type+'commandeDet').replaceWith('<span id="'+type+'commandeDet">&nbsp;</span>');
          }
        });
EOF;

       $html .= 'jQuery("#MnTtype'.$type.'").click(function(){ '.$type.'showGMAO("MnT"); });'."\n";
       $html .= 'jQuery("#TkTtype'.$type.'").click(function(){ '.$type.'showGMAO("TkT"); });'."\n";
       $html .= 'jQuery("#SaVtype'.$type.'").click(function(){ '.$type.'showGMAO("SaV"); });'."\n";
       $html .= '});'."\n";
       $html .= ' var typeContratRem=false;'."\n";
       $html .= 'function '.$type.'showGMAO(typeContrat){'."\n";
       $html .= "    typeContratRem=typeContrat"."\n";
       $html .= "    if (jQuery('#ticket".$type."').css('display')=='block') { jQuery('#ticket".$type."').slideUp('fast',function(){ ".$type."showGMAO2(); })}"."\n";
       $html .= "    else if (jQuery('#maintenance".$type."').css('display')=='block') { jQuery('#maintenance".$type."').slideUp('fast',function(){ ".$type."showGMAO2(); })}"."\n";
       $html .= "    else if (jQuery('#savgmao".$type."').css('display')=='block') { jQuery('#savgmao".$type."').slideUp('fast',function(){ ".$type."showGMAO2(); })}"."\n";
       $html .= "    else { ".$type."showGMAO2(); }"."\n";
       $html .= "}"."\n";

       $html .= 'function '.$type.'showGMAO2(){'."\n";
       $html .= "    if (typeContratRem == 'MnT'){"."\n";
       $html .= "      jQuery('#maintenance".$type."').slideDown();"."\n";
       $html .= "    }"."\n";
       $html .= "    if (typeContratRem == 'TkT'){"."\n";
       $html .= "      jQuery('#ticket".$type."').slideDown();"."\n";
       $html .= "    }"."\n";
       $html .= "    if (typeContratRem == 'SaV'){"."\n";
       $html .= "      jQuery('#savgmao".$type."').slideDown();"."\n";
       $html .= "    }"."\n";
       $html .= "}"."\n";

       $html .=<<<EOF
    function publish_selvalue_callBack(obj,value){
        if (jQuery(obj).attr('id') == "p_idprod_add")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdClause-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#addClauseProd').replaceWith('<div id="addClauseProd" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idprod_mod")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdClause-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#modClauseProd').replaceWith('<div id="modClauseProd" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idContratprod_add")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdContratProd-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var price = jQuery(msg).find('price').text();
                    var tva = jQuery(msg).find('tva').text();
                    var qte = jQuery(msg).find('qte').text();
                    var qteMNT = jQuery(msg).find('qteMNT').text();
                    var clause = jQuery(msg).find('clause').text();
                    var Hotline = jQuery(msg).find('Hotline').text();
                    var TeleMaintenance = jQuery(msg).find('TeleMaintenance').text();
                    var Maintenance = jQuery(msg).find('Maintenance').text();
                    var SLA = jQuery(msg).find('SLA').text();
                    var VisiteSurSite = jQuery(msg).find('VisiteSurSite').text();
                    var reconductionAuto = jQuery(msg).find('reconductionAuto').text();
                    var durValid = jQuery(msg).find('durValid').text();
                    var isSAV = jQuery(msg).find('isSAV').text();

                    var qteTktPerDuree = jQuery(msg).find('qteTktPerDuree').text();
                    var qteTempsPerDuree = jQuery(msg).find('qteTempsPerDuree').text();
                    var qteTempsPerDureeH = jQuery(msg).find('qteTempsPerDureeH').text();
                    var qteTempsPerDureeM = jQuery(msg).find('qteTempsPerDureeM').text();

                    jQuery('#qteTktPerDureeadd').val(qteTktPerDuree);
                    jQuery('#qteTempsPerDureeHadd').val(qteTempsPerDureeH);
                    jQuery('#qteTempsPerDureeMadd').val(qteTempsPerDureeM);

                    if (tva + "x" != "x")
                    {
                        var i =0;
                        var remi=0;
                        jQuery('#addtauxtva').find('option').each(function(){
                            if (tva.replace(/,/,'.') == jQuery(this).val())
                            {
                                remi=i;
                            }
                            i++;
                        });
                        jQuery('#addtauxtva').selectmenu('value',remi);
                    }

                    jQuery('#nbTicketadd').val(qte);
                    jQuery('#nbTicketMNTadd').val(qteMNT);
                    if (Hotline == 1){
                        jQuery('#hotlineadd').attr('checked',true);
                    } else {
                        jQuery('#hotlineadd').attr('checked',false);
                    }
                    if (TeleMaintenance == 1){
                        jQuery('#telemaintenanceadd').attr('checked',true);
                    } else {
                        jQuery('#telemaintenanceadd').attr('checked',false);
                    }
                    if (reconductionAuto == 1)
                    {
                        jQuery('#addrecondAuto').attr('checked',true);
                    } else {
                        jQuery('#addrecondAuto').attr('checked',false);
                    }
                    jQuery('#nbVisiteadd').val(VisiteSurSite);
                    jQuery('#addSLA').val(SLA);
                    jQuery('#DurSAVadd').val(durValid);
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#addClauseProdCont').replaceWith('<div id="addClauseProdCont" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');

                    jQuery('input[name=typeadd]').attr('checked',false);
                    //reinit durValid Mnt et Tkt
                    jQuery('#DurValMntadd').val("");
                    jQuery('#DurValTktadd').val("");

                    if (Maintenance == 1)
                    {
                        jQuery('#MnTtypeadd').attr('checked',true);
                        jQuery('#ticketadd').hide();
                        jQuery('#savgmaoadd').hide();
                        addshowGMAO("MnT");
                        jQuery('#DurSAVadd').val("");
                        jQuery('#nbTicketadd').val("");
//                        jQuery('#nbTicketMNTadd').val("");
                        jQuery('#DurValMntadd').val(durValid);
                    } else if (isSAV == 1){
                        jQuery('#SaVtypeadd').attr('checked',true);
                        jQuery('#ticketadd').hide();
                        jQuery('#maintenanceadd').hide();
                        addshowGMAO("SaV");
                        jQuery('#nbTicketadd').val("");
                        jQuery('#nbTicketMNTadd').val("");
                        jQuery('#telemaintenanceadd').attr('checked',false);
                        jQuery('#hotlineadd').attr('checked',false);
                    } else if (qte+"x"!= "x"){
                        jQuery('#TkTtypeadd').attr('checked',true);
                        jQuery('#savgmaoadd').hide();
                        jQuery('#maintenanceadd').hide();
                        addshowGMAO("TkT");
                        jQuery('#DurSAVadd').val("");
                        jQuery('#telemaintenanceadd').attr('checked',false);
                        jQuery('#hotlineadd').attr('checked',false);
                        jQuery('#DurValTktadd').val(durValid);
                        jQuery('#nbTicketMNTadd').val("");
                    }

                    jQuery('#addPuHT').val(price);
                }
            });
        }
        if (jQuery(obj).attr('id') == "p_idContratprod_mod")
        {
            jQuery.ajax({
                url:DOL_URL_ROOT+"/Babel_GMAO/ajax/getProdContratProd-xml_response.php",
                data:"prod="+value+"&fk_soc="+fk_soc,
                datatype:"xml",
                type: "POST",
                success: function(msg){
                    var price = jQuery(msg).find('price').text();
                    var tva = jQuery(msg).find('tva').text();
                    var qte = jQuery(msg).find('qte').text();
                    var qteMNT = jQuery(msg).find('qteMNT').text();
                    var clause = jQuery(msg).find('clause').text();
                    var Hotline = jQuery(msg).find('Hotline').text();
                    var TeleMaintenance = jQuery(msg).find('TeleMaintenance').text();
                    var Maintenance = jQuery(msg).find('Maintenance').text();
                    var SLA = jQuery(msg).find('SLA').text();
                    var VisiteSurSite = jQuery(msg).find('VisiteSurSite').text();
                    var reconductionAuto = jQuery(msg).find('reconductionAuto').text();
                    var durValid = jQuery(msg).find('durValid').text();
                    var isSAV = jQuery(msg).find('isSAV').text();
                    var qteTktPerDuree = jQuery(msg).find('qteTktPerDuree').text();
                    var qteTempsPerDuree = jQuery(msg).find('qteTempsPerDuree').text();
                    var qteTempsPerDureeH = jQuery(msg).find('qteTempsPerDureeH').text();
                    var qteTempsPerDureeM = jQuery(msg).find('qteTempsPerDureeM').text();

                    jQuery('#qteTktPerDureemod').val(qteTktPerDuree);
                    jQuery('#qteTempsPerDureeHmod').val(qteTempsPerDureeH);
                    jQuery('#qteTempsPerDureeMmod').val(qteTempsPerDureeM);

                    if (tva + "x" != "x")
                    {
                        var i =0;
                        var remi=0;
                        jQuery('#modtauxtva').find('option').each(function(){
                            if (tva.replace(/,/,'.') == jQuery(this).val())
                            {
                                remi=i;
                            }
                            i++;
                        });
                        jQuery('#modtauxtva').selectmenu('value',remi);
                    }

                    jQuery('#nbTicketmod').val(qte);
                    jQuery('#nbTicketMNTmod').val(qteMNT);

                    if (Hotline == 1){
                        jQuery('#hotlinemod').attr('checked',true);
                    } else {
                        jQuery('#hotlinemod').attr('checked',false);
                    }
                    if (TeleMaintenance == 1){
                        jQuery('#telemaintenancemod').attr('checked',true);
                    } else {
                        jQuery('#telemaintenancemod').attr('checked',false);
                    }
                    if (reconductionAuto == 1)
                    {
                        jQuery('#modrecondAuto').attr('checked',true);
                    } else {
                        jQuery('#modrecondAuto').attr('checked',false);
                    }
                    jQuery('#nbVisitemod').val(VisiteSurSite);
                    jQuery('#modSLA').val(SLA);
                    jQuery('#DurSAVmod').val(durValid);
                    var clause = jQuery(msg).find('clause').text();
                    jQuery('#modClauseProdCont').replaceWith('<div id="modClauseProdCont" class="ui-widget-content" style="padding: 5px;">'+clause+'</div>');

                    jQuery('input[name=typemod]').attr('checked',false);
                    jQuery('#DurValMntmod').val("");
                    jQuery('#DurValTktmod').val("");
                    if (Maintenance == 1)
                    {
                        jQuery('#MnTtypemod').attr('checked',true);
                        jQuery('#ticketmod').hide();
                        jQuery('#savgmaomod').hide();
                        modshowGMAO("MnT");
                        jQuery('#DurSAVmod').val("");
                        jQuery('#nbTicketmod').val("");
                        jQuery('#DurValMntmod').val(durValid);
                    } else if (isSAV == 1){
                        jQuery('#SaVtypemod').attr('checked',true);
                        jQuery('#ticketmod').hide();
                        jQuery('#maintenancemod').hide();
                        modshowGMAO("SaV");
                        jQuery('#nbTicketmod').val("");
                        jQuery('#nbTicketMNTmod').val("");
                        jQuery('#telemaintenancemod').attr('checked',false);
                        jQuery('#hotlinemod').attr('checked',false);
                    } else if (qte+"x"!= "x"){
                        jQuery('#TkTtypemod').attr('checked',true);
                        jQuery('#savgmaomod').hide();
                        jQuery('#maintenancemod').hide();
                        modshowGMAO("TkT");
                        jQuery('#DurSAVmod').val("");
                        jQuery('#nbTicketMNTmod').val("");
                        jQuery('#telemaintenancemod').attr('checked',false);
                        jQuery('#hotlinemod').attr('checked',false);
                        jQuery('#DurValTktmod').val(durValid);
                    }

                    jQuery('#modPuHT').val(price);
                }
            });
        }
    }

EOF;
       $html .= "</script>"."\n";
       $html .=  '<td class="ui-widget-content"><span id="'.$type.'commandeDet">&nbsp;</span>';
       $html .=  '</td>';
       $html .=  '</tr>';

       $html .=  "</table>";
       $html .= "</div>";


       $html .= "<div id='".$type."produit'>";
       $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
       $html .=  '<tr>';
       $html .=  '<th colspan="4" class="ui-widget-header">Recherche de produits</th></tr>';
       $html .=  '<tr>';
       $html .=  '<th colspan=1 class="ui-state-default ui-widget-header">Produit</th>';
       $html .=  '<td class="ui-widget-content" width=175>';
       // multiprix
       $filter="0";
       if($conf->global->PRODUIT_MULTIPRICES == 1)
           $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
       else
           $html .= $form->select_produits('','p_idprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
       if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
       $html .=  '<td class="ui-widget-content">';
       $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idprod_'.$type.'"></div>';


       $html .=  '</td>';
       $html .=  '<tr>';
       $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Num&eacute;ro de s&eacute;rie';
       $html .=  '<td class="ui-widget-content" colspan=2><input type="text" style="width: 300px" name="'.$type.'serial" id="'.$type.'serial">';
       $html .=  "</table>";
       $html .= "</div>";

       $html .= "<div id='".$type."price'>";
       $html .=  '<table style="width: 870px; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
       $html .=  '<tr>';
       $html .=  '<th class="ui-state-default ui-widget-header" width=150 colspan=1>Produit contrat'."\n";
       $html .=  '<td class="ui-widget-content" colspan=1 width=175>'."\n";
       $filter="2";
       if($conf->global->PRODUIT_MULTIPRICES == 1)
           $html .= $form->select_produits('','p_idContratprod_'.$type,$filter,$conf->produit->limit_size,$this->societe->price_level,1,true,false,false);
       else
           $html .= $form->select_produits('','p_idContratprod_'.$type,$filter,$conf->produit->limit_size,false,1,true,true,false);
       if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $html .=  '<br>';
       $html .= ' <td class="ui-widget-content" colspan=2>';
       $html .=  '<div class="nocellnopadd" id="ajdynfieldp_idContratprod_'.$type.'"></div>';

       $html .=  '<tr>';
       $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Prix HT'."\n";
       $html .=  '<td class="ui-widget-content" colspan=1><input type="text" style="width: 100px" name="'.$type.'PuHT" id="'.$type.'PuHT">'."\n";
       $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Quantit&eacute;'."\n";
       $html .=  '<td class="ui-widget-content" colspan=1><input type="text" style="width: 100px" value="1" name="'.$type.'Qte" id="'.$type.'Qte">'."\n";
       $html .=  '<tr>';
       $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>TVA'."\n";
       $html .=  '<td class="ui-widget-content" colspan=3>'."\n";

       $html .= $form->select_tva($type.'tauxtva','19.6',$mysoc,$this->societe->id,"",0,false);

       $html .=  '<tr style="border: 1px Solid #0073EA;">'."\n";
       $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Ajustement <em>Prorata temporis</em>'."\n";
       $html .=  '<td class="ui-widget-content"  colspan=3><input type="checkbox" name="'.$type.'prorata" id="'.$type.'prorata">'."\n";
       $html .=  '</td>'."\n";
       $html .=  '</tr>'."\n";

        $html .=  "</table>";
        $html .= "</div>";
        $html .= "<div id='".$type."detail'>";
        $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header">Maintenance';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="MnTtype'.$type.'" name="type'.$type.'" value="MnT" type="radio"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header">Ticket';
        $html .=  '</th><td colspan=2 class="ui-widget-content"><input id="TkTtype'.$type.'" name="type'.$type.'" value="TkT" type="radio"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-content">SAV';
        $html .=  '</th><td colspan=2 class="ui-widget-content"><input id="SaVtype'.$type.'" name="type'.$type.'" value="SaV" type="radio"></td>';

        $html .=  "</table>";
        $html .= "<div>";
        $html .= "<div id='maintenance".$type."' style='display: none;'>";
        $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=3>Maintenance';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Nb visite annuelle';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="nbVisite'.$type.'" name="nbVisite'.$type.'"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>T&eacute;l&eacute;maintenance';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input type=checkbox id="telemaintenance'.$type.'" name="telemaintenance'.$type.'"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Hotline';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input type=checkbox name="hotline'.$type.'" id="hotline'.$type.'"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e validit&eacute;<br/>(en mois)';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="DurValMnt'.$type.'" name="DurValMnt'.$type.'"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Nb Ticket<br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="nbTicketMNT'.$type.'" name="nbTicketMNT'.$type.'"></td>';
        $html .=  '<tr><th class="ui-widget-header ui-state-default" >Dur&eacute;e par ticket<br/><em><small>(<b>0 h 0 min</b> sans d&eacute;compte de temps)</small></em></th>
                   <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="1" id="qteTktPerDuree'.$type.'" name="qteTktPerDuree'.$type.'"> ticket(s) pour <input style="text-align:center;" type="text" size=4 value="0" id="qteTempsPerDureeH'.$type.'" name="qteTempsPerDureeH'.$type.'"> h <input style="text-align:center;" type="text" size=4 value="0" id="qteTempsPerDureeM'.$type.'" name="qteTempsPerDureeM'.$type.'"> min';
//                $qteTempsPerDureeM = 0;
//                $qteTempsPerDureeH = 0;
//                if ($product->qteTempsPerDuree > 0 ){
//                    $arrDur = $product->convDur($product->qteTempsPerDuree);
//                    $qteTempsPerDureeH=$arrDur['hours']['abs'];
//                    $qteTempsPerDureeM=$arrDur['minutes']['rel'];
//                }
//
//
//                print '<tr><th class="ui-widget-header ui-state-default" >'.$langs->trans("Dur&eacute;e par ticket<br/><em><small>(<b>0 h 0 min</b>sans d&eacute;compte de temps)</small></em>").'</th>
//                           <td class="ui-widget-content"><input style="text-align:center;" size=4 type="text" value="'.$product->qteTktPerDuree.'" name="qteTktPerDuree"> ticket(s) pour <input style="text-align:center;" type="text" size=4 value="'.$qteTempsPerDureeH.'" name="qteTempsPerDureeH"> h <input style="text-align:center;" type="text" size=4 value="'.$qteTempsPerDureeM.'" name="qteTempsPerDureeM"> min';



        $html .=  "</table>";
        $html .= "</div>";

        $html .= "<div id='ticket".$type."' style='display: none;'>";
        $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=3>Tickets';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Nb Ticket<br/><em><small>(<b>0</b> Sans ticket, <b>-1</b> Illimit&eacute;)</small></em>';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="nbTicket'.$type.'" name="nbTicket'.$type.'"></td>';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e validit&eacute;<br/>(en mois)';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="DurValTkt'.$type.'" name="DurValTkt'.$type.'"></td>';

        $html .=  "</table>";
        $html .= "</div>";

        $html .= "<div id='savgmao".$type."' style='display: none;'>";
        $html .=  '<table style="width: 870px; border: 1px Solid; border-collapse: collapse;margin-top: 20px;" cellpadding=10 >';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=3>SAV';
        $html .=  '<tr>';
        $html .=  '<th class="ui-state-default ui-widget-header" colspan=1>Dur&eacute;e extension<br/>(en mois)';
        $html .=  '</th><td class="ui-widget-content" colspan=2><input id="DurSAV'.$type.'" name="DurSAV'.$type.'"></td>';
        $html .=  "</table>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        $html .= "</div>";

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
            $requete = "UPDATE ".MAIN_DB_PREFIX."contratdet SET avenant = 0, statut = 4, date_valid =now() WHERE fk_contrat =".$this->id;
            $resql = $this->db->query($requete);

            //$this->use_webcal=($conf->global->PHPWEBCALENDAR_CONTRACTSTATUS=='always'?1:0);
            $error=0;
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
        global $user, $conf;

        print "<div id='dialogRenew' >";
        print "<form id='renewContratForm'><table width=100% cellpadding=5>";
        print "<tr><th colspan=2 class='ui-widget-header ui-state-default'>Date du renouvellement</th>
                   <td class='ui-widget-content' colspan=1><input type='text' name='renewDate' id='dateFinmod'>";
        print "    <th colspan=1 class='ui-widget-header ui-state-default'>Dur&eacute;e du renouvellement</th>
                   <td class='ui-widget-content' colspan=1><input type='text' name='renewDurr'>";
        $cnt =  count($this->lignes);
        $iter =0;

        foreach($this->lignes as $key=>$val)
        {
            if($val->statut <5 && $val->statut>0)
            {
//                if ($iter != $cnt && $iter != 0)
                    $style="border-top-style: double;";

                print "<tr style='".$style."'><td class='ui-widget-content' align=center rowspan=3 valign=middle><input type='checkbox' class='chkBoxRenew' value='".$val->id."' name='renew-".$val->id."'>";
                print "<tr style='".$style."'><th class='ui-widget-header ui-state-default'>Type";
                print "    <td class='ui-widget-content'>".($val->type==2?'Ticket':($val->type==3?'Maintenance':($val->type==4?'SAV':"Libre")));
                print "    <th class='ui-widget-header ui-state-default'>Produit Contrat";
                print "    <td class='ui-widget-content'>".($val->GMAO_Mixte['contrat_prod']?$val->GMAO_Mixte['contrat_prod']->getNomUrl(1):"-");
                print "<tr><th class='ui-widget-header ui-state-default'>Produit Concern&eacute;";
                print "    <td class='ui-widget-content'>".($val->product?$val->product->getNomUrl(1):"-");
                print "    <th class='ui-widget-header ui-state-default'>Num. s&eacute;rie";
                print "    <td class='ui-widget-content'>".($val->GMAO_Mixte['serial_number']."x" != "x"?$val->GMAO_Mixte['serial_number']:"-");
                $iter ++;
            }
        }
        print "</table></form>";
        print "</div>";


        print "<script type='text/javascript' src='".DOL_URL_ROOT."/Synopsis_Common/jquery/jquery.tooltip.js'></script>";
        print  "<style>";
        print  ".ui-placeHolder { background-color: #eee05d; opacity: 0.9; border:1px Dashed #999; min-height: 2em; }
               #ui-datepicker-div { z-index: 2000; }
               #modForm input,#addForm input{ text-align:center; }
               #sortable li span img{ cursor: pointer; }
               .pointerCursor { cursor: pointer; }";
        if (($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED)) && 1)
        {
            print  "  .moveCursor { cursor: move; border-left:1px Solid #ccc; } ";
        } else {
            print  "  .moveCursor { cursor: pointer; border-left:1px Solid #ccc; } ";
        }
        print  "</style>";
        print  "<script>";
        print  <<<EOF
            jQuery(document).ready(function(){
                jQuery("#jsContrat li").dblclick(function(){
                    var id = jQuery(this).attr('id');
                    location.href=DOL_URL_ROOT+'/Babel_GMAO/contratDetail.php?id='+id;
                });
            });

EOF;

        print  "</script>";


        $this->sumMnt();
        print "<br/>";
        print "<div class='titre'>R&eacute;sum&eacute;</div>";
        print '<div id="tabs">';
        print "<table cellpadding=15 width=600>";
        $requete = "SELECT count(rowid) as sDI FROM llx_Synopsis_demandeInterv WHERE fk_contrat = ".$this->id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $sumDI = ($res->sDI>0?$res->sDI:0);
        $requete = "SELECT count(rowid) as sFI FROM ".MAIN_DB_PREFIX."fichinter WHERE fk_contrat = ".$this->id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $sumFI = ($res->sFI>0?$res->sFI:0);
        $requete = "SELECT count(id) as sSAV FROM Babel_GMAO_SAV_client WHERE element_type like 'contrat%' AND element_id IN (SELECT rowid FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat =  ".$this->id.")";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $sumSAV = ($res->sSAV>0?$res->sSAV:0);
        $requete = "SELECT count(id) as sTkt FROM Babel_GMAO_Contrat_Tkt WHERE contrat_refid = ".$this->id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $sumTkt = ($res->sTkt>0?$res->sTkt:0);

        print "<tr><th width=150 class='ui-state-default ui-widget-header'>Total HT<td align=right width=150 class='ui-widget-content' nowrap><table width=100%><tr><td>En&nbsp;cours<td align=right>".price($this->totalHT)."&nbsp;&euro;<tr><td><em><small>Inactif:<td align=right><em><small>".price($this->totalHTInactif)."&nbsp;&euro;</table>";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Demande Interv./Fiche Interv.<td align=right width=150 class='ui-widget-content'>".$sumDI." / ".$sumFI;
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TVA<td align=right width=150 class='ui-widget-content'>".price($this->totalTVA)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>SAV<td align=right width=150 class='ui-widget-content'>".$sumSAV."";
        print "<tr><th width=150 class='ui-state-default ui-widget-header'>TTC<td align=right width=150 class='ui-widget-content'>".price($this->totalTTC)." &euro;";
        print "    <th width=150 class='ui-state-default ui-widget-header'>Tickets<td align=right width=150 class='ui-widget-content'>".$sumTkt;
        print "</table>";
        print "<a name='documentAnchor'></a>";

        return "";
    }
    public function sumMnt()
    {
        $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.total_ht,
                           ".MAIN_DB_PREFIX."contratdet.total_tva,
                           ".MAIN_DB_PREFIX."contratdet.total_ttc,
                           ".MAIN_DB_PREFIX."contratdet.qty,
                           ".MAIN_DB_PREFIX."contratdet.price_ht
                      FROM ".MAIN_DB_PREFIX."contratdet,
                           ".MAIN_DB_PREFIX."contrat
                     WHERE ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".MAIN_DB_PREFIX."contrat.rowid
                       AND ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".$this->id. "
                       AND ".MAIN_DB_PREFIX."contratdet.statut <> 0
                       AND ".MAIN_DB_PREFIX."contratdet.statut <> 5 ";
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
        $requete = "SELECT ".MAIN_DB_PREFIX."contratdet.total_ht,
                           ".MAIN_DB_PREFIX."contratdet.total_tva,
                           ".MAIN_DB_PREFIX."contratdet.total_ttc,
                           ".MAIN_DB_PREFIX."contratdet.qty,
                           ".MAIN_DB_PREFIX."contratdet.price_ht
                      FROM ".MAIN_DB_PREFIX."contratdet,
                           ".MAIN_DB_PREFIX."contrat
                     WHERE ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".MAIN_DB_PREFIX."contrat.rowid
                       AND ".MAIN_DB_PREFIX."contratdet.fk_contrat = ".$this->id. "
                       AND ".MAIN_DB_PREFIX."contratdet.statut = 0
                        ";
        $sql = $this->db->query($requete);
        $totalInactif=0;
        while ($res = $this->db->fetch_object($sql))
        {
            $totalInactif += $res->total_ht ;
        }

        $this->totalHT = $total;
        $this->totalHTInactif = $totalInactif;
        $this->totalTVA = $tva;
        $this->totalTTC = $ttc;

        //Par statut d'intervention

        $requete = " SELECT DISTINCT fk_statut FROM llx_Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." ORDER by fk_statut";
        $sql = $this->db->query($requete);
        while($res=$this->db->fetch_object($sql))
        {
            $this->sumDInterByStatut[$res->fk_statut]=0;
        }

        $requete = " SELECT DISTINCT ifnull(fk_user_target, fk_user_prisencharge) as fk_user FROM llx_Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." ORDER by fk_statut";
        $sql = $this->db->query($requete);
        while($res=$this->db->fetch_object($sql))
        {
            $this->sumDInterByUser[$res->fk_user]=0;
        }
        $requete = "SELECT min(datei) as mini, max(datei) as maxi FROM llx_Synopsis_demandeInterv WHERE fk_contrat = ".$this->id." GROUP BY fk_contrat";
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

        $requete = "SELECT fk_statut, datei, ifnull(fk_user_target, fk_user_prisencharge) as fk_user  FROM llx_Synopsis_demandeInterv WHERE fk_contrat = ".$this->id.' ORDER BY datei DESC';
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
            if ( $this->statut == 1)
            {
                    $html .=  '<a class="butAction" href="#" onClick="openDialogAdd();">'.$langs->trans("Avenants").'</a>';
            }

            if ($this->statut >0){
                $hasAvenant=false;
                foreach($this->lignes as $key=>$val)
                {
                    if ($val->statut == 0){
                        $hasAvenant=true;
                        break;
                    }
                }
                if ( $hasAvenant){
                    $html .=  '<a class="butAction" href="#" onClick="validateAvenant();">'.$langs->trans("Valid. avenants").'</a>';
                    $html .= "<div id='confirmAvenant'>&Ecirc;tes vous sur de vouloir valider l'avenant</div>";
                }
            }
            if ($this->statut == 0 || ($this->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
            {
                    $html .=  '<a class="butAction" href="#" onClick="renewContrat();">'.$langs->trans("Renouveller").'</a>';
            }

//             $html .=  "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$this->id."&action=generatePdf>G&eacute;n&eacute;rer</a>";
//             $html .=  "<a class='butAction' href=".$_SERVER['PHP_SELF']."?id=".$this->id."&action=generatePdf&model=BIMP>G&eacute;n&eacute;rer</a>";

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
                $requete = "SELECT * FROM llx_product WHERE rowid = ".$res->fk_product;
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
    public function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0, $info_bits=0,$commandeDet=false)
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
//                $date_end = preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$date_end);
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
            $sql.= " total_ht, total_tva, total_ttc,";
            $sql.= " info_bits,";
            $sql.= " price_ht, remise";
            if ($commandeDet) { $sql .= ",fk_commande_ligne ";}
            if ($date_start > 0) { $sql.= ",date_ouverture_prevue"; }
            if ($date_start > 0) { $sql.= ",date_ouverture"; }
            if ($hasDateEnd)  { $sql.= ",date_fin_validite"; }
            $sql.= ") VALUES ($lineOrder, $this->id, '" . addslashes($desc) . "','" . addslashes($desc) . "',";
            $sql.= ($fk_product>0 ? $fk_product : "null").",";
            $sql.= " '".$qty."',";
            $sql.= " '".$txtva."',";
            $sql.= " ".price2num($remise_percent).",".price2num($pu).",".$user->id .",";
            $sql.= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_ttc).",";
            $sql.= " '".$info_bits."',";
            $sql.= " ".price2num($price).",".price2num( $remise);
            if ($commandeDet){ $sql .= ",".$commandeDet; }
            if ($date_start > 0) { $sql.= ",'".$date_start."'"; }
            if ($date_start > 0) { $sql.= ",'".$date_start."'"; }
            if ($hasDateEnd) {
                if($dateEndHasInterval){
                     $sql.= ", date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." MONTH)";
                } else {
                     $sql.= ",'".$date_end."'";
                }
                 }
            $sql.= ")";
            dol_syslog("Contrat::addline sql=".$sql);
//print $sql;
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
    public function updateline($rowid, $desc, $pu, $qty, $remise_percent=0,
         $date_start='', $date_end='', $tvatx,
         $date_debut_reel='', $date_fin_reel='',$fk_prod=false,$fk_commandedet=false)
    {
        // Nettoyage parametres
        $qty=trim($qty);
        $desc=trim($desc);
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

            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;


        dol_syslog("Contrat::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_debut_reel, $date_fin_reel, $tvatx");
        $hasDateEnd = false;
        $dateEndHasInterval = false;
        if (preg_match('/\+([0-9]*)/',$date_end,$arr)){
            $hasDateEnd=true;
            $dateEndHasInterval=$arr[1];
        }else if ($date_end > 0){
            $hasDateEnd=true;
//                $date_end = preg_replace('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/','$3-$2-$1',$date_end);
        }



        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".addslashes($desc)."'";
        $sql .= ",price_ht='" .     price2num($price)."'";
        $sql .= ",subprice='" .     price2num($price)."'";
        $sql .= ",total_ht='" .     price2num($total_ht)."'";
        $sql .= ",total_tva='" .     price2num($total_tva)."'";
        $sql .= ",total_ttc='" .     price2num($total_ttc)."'";
        $sql .= ",remise='" .       price2num($remise)."'";
        $sql .= ",remise_percent='".price2num($remise_percent)."'";
        $sql .= ",qty='$qty'";
        if($fk_commandedet) { $sql .= ",fk_commande_ligne=".$fk_commandedet." "; }
        $sql .= ",tva_tx='".        price2num($tvatx)."'";
        if ($fk_prod && $fk_prod != 0) { $sql .= ", fk_product=".$fk_prod; }
        else if ($fk_prod == 0){ $sql .= ", fk_product = NULL ";}
        if ($date_start > 0) { $sql.= ",date_ouverture_prevue = '".$date_start."'"; }
        if ($date_start > 0) { $sql.= ",date_ouverture = '".$date_start."'"; }
        if ($hasDateEnd){
        { $sql.= ",date_fin_validite="; }
            if($dateEndHasInterval){
                     $sql.= " date_add(date_ouverture_prevue,INTERVAL ".$dateEndHasInterval." MONTH) ";
                } else {
                     $sql.= " '".$date_end."' ";
                }
        }
//        if ($date_end > 0) { $sql.= ",date_fin_validite=".$this->db->idate($date_end); }
//        else { $sql.=",date_fin_validite=null"; }
//        if ($date_debut_reel > 0) { $sql.= ",date_ouverture=".$this->db->idate($date_debut_reel); }
//        else { $sql.=",date_ouverture=null"; }
//        if ($date_fin_reel > 0) { $sql.= ",date_cloture=".$this->db->idate($date_fin_reel); }
//        else { $sql.=",date_cloture=null"; }
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


}
?>