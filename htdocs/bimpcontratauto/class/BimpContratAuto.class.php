<?php

/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/bimpcontratauto/class/BimpcontratAuto.class.php
 * 	\ingroup    bimpcontratauto
 * 	\brief      Chose 
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class BimpContratAuto extends CommonObject {

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    function __construct($db) {
        global $conf;
        $this->db = $db;
    }

    function create() {
        dol_syslog(get_class($this) . '::create', LOG_DEBUG);
    }

    /**
     * 
     * @param type $id_client
     * @return $dolContratObject  array of all contrats/service by contrat
     */
    function getAllContrats($id_client) {
        $dolContratObject = $this->getDolContratsObject($id_client);
        $contrats = $this->fillCustomFieldForContrat($dolContratObject);
        $contrats = $this->addServices($dolContratObject, $contrats);
//                $newContrat = array('id' => $objp->id, 'nomUrl' => $contrat->getNomUrl(1, 12));
//                $contrats[] = $newContrat;
        return $contrats;
    }

    /**
     * Create an array of contrats and its service
     * To send to the view
     */
    function fillCustomFieldForContrat($dolContratObject) {
        $contrats = array();
        foreach ($dolContratObject as $contratObj) {
            $contrats[$contratObj->id] = array( 'ref' => $contratObj->ref,
                                                'dateDebutContrat' => $contratObj->date_creation,
                                                'nbService' => sizeof($contratObj->lines),
                                                'statut' => $contratObj->statut);       // 1 -> open ; 0 -> closed
        }
        return $contrats;
    }

    /**
     * Add services to contrats
     */
    function addServices($dolContratObject, $contrats) {
        foreach ($dolContratObject as $contratObj) {
            $prixTotalContrat  = 0;
            $lines = $contratObj->fetch_lines();    // get services
            $services = array();                    // init tab service
            
            foreach ($lines as $line) {
                $duree = $this->getServiceDuration($line->fk_product);
                
                if (!isset($lowestDuration)) {                  // Recherche du
                    $lowestDuration = $line->qty;               // service qui se
                } else if ($lowestDuration > $line->qty) {      // fini 
                    $lowestDuration = $duree;                   // en
                }                                               // premier
                
                $services[] = array(
                    'id_product' => $line->id,      // attention l'id n'est pas toujours définit
                    'ref' => $line->ref,
                    'duree' => $duree,              // non utilisé
                    'qty' => $line->qty,            // duréee du service (en mois)
                    'dateDebutService' => $line->date_ouverture_prevue,        // TODO ou date_ouverture ?
                    'prixUnitaire' => $line->price,
                    'prixTotal' => $line->qty * $line->price);                 // TODO prixTotal dépend de quantité et non pas de durée
                $prixTotalContrat += $line->qty * $line->price ;
            }
            $contrats[$contratObj->id]['dateFin'] = dol_time_plus_duree($contrats[$contratObj->id]['dateDebutContrat'], $lowestDuration, 'm');
            $contrats[$contratObj->id]['services'] = $services;
            $contrats[$contratObj->id]['prixTotalContrat'] = $prixTotalContrat;
        }
        return $contrats;
    }

    /**
     * Get the duration of a service by instancing a product
     */
    function getServiceDuration($fk_product) {
        if($fk_product >0 ) {
        $product = new Product($this->db);
        $product->fetch($fk_product);
        return $product->duration;
        }
        return 'unfetchable_product';
    }

    /**
     * Search all contract with that client and return it
     * @param type $id_client
     * @return \Contrat     array of contrat objects
     */
    function getDolContratsObject($id_client) {     // TODO change en utilisant get list of contract
        global $conf;

        $dolContratObject = array();

        if (isset($conf->global->MAIN_SIZE_SHORTLIST_LIMIT))
            $MAXLIST = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
        else
            $MAXLIST = 25;

        $sql = "SELECT s.rowid, c.rowid as id";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s, " . MAIN_DB_PREFIX . "contrat as c";
        $sql.= " WHERE c.fk_soc = s.rowid ";
        $sql.= " AND s.rowid = " . $id_client;
        $sql.= " AND c.entity = " . $conf->entity;
        $sql.= " ORDER BY c.datec DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num && $i < $MAXLIST) {
                $newContrat = new Contrat($this->db);
                $objp = $this->db->fetch_object($resql);
                $newContrat->fetch($objp->id);
                $dolContratObject[] = $newContrat;
                $i++;
            }
            $this->db->free($resql);
        } else {
            dol_print_error($this->db);
        }
        return $dolContratObject;
    }

}

//		$productstatic=new Product($db);
//
//        $usemargins=0;
//		if (! empty($conf->margin->enabled) && ! empty($object->element) && in_array($object->element,array('facture','propal','commande'))) $usemargins=1;
//
//        $var=false;
//
//		// Title line for service
//        $cursorline=1;
//        while ($cursorline <= $nbofservices)
//        {
//            print '<form name="update" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
//            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
//            print '<input type="hidden" name="action" value="updateline">';
//            print '<input type="hidden" name="elrowid" value="'.$object->lines[$cursorline-1]->id.'">';
//            print '<input type="hidden" name="idprod" value="'.(!empty($object->lines[$cursorline-1]->fk_product) ? $object->lines[$cursorline-1]->fk_product : 0).'">';
//            print '<input type="hidden" name="fournprice" value="'.(!empty($object->lines[$cursorline-1]->fk_fournprice) ? $object->lines[$cursorline-1]->fk_fournprice : 0).'">';
//
//            // Area with common detail of line
//            print '<table class="notopnoleftnoright allwidth tableforservicepart1" width="100%">';
//
//            $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.product_type, cd.description, cd.price_ht, cd.qty,";
//            $sql.= " cd.tva_tx, cd.vat_src_code, cd.remise_percent, cd.info_bits, cd.subprice, cd.multicurrency_subprice,";
//            $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
//            $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
//            $sql.= " cd.commentaire as comment, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht,";
//	        $sql.= " cd.fk_unit,";
//            $sql.= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
//            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
//            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
//            $sql.= " WHERE cd.rowid = ".$object->lines[$cursorline-1]->id;
//
//            $result = $db->query($sql);
//            if ($result)
//            {
//                $total = 0;
//
//                print '<tr class="liste_titre'.($cursorline?' liste_titre_add':'').'">';
//                print '<td>'.$langs->trans("ServiceNb",$cursorline).'</td>';
//                print '<td width="80" align="center">'.$langs->trans("VAT").'</td>';
//                print '<td width="80" align="right">'.$langs->trans("PriceUHT").'</td>';
//                if (!empty($conf->multicurrency->enabled)) {
//                    print '<td width="80" align="right">'.$langs->trans("PriceUHTCurrency").'</td>';
//                }
//                print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
//	            if ($conf->global->PRODUCT_USE_UNITS) print '<td width="30" align="left">'.$langs->trans("Unit").'</td>';
//                print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
//				if (! empty($conf->margin->enabled) && ! empty($conf->global->MARGIN_SHOW_ON_CONTRACT)) print '<td width="50" align="right">'.$langs->trans("BuyingPrice").'</td>';
//                print '<td width="30">&nbsp;</td>';
//                print "</tr>\n";
//
//                $objp = $db->fetch_object($result);
//
//                //
//
//                if ($action != 'editline' || GETPOST('rowid') != $objp->rowid)
//                {
//                    print '<tr '.$bcnd[$var].' valign="top">';
//                    // Label
//                    if ($objp->fk_product > 0)
//                    {
//                        print '<td>';
//                        $productstatic->id=$objp->fk_product;
//                        $productstatic->type=$objp->ptype;
//                        $productstatic->ref=$objp->pref;
//						$productstatic->entity=$objp->pentity;
//						$productstatic->label=$objp->plabel;
//						$text = $productstatic->getNomUrl(1,'',20);
//                        if ($objp->plabel)
//                        {
//                        	$text .= ' - ';
//                        	//$productstatic->ref=$objp->label;
//                        	//$text .= $productstatic->getNomUrl(0,'',16);
//                        	$text .= $objp->plabel;
//                        }
//                        $description = $objp->description;
//


    /*

      function __construct($db)
      function getNextNumRef($soc)
      function active_line($user, $line_id, $date, $date_end='', $comment='')
      function close_line($user, $line_id, $date_end, $comment='')
      function cloture($user)
      dol_print_error($this->db,'Error in cloture function');
      function validate($user, $force_number='', $notrigger=0)
      function reopen($user, $notrigger=0)
      function fetch($id,$ref='')
      function fetch_lines()
      function create($user)
      function delete($user)
      function update($user=null, $notrigger=0)
      function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $fk_fournprice=null, $pa_ht = 0,$array_options=0, $fk_unit = null)
      function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx=0.0, $localtax2tx=0.0, $date_debut_reel='', $date_fin_reel='', $price_base_type='HT', $info_bits=0, $fk_fournprice=null, $pa_ht = 0,$array_options=0, $fk_unit = null)
      function deleteline($idline,$user)
     *  @deprecated					This function will never be used. Status of a contract is status of its lines.
      function update_statut($user)
      function getLibStatut($mode)
      function LibStatut($statut,$mode)
      function getNomUrl($withpicto=0,$maxlength=0,$notooltip=0)
      function info($id)
      function array_detail($statut=-1)
      function getListOfContracts($option='all')
      function load_board($user,$mode)
      function load_state_board()
      function getIdBillingContact()
      function getIdServiceContact()
      function initAsSpecimen()
      public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
      public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
      function createFromClone($socid = 0, $notrigger=0) {
      function __construct($db)
      function getLibStatut($mode)
      function LibStatut($statut,$mode,$expired=-1,$moreatt='')
      function getNomUrl($withpicto=0,$maxlength=0)
      function fetch($id, $ref='')
      function update($user, $notrigger=0)
      function update_total()
      public function insert($notrigger = 0)
      function active_line($user, $date, $date_end = '', $comment = '')
      function close_line($user, $date_end, $comment = '')


      Nom : bimpcontratauto

      But :
      Dans un client un new onglet qui affichera le résumé des contrat en cours + possibilité de créer un contrat a la volé.

      Resumé du ou des contrat en cours.

      Total facturé
      Total payé (peu être différent)
      Total restant
      Total Contrat

      Création du contrat :
      Un peut comme dans le module précédent plusieurs choix possible par service :

      •CTR-ASSITANCE
      - Non
      - 12 moi
      - 24 moi
      - 36 moi

      CTR-PNEUMATIQUE
      - Non
      - 12 moi
      - 24 moi
      - 36 moi

      CTR-MAINTENANCE

      CTR_EXTENSION

      Blyyd Connect

      Puis un formulaire avec date de début
      Et un boutons créer.
      $tabProd = array(
      array("id"=>234, "name"=> "Maintenance", "Values"=>array("Non", 12,24)),
      array("id"=>235, "name"=> "Extenssion", "Values"=>array("Non", 12,24)));
     */    