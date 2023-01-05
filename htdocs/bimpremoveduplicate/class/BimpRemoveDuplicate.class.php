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
 * 	\file       htdocs/bimpremoveduplicate/class/BimpRemoveDuplicate.class.php
 * 	\ingroup    bimpremoveduplicate
 * 	\brief      Chose 
 */
//$path = dirname(__FILE__) . '/';
//require_once($path . '../../htdocs/main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

class BimpRemoveDuplicateCustomer {

    private $db;
    public $errors;
    public $nb_row;

    /**
     * 	Constructor
     *
     *  @param		DoliDB		$db     Database handler
     */
    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function getAllDuplicate($limit = 100, $detail = true) {
        $customers = array();

        $sql = 'SELECT CONCAT(nom, "_", zip) as group_key, COUNT(*) as counter';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' WHERE zip IS NOT NULL';
        $sql .= ' GROUP BY group_key';
        $sql .= ' HAVING counter > 1';

        $cont_soc = 0;
        $result = $this->db->query($sql);
        $this->nb_row = $result->num_rows;
        if ($result) {
            while ($obj = $this->db->fetch_object($result) and $cont_soc < $limit) {
                if ($detail = true)
                    $customers[] = $this->getDetail($obj->group_key);
                else
                    $customers[] = $obj;
                ++$cont_soc;
            }
        } else {
            $this->errors[] = "Aucun doublon n'est présent dans la base";
            return 0;
        }

        return $customers;
    }

    private function getDetail($group_key) {
        $details = array();

        $sql = 'SELECT rowid, nom, email, address, zip, town, phone, datec';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe';
        $sql .= ' WHERE CONCAT(nom, "_", zip)="' . $group_key . '"';

        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result)) {
                $obj->group_key = str_replace(' ', '_', $group_key);
                $obj->commerciaux = $this->getCommerciaux($obj->rowid);
                $details[] = $obj;
            }
        } else {
            $this->errors[] = "Aucun client a pour nom: $nom";
            return 0;
        }
        return $details;
    }

    private function getCommerciaux($id_soc) {
        $commerciaux = array();

        $sql = 'SELECT u.firstname as firstname, u.lastname as lastname';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux as sc';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON sc.fk_user=u.rowid';
        $sql .= ' WHERE sc.fk_soc=' . $id_soc;

        $result = $this->db->query($sql);
        while ($obj = $this->db->fetch_object($result)) {
            $commerciaux[] = $obj->firstname . ' ' . $obj->lastname;
        }
        return $commerciaux;
    }

    /**
     * 
     * @param type $src_to_dest array with multiple scr having multiple
     */
    public function mergeDuplicate($src_to_dest) {

        $success = array();

        global $langs, $user, $hookmanager;
        $langs->loadLangs(array("errors", "companies", "commercial", "bills", "banks", "users"));


        $soc_dest = new Societe($this->db);
        $soc_origin = new Societe($this->db);

        $action = 'confirm_merge';
        $hookmanager->initHooks(array('thirdpartycard', 'globalcard'));


        foreach ($src_to_dest as $src => $dest) {
            $result = $soc_dest->fetch((int) $dest);

            if ($result != 1) {
                $this->addErrorMerge($src, "Tier destination inconnue, id=" . $dest);
                continue;
            }

            $error = 0;
            $soc_origin_id = (int) $src;

            if ($soc_origin_id <= 0) {
                $langs->load('errors');
                $langs->load('companies');
                $this->addErrorMerge($src, $langs->trans('ErrorThirdPartyIdIsMandatory', $langs->trans('MergeOriginThirdparty')));
            } else {
                if (!$error && $soc_origin->fetch($soc_origin_id) < 1) {
                    $this->addErrorMerge($src, $langs->trans('ErrorRecordNotFound'));
                    $error++;
                }
                if (!$error) {
                    $this->db->begin();

                    // Recopy some data
                    $soc_dest->client = $soc_dest->client | $soc_origin->client;
                    $soc_dest->fournisseur = $soc_dest->fournisseur | $soc_origin->fournisseur;
                    $listofproperties = array(
                        'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'skype', 'url', 'barcode',
                        'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
                        'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
                        'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
                        'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
                        'model_pdf', 'fk_projet'
                    );
                    foreach ($listofproperties as $property) {
                        if (empty($soc_dest->$property))
                            $soc_dest->$property = $soc_origin->$property;
                    }

                    // Concat some data
                    $listofproperties = array(
                        'note_public', 'note_private'
                    );
                    foreach ($listofproperties as $property) {
                        $soc_dest->$property = dol_concatdesc($soc_dest->$property, $soc_origin->$property);
                    }

                    // Merge extrafields
                    if (is_array($soc_origin->array_options)) {
                        foreach ($soc_origin->array_options as $key => $val) {
                            if (empty($soc_dest->array_options[$key]))
                                $soc_dest->array_options[$key] = $val;
                        }
                    }

                    // Merge categories
                    $static_cat = new Categorie($this->db);

                    $custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
                    $custcats = $static_cat->containing($soc_dest->id, 'customer', 'id');
                    $custcats = BimpTools::merge_array($custcats, $custcats_ori);
                    $soc_dest->setCategories($custcats, 'customer');

                    $suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
                    $suppcats = $static_cat->containing($soc_dest->id, 'supplier', 'id');
                    $suppcats = BimpTools::merge_array($suppcats, $suppcats_ori);
                    $soc_dest->setCategories($suppcats, 'supplier');

                    // If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
                    if ($soc_origin->code_client == $soc_dest->code_client || $soc_origin->code_fournisseur == $soc_dest->code_fournisseur || $soc_origin->barcode == $soc_dest->barcode) {
                        dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
                        $soc_origin->code_client = '';
                        $soc_origin->code_fournisseur = '';
                        $soc_origin->barcode = '';
                        $soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
                    }

                    // Update
                    $soc_dest->update($soc_dest->id, $user, 0, 1, 1, 'merge');
                    if ($result < 0) {
                        $error++;
                    }

                    // Move links
                    if (!$error) {
                        $objects = array(
                            'Adherent' => '/adherents/class/adherent.class.php',
                            'Societe' => '/societe/class/societe.class.php',
                            //'Categorie' => '/categories/class/categorie.class.php',
                            'ActionComm' => '/comm/action/class/actioncomm.class.php',
                            'Propal' => '/comm/propal/class/propal.class.php',
                            'Commande' => '/commande/class/commande.class.php',
                            'Facture' => '/compta/facture/class/facture.class.php',
                            'FactureRec' => '/compta/facture/class/facture-rec.class.php',
                            'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
                            'Contact' => '/contact/class/contact.class.php',
                            'Contrat' => '/contrat/class/contrat.class.php',
                            'Expedition' => '/expedition/class/expedition.class.php',
                            'Fichinter' => '/fichinter/class/fichinter.class.php',
                            'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
                            'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
                            'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
                            'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
                            'Livraison' => '/livraison/class/livraison.class.php',
                            'Product' => '/product/class/product.class.php',
                            'Project' => '/projet/class/project.class.php',
                            'User' => '/user/class/user.class.php',
                        );

                        //First, all core objects must update their tables
                        foreach ($objects as $object_name => $object_file) {
                            require_once DOL_DOCUMENT_ROOT . $object_file;

                            if (!$error && !$object_name::replaceThirdparty($this->db, $soc_origin->id, $soc_dest->id)) {
                                $error++;
                                $this->addErrorMerge($src, $this->db->lasterror());
                            }
                        }
                    }

                    // External modules should update their ones too
                    if (!$error) {
                        $reshook = $hookmanager->executeHooks('replaceThirdparty', array(
                            'soc_origin' => $soc_origin->id,
                            'soc_dest' => $soc_dest->id
                                ), $soc_destUNEAUTREMPOUREVEITEBUG, $action);

                        if ($reshook < 0) {
                            $this->addErrorMerge($src, $hookmanager->error . ', ' . implode(",", $hookmanager->errors));
                            $error++;
                        }
                    }


                    if (!$error) {
                        $soc_dest->context = array('merge' => 1, 'mergefromid' => $soc_origin->id);

                        // Call trigger
                        $result = $soc_dest->call_trigger('COMPANY_MODIFY', $user);
                        if ($result < 0) {
                            $this->addErrorMerge($src, $soc_dest->error . ', ' . implode(",", $soc_dest->errors));
                            $error++;
                        }
                    }

                    if (!$error) {
                        //We finally remove the old thirdparty
                        if ($soc_origin->delete($soc_origin->id, $user) < 1) {
                            $error++;
                        }
                    }

                    if (!$error) {
                        $this->db->commit();
                        $success[$src] = $dest;
                    } else {
                        $langs->load("errors");
                        $this->addErrorMerge($src, $langs->trans('ErrorsThirdpartyMerge'));
                        $this->db->rollback();
                    }
                }
            }
        }
        return $success;
    }

    private function addErrorMerge($id_source, $error) {
        if (isset($this->errors[$id_source]))
            $this->errors[$id_source] .= html_entity_decode($error);
        else
            $this->errors[$id_source] = html_entity_decode($error);
    }

}
