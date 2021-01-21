<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";

class Equipment extends BimpObject
{

    public static $ref_properties = array('serial');
    public static $types = array(
        1  => 'Ordinateur',
        2  => 'Periph Mobile',
        10 => 'Accessoire',
        20 => 'License',
        30 => 'Serveur',
        13 => 'Matériel réseau',
        50 => 'Autre'
    );
    public static $typesPlace = array();
    public static $origin_elements = array(
        0 => '',
        1 => 'Fournisseur',
        2 => 'Client',
        3 => 'Commande Fournisseur'
    );
    protected $current_place = null;

    public function __construct($module, $object_name)
    {
        self::loadClass('bimpequipment', 'BE_Place');
        self::$typesPlace = BE_Place::$types;
        parent::__construct($module, $object_name);
        $this->iconeDef = "fa-laptop";
    }

    // Gestion des droits: 
    // Exeptionnelement les droit dans les isCre.. et isEdi... pour la creation des prod par les commerciaux

    public function canDelete()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return 1;
    }

    public function isCreatable($force_create = false, &$errors = array())
    {
        return $this->isEditable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        global $user;
        if ($force_edit || $user->rights->admin or $user->rights->produit->creer)
            return 1;
    }

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'validate':
                if ((int) $user->admin != 1) {
                    return 0;
                }
                return 1;
        }

        return parent::canEditField($field_name);
    }

    // Getters booléens: 

    public function isAvailable($id_entrepot = 0, &$errors = array(), $allowed = array(), $no_check = array())
    {
        // *** Valeurs possibles dans $allowed: ***
        // Pour chaque valeur: $allowed['id_xxx'] = array(id1, id2, ...);
        // id_reservation
        // id_vente (vente caisse)
        // id_sav
        // id_propal
        // id_facture 
        // id_commande_line_return (ligne de commande client (Bimp_CommandeLine) pour un retour. 
        // id_commande_fourn (retour)
        // id_commande_fourn_line (retour)
        // id_reception (retour)
        // id_vente_return (retour en caisse)

        if (!$this->isLoaded()) {
            $errors[] = 'ID de l\'équipement absent';
            return 0;
        }

        // Check de la présence dans l\'entrepôt. 
        if ((int) $id_entrepot) {
            if (!$this->isInEntrepot($id_entrepot, $errors)) {
                return 0;
            }
        }

        // Check des réservations en cours: 
        $reservations = $this->getReservationsList();
        if (count($reservations)) {
            if (!isset($allowed['id_reservation']) || !(int) $allowed['id_reservation'] || !in_array((int) $allowed['id_reservation'], $reservations)) {
                $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' est réservé - ' . (isset($allowed['id_reservation']) ? ' Allowed : ' . $allowed['id_reservation'] . ' - LISTE: ' . print_r($reservations, 1) : '');
            }
        }

        // Check des ventes caisse en cours (Brouillons): 
        $this->isNotInVenteBrouillon($errors, isset($allowed['id_vente']) ? (int) $allowed['id_vente'] : 0);

        // Check des SAV: 
        if (!in_array('sav', $no_check)) {
            $filters = array(
                'status'       => array(
                    'operator' => '<',
                    'value'    => 999
                ),
                'id_equipment' => (int) $this->id
            );

            if (isset($allowed['id_sav']) && (int) $allowed['id_sav']) {
                $filters['id'] = array(
                    'operator' => '!=',
                    'value'    => (int) $allowed['id_sav']
                );
            }
            $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', $filters, true);

            if (BimpObject::objectLoaded($sav)) {
                $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' est en cours de traitement dans le SAV ' . $sav->getNomUrl(0, 1, 1, 'default');
            }
        }

        // Check des ajouts aux devis SAV non validés: 
        $sql = 'SELECT sav.id FROM ' . MAIN_DB_PREFIX . 'bs_sav sav';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'propal p on sav.id_propal = p.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav_propal_line l ON l.id_obj = sav.id_propal ';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'object_line_equipment leq ON (leq.id_object_line = l.id AND leq.object_type = \'sav_propal\') ';
        $sql .= ' WHERE leq.id_equipment = ' . (int) $this->id;
        $sql .= ' AND sav.status < 6';

        if (isset($allowed['id_propal']) && (int) $allowed['id_propal']) {
            $sql .= ' AND sav.id_propal != ' . (int) $allowed['id_propal'];
        }

        $rows = $this->db->executeS($sql, 'array');
        if (!is_null($rows) && !empty($rows)) {
            foreach ($rows as $r) {
                $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $r['id']);
                if (BimpObject::objectLoaded($sav)) {
                    $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' a été ajouté à un devis SAV non terminé: ' . $sav->getNomUrl(0, 1, 1, 'default');
                }
            }
        }

        // Check des ajouts aux factures non validées. 
//        $sql = 'SELECT f.rowid  as id FROM ' . MAIN_DB_PREFIX . 'facture f';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bimp_facture_line l ON l.id_obj = f.rowid ';
//        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'object_line_equipment leq ON (leq.id_object_line = l.id AND leq.object_type = \'facture\') ';
//        $sql .= ' WHERE leq.id_equipment = ' . (int) $this->id;
//        $sql .= ' AND f.fk_statut = 0';
//
//        if (isset($allowed['id_facture']) && (int) $allowed['id_facture']) {
//            $sql .= ' AND f.rowid != ' . (int) $allowed['id_facture'];
//        }
//
//        $rows = $this->db->executeS($sql, 'array');
//        if (!is_null($rows) && !empty($rows)) {
//            foreach ($rows as $r) {
//                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id']);
//                if (BimpObject::objectLoaded($facture)) {
//                    $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' a été ajouté à une facture non validée ' . $facture->getNomUrl(0, 1, 1, 'full');
//                }
//            }
//        }
//        
        // Check retour en commande client: 
        if ((int) $this->getData('id_commande_line_return')) {
            if (!isset($allowed['id_commande_line_return']) || ((int) $allowed['id_commande_line_return'] !== (int) $this->getData('id_commande_line_return'))) {
                $line_check = false;
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('id_commande_line_return'));
                if (BimpObject::objectLoaded($line) && $line->getFullQty() < 0) {
                    // On verifie que l'équipement est bien attribué en retour à la ligne de commande: 
                    $equipments_returns = $line->getData('equipments_returned');
                    if (array_key_exists((int) $this->id, $equipments_returns)) {
                        $line_check = true;
                        $commande = $line->getParentInstance();
                        if (BimpObject::objectLoaded($commande)) {
                            $msg = 'Un retour est en cours pour l\'équipement ' . $this->getNomUrl(0, 1, 1, 'default');
                            $msg .= ' dans la commande client ' . $commande->getNomUrl(0, 1, 1, 'full') . " a la ligne " . $this->getData('id_commande_line_return');
                            $errors[] = $msg;
                        }
                    }
                }

                if (!$line_check) {
                    $this->updateField('id_commande_line_return', 0);
                }
            }
        }

        // Check retour fournisseur: 
        $id_product = (int) $this->getData('id_product');
        if ($id_product) {
            $sql = 'SELECT l.id FROM ' . MAIN_DB_PREFIX . 'bimp_commande_fourn_line l';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet det ON l.id_line = det.rowid';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur cf ON cf.rowid = l.id_obj';
            if ((int) $id_entrepot) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields cfef ON cfef.fk_object = cf.rowid';
            }
            $sql .= ' WHERE cf.fk_statut BETWEEN 3 AND 4';
            $sql .= ' AND det.fk_product = ' . (int) $id_product;
            $sql .= ' AND det.qty < 0';

            if ($id_entrepot) {
                $sql .= ' AND cfef.entrepot = ' . (int) $id_entrepot;
            }

            if (isset($allowed['id_commande_fourn']) && (int) $allowed['id_commande_fourn']) {
                $sql .= ' AND cf.rowid != ' . (int) $allowed['id_commande_fourn'];
            }

            if (isset($allowed['id_commande_fourn_line']) && (int) $allowed['id_commande_fourn_line']) {
                $sql .= ' AND l.id != ' . (int) $allowed['id_commande_fourn_line'];
            }

            $rows = $this->db->executeS($sql, 'array');
            if (!is_null($rows) && !empty($rows)) {
                foreach ($rows as $r) {
                    $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', (int) $r['id']);
                    if (BimpObject::objectLoaded($line)) {
                        $id_reception = (int) $line->getReturnedEquipmentIdReception((int) $this->id);
                        if ($id_reception) {
                            if (isset($allowed['id_reception']) && (int) $allowed['id_reception'] === $id_reception) {
                                continue;
                            }

                            $commande = $line->getParentInstance();

                            if (BimpObject::objectLoaded($commande)) {
                                $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' a été ajouté comme retour dans la commande fournisseur ' . $commande->getNomUrl(0, 1, 1, 'full');
                            }
                        }
                    }
                }
            }
        }


        // Check des retours caisse: 
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcaisse', 'BC_Vente');

            $sql = 'SELECT v.id FROM ' . MAIN_DB_PREFIX . 'bc_vente v ';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bc_vente_return r ON r.id_vente = v.id';
            $sql .= ' WHERE r.id_equipment = ' . (int) $this->id . ' AND v.status = ' . BC_Vente::BC_VENTE_BROUILLON;

            if (isset($allowed['id_vente_return']) && (int) $allowed['id_vente_return']) {
                $sql .= ' AND v.id != ' . (int) $allowed['id_vente_return'];
            }

            $rows = $this->db->executeS($sql, 'array');
            if (!is_null($rows) && !empty($rows)) {
                foreach ($rows as $r) {
                    $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' a été ajouté dans un retour caisse en cours (vente #' . $r['id'] . ')';
                }
            }
        }

        return (count($errors) ? 0 : 1);
    }

    public function isInEntrepot($id_entrepot = 0, &$errors = array())
    {
        if (!$this->isLoaded()) {
            $errors[] = 'ID de l\'équipement absent';
            return 0;
        }

        $place = $this->getCurrentPlace();
        if ((int) $place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
            if (!$id_entrepot || (int) $place->getData('id_entrepot') === (int) $id_entrepot) {
                return 1;
            }
        }

        if ($id_entrepot) {
            $entrepot = BimpCache::getDolObjectInstance($id_entrepot, 'product/stock', 'entrepot');
            if (BimpObject::objectLoaded($entrepot)) {
                $entrepot_label = BimpObject::getInstanceNomUrlWithIcons($entrepot);
            } else {
                $entrepot_label = 'd\'ID ' . $id_entrepot;
            }
            $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' n\'est pas présent dans l\'entrepôt ' . $entrepot_label;
        } else {
            $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' n\'est présent dans aucun entrepôt';
        }

        return 0;
    }

    public function isNotInVenteBrouillon(&$errors = array(), $id_vente_allowed = 0)
    {
        $check = 1;
        if ($this->isLoaded()) {
            BimpObject::loadClass('bimpcaisse', 'BC_Vente');

            $sql = 'SELECT v.id FROM ' . MAIN_DB_PREFIX . 'bc_vente_article a ';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bc_vente v ON a.id_vente = v.id';
            $sql .= ' WHERE a.id_equipment = ' . (int) $this->id . ' AND v.status = ' . BC_Vente::BC_VENTE_BROUILLON;

            if ((int) $id_vente_allowed) {
                $sql .= ' AND v.id != ' . (int) $id_vente_allowed;
            }

            $rows = $this->db->executeS($sql, 'array');
            if (!is_null($rows) && !empty($rows)) {
                foreach ($rows as $r) {
                    $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' a été ajouté dans une vente caisse en cours (vente #' . $r['id'] . ')';
                    $check = 0;
                }
            }
        }

        return $check;
    }

    public function isSold()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_facture')) {
                return 1;
            }
        }

        return 0;
    }

    public function equipmentExists($serial, $id_product)
    {
        if (is_null($id_product)) {
            $id_product = 0;
        }

        $value = $this->db->getValue($this->getTable(), 'id', '`serial` = \'' . $serial . '\' AND `id_product` = ' . (int) $id_product);
        if (!is_null($value) && $value) {
            return 1;
        }

        return 0;
    }

    public function showProductInput()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('id_product')) {
                return 0;
            }
        }

        return 1;
    }

    // Getters params: 

    public function getDefaultListExtraBtn()
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Etiquette',
            'icon'    => 'fas_sticky-note',
            'onclick' => $this->getJsActionOnclick('generateEtiquette')
        );

        return $buttons;
    }

    public function getPackageListExtraBtn()
    {
        $buttons = $this->getDefaultListExtraBtn();

        if ($this->isLoaded()) {
            $package = $this->getChildObject('package');
            if (BimpObject::objectLoaded($package)) {
                if ($package->isActionAllowed('moveEquipment') && $package->canSetAction('moveEquipment')) {
                    $buttons[] = array(
                        'label'   => 'Changer de package',
                        'icon'    => 'arrow-circle-right',
                        'onclick' => $package->getJsActionOnclick('moveEquipment', array(
                            'id_equipment' => (int) $this->id
                                ), array(
                            'form_name'   => 'move_equipment',
                            'no_triggers' => true
                        ))
                    );
                }
                if ($package->isActionAllowed('removeEquipment') && $package->canSetAction('removeEquipment')) {
                    $buttons[] = array(
                        'label'   => 'Retirer',
                        'icon'    => 'fas_trash-alt',
                        'onclick' => $package->getJsActionOnclick('removeEquipment', array(
                            'id_equipment' => (int) $this->id
                                ), array(
                            'form_name'   => 'remove_equipment',
                            'no_triggers' => true
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    // Getters filters: 

    public function getProductSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $where = 'prod.ref LIKE \'%' . (string) $value . '%\' OR prod.label LIKE \'%' . (string) $value . '%\' OR prod.barcode = \'' . (string) $value . '\'';

        if (preg_match('/^\d+$/', (string) $value)) {
            $where .= ' OR prod.rowid = ' . $value;
        }

        $filters['or_product'] = array(
            'or' => array(
                $main_alias . '.product_label' => array(
                    'part_type' => 'middle',
                    'part'      => $value
                ),
                $main_alias . '.id_product'    => array(
                    'in' => 'SELECT prod.rowid FROM ' . MAIN_DB_PREFIX . 'product prod WHERE ' . $where
                )
            )
        );
    }

    public function getPlaceSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $joins['place'] = array(
                'table' => 'be_equipment_place',
                'alias' => 'place',
                'on'    => 'place.id_equipment = ' . $main_alias . '.id'
            );
            $joins['place_entrepot'] = array(
                'table' => 'entrepot',
                'alias' => 'place_entrepot',
                'on'    => 'place_entrepot.rowid = place.id_entrepot'
            );
            $joins['place_user'] = array(
                'table' => 'user',
                'alias' => 'place_user',
                'on'    => 'place_user.rowid = place.id_user'
            );
            $joins['place_client'] = array(
                'table' => 'societe',
                'alias' => 'place_client',
                'on'    => 'place_client.rowid = place.id_client'
            );

            $filters['place.position'] = 1;
            $filters['or_place'] = array(
                'or' => array(
                    'place.place_name'         => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_entrepot.lieu'      => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_entrepot.ref'       => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_user.firstname'     => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_user.lastname'      => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_client.nom'         => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                    'place_client.code_client' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    ),
                )
            );
        }
    }

    public function getPlace2SearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        if ((string) $value) {
            $joins['placeType'] = array(
                'table' => 'be_equipment_place',
                'alias' => 'placeType',
                'on'    => 'placeType.id_equipment = ' . $main_alias . '.id'
            );

            $filters['placeType.position'] = 1;
            $filters['placeType.type'] = $value;
        }
    }

    public function getReservedSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a')
    {
        $sql = '(SELECT COUNT(reservation.id) FROM ' . MAIN_DB_PREFIX . 'br_reservation reservation WHERE reservation.id_equipment = ' . $main_alias . '.id';
        $sql .= ' AND reservation.status < 300 AND reservation.status >= 200)';

        if ((int) $value > 0) {
            $filters[$sql] = array(
                'operator' => '>',
                'value'    => 0
            );
        } else {
            $filters[$sql] = 0;
        }
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'in_package':
                // Bouton Exclure désactivé
                if (empty($values) || (in_array(0, $values) && in_array(1, $values))) {
                    // On ne filtre pas...
                    break;
                }
                if (in_array(0, $values)) {
                    $filters['a.id_package'] = 0;
                }
                if (in_array(1, $values)) {
                    $filters['a.id_package'] = array(
                        'operator' => '>',
                        'value'    => 0
                    );
                }
                break;

            case 'place_date_end':
                // Bouton Exclure désactivé
                $joins['places'] = array(
                    'table' => 'be_equipment_place',
                    'on'    => 'places.id_equipment = a.id',
                    'alias' => 'places'
                );
                $joins['next_place'] = array(
                    'table' => 'be_equipment_place',
                    'on'    => 'next_place.id_equipment = a.id',
                    'alias' => 'next_place'
                );
                $filters['next_place_position'] = array('custom' => 'next_place.position = (places.position - 1)');

                $or_field = array();
                foreach ($values as $value) {
                    $or_field[] = BC_Filter::getRangeSqlFilter($value, $errors);
                }

                if (!empty($or_field)) {
                    $filters['next_place.date'] = array(
                        'or_field' => $or_field
                    );
                }
                break;
        }

        parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters array: 

    public function getHasReservationsArray()
    {
        return array(
            '' => '',
            1  => 'OUI',
            0  => 'NON'
        );
    }

    public function getContratsArray()
    {
        $id_soc = isset($this->data['id_soc']) ? $this->data['id_soc'] : 0;

        if (!$id_soc) {
            return array(
                0 => '<span class="warning">Aucun contrat</span>'
            );
        }

        $rows = $this->db->getRows('contrat', '`fk_soc` = ' . (int) $id_soc, null, 'array', array('rowid', 'ref'));

        $return = array(
            0 => '<span class="warning">Aucun contrat</span>',
        );

        if (!is_nan($rows)) {
            foreach ($rows as $r) {
                $return[(int) $r['rowid']] = $r['ref'];
            }
        }

        return $return;
    }

    public static function getAvailableEquipmentsArray($id_entrepot = null, $id_product = null)
    {
        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');

        $joins = array(
            'eq' => array(
                'table' => 'be_equipment',
                'alias' => 'eq',
                'on'    => 'a.id_equipment = eq.id'
            )
        );

        $filters = array(
            'a.position' => 1
        );

        if (!is_null($id_entrepot)) {
            $filters['a.type'] = BE_Place::BE_PLACE_ENTREPOT;
            $filters['a.id_entrepot'] = $id_entrepot;
        }

        if (!is_null($id_product)) {
            $filters['eq.id_product'] = $id_product;
        }

        $list = $place->getList($filters, null, null, 'id', 'asc', 'array', array('a.id_equipment'), $joins);

        $equipments = array();

        if (!is_null($list)) {
            foreach ($list as $item) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item['id_equipment']);
                if (BimpObject::objectLoaded($equipment)) {
                    if ($equipment->isAvailable((int) $id_entrepot)) {

                        $equipments[(int) $equipment->id] = $equipment->displaySerialImei();
                    }
                }
            }
        }

        return $equipments;
    }

    // Getters données: 

    public function getName($withGeneric = true)
    {
        if ($this->isLoaded()) {
            $name = (string) $this->getData('serial');
            if ($name) {
                return $name;
            }
            return 'Equipement #' . $this->id;
        }

        return '';
    }

    public function getRef($withGeneric = true)
    {
        return $this->getData("serial");
    }

    public function getProductLabel($with_ref = false)
    {
        $product = $this->getChildObject('product');

        if (BimpObject::objectLoaded($product)) {
            $label = '';

            if ($with_ref) {
                $label = $product->ref . ' - ';
            }
            $label .= $product->label;

            return $label;
        }

        return (string) $this->getData('product_label');
    }

    public function getReservationsList()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $rows = $reservation->getList(array(
            'id_equipment' => (int) $this->id,
            'status'       => array(
                'and' => array(
                    array(
                        'operator' => '<',
                        'value'    => 300
                    ),
                    array(
                        'operator' => '>=',
                        'value'    => 200
                    )
                )
            )
                ), null, null, 'id', 'desc', 'array', array('id'));

        $reservations = array();

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $reservations[] = (int) $r['id'];
            }
        }

        return $reservations;
    }

    public function getCurrentPlace()
    {
        if ($this->isLoaded()) {
            if (is_null($this->current_place)) {
                $place = BimpObject::getInstance($this->module, 'BE_Place');
                $items = $place->getList(array(
                    'id_equipment' => $this->id,
                    'position'     => 1
                        ), 1, 1, 'id', 'desc', 'array', array(
                    'id'
                ));

                if (isset($items[0])) {
                    $place = BimpCache::getBimpObjectInstance($this->module, 'BE_Place', (int) $items[0]['id']);
                    if ($place->isLoaded()) {
                        $this->current_place = $place;
                    } else {
                        $this->current_place = null;
                    }
                }
            }
        }

        return $this->current_place;
    }

    public function getOriginIdElementInput()
    {
        $element = (int) $this->getData('origin_element');
        if ($element) {
            switch ($element) {
                case 1:
                    return BimpInput::renderInput('search_societe', 'origin_id_element', $this->getData('origin_id_element'), array(
                                'type' => 'supplier'
                    ));

                case 2:
                    return BimpInput::renderInput('search_societe', 'origin_id_element', $this->getData('origin_id_element'), array(
                                'type' => 'customer'
                    ));

                case 3:
                    return BimpInput::renderInput('text', 'origin_id_element', $this->getData('origin_id_element'), array(
                                'data' => array(
                                    'data_type' => 'number',
                                    'decimals'  => 0,
                                    'unsigned'  => 1
                                )
                    ));
            }
        }

        return '';
    }

    public function getPlaceByDate($date, &$errors)
    {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        if ($dt == false or array_sum($dt::getLastErrors())) {
            $date .= ' 00:00:00';
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
            if ($dt == false or array_sum($dt::getLastErrors()))
                $errors[] = "Equipement::getPlaceByDate() Format de date incorrect " . $date;
        }

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_equipment=' . (int) $this->id;
        $sql .= ' AND   date <= "' . $date . '"';
        $sql .= ' ORDER BY id DESC';

        $rows = $this->db->executeS($sql);

        if (!is_null($rows) && count($rows)) {
            return (int) $rows[0]->id;
        }

        return 0;
    }

    // Affichage: 

    public function displayOriginElement()
    {
        if ($this->isLoaded()) {
            $id_element = (int) $this->getData('origin_id_element');
            if (!$id_element) {
                return '';
            }

            switch ((int) $this->getData('origin_element')) {
                case 1:
                case 2:
                    global $db;
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $id_element);
                    if (BimpObject::objectLoaded($soc)) {
                        return $soc->getLink();
                    }
                    return BimpRender::renderAlerts('La société d\'ID ' . $id_element . ' n\'existe pas');

                case 3:
                    global $db;
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_element);
                    if (BimpObject::objectLoaded($comm)) {
                        return $comm->getLink();
                    }
                    return BimpRender::renderAlerts('La commande fournisseur d\'ID ' . $id_element . ' n\'existe pas');
            }
        }

        return '';
    }

    public function displayCurrentPlace()
    {
        $place = $this->getCurrentPlace();
        if (!is_null($place) && $place->isLoaded()) {
            return $place->displayPlace();
        }

        return '';
    }

    public function displayCurrentPlaceType()
    {
        $place = $this->getCurrentPlace();
        if (!is_null($place) && $place->isLoaded()) {
            return $place->displayData("type");
        }

        return '';
    }

    public function displayReserved()
    {
        if (count($this->getReservationsList())) {
            return '<span class="success">OUI</span>';
        }

        return '</span class="danger">NON</span>';
    }

    public function displayProduct($display_name = 'default', $no_html = false, $with_label = false)
    {
        if ((int) $this->getData('id_product')) {
            $html = $this->displayData('id_product', $display_name, ($no_html ? 0 : 1), $no_html);
            if ($with_label) {
                $product = $this->getChildObject('product');
                if (BimpObject::objectLoaded($product)) {
                    if ($no_html) {
                        $html .= "\n";
                    } else {
                        $html .= '<br/>';
                    }
                    $html .= $product->label;
                }
            }

            return $html;
        }

        return $this->displayData('product_label', 'default', ($no_html ? 0 : 1), $no_html);
    }

    public function defaultDisplayContratsItem($id_contrat)
    {
        $contrat = BimpObject::getDolInstance('contrat');
        if ($contrat->fetch((int) $id_contrat) > 0) {
            $label = $contrat->ref;
            if (isset($contrat->societe) && is_a($contrat->societe, 'Societe')) {
                $label .= ' (client: ' . $contrat->societe->nom . ')';
            } elseif (isset($contrat->socid) && $contrat->socid) {
                global $db;
                $client = new Societe($db);
                if ($client->fetch($contrat->socid) > 0) {
                    $label .= ' (client: ' . $client->nom . ')';
                }
                unset($client);
            }
            unset($contrat);
            return $label;
        }
        return BimpRender::renderAlerts('Le contrat d\'ID ' . $id_contrat . ' semble ne plus exister');
    }

    public function displayUnavailable()
    {
        if ($this->isLoaded()) {
            return 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' n\'est pas disponible';
        }

        return 'ID de l\'équipement absent';
    }

    public function displayReturnUnavailable()
    {
        if ($this->isLoaded()) {
            return 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' n\'est pas disponible (Un retour est probablement déjà en cours)';
        }

        return 'ID de l\'équipement absent';
    }

    public function displayAvailability($id_entrepot = 0, $allowed = array())
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $this->isAvailable($id_entrepot, $errors, $allowed);
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'warning');
        } else {
            $place = $this->getCurrentPlace();
            if (BimpObject::objectLoaded($place)) {
                if ($place->getData('type') == BE_Place::BE_PLACE_ENTREPOT)
                    $html .= BimpRender::renderAlerts('Equipement disponible en stock', 'success');
                elseif ($place->getData('type') == BE_Place::BE_PLACE_CLIENT)
                    $html .= BimpRender::renderAlerts('Equipement disponible pour un retour', 'success');
                else
                    $html .= BimpRender::renderAlerts('Equipement non reservé', 'success');
            } else {
                $html .= BimpRender::renderAlerts('Aucun emplacement défini', 'warning');
            }
        }

        return $html;
    }

    public function displayOldSn()
    {
        $tabT = array();
        $sql = $this->db->db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "bimp_gsx_repair` WHERE `new_serial` LIKE '" . $this->getData('serial') . "'");
        while ($ln = $this->db->db->fetch_object($sql)) {
            $tabT[] = $ln->serial;
        }
        return implode(" ", $tabT);
    }

    public function displaySerialImei()
    {
        $label = $this->getRef();
        $imei = $this->getData('imei');
        if ($imei != '' && $imei != 'n/a')
            $label .= ' (' . $imei . ')';

        return $label;
    }

    public function displayPackage($display_name = 'default', $with_remove_button = true, $no_html = false, $display_input_value = true)
    {
        if ((int) $this->getData(('id_package'))) {
            $html = $this->displayData('id_package', $display_name, $display_input_value, $no_html);

            if ($this->isLoaded() && $with_remove_button) {
                $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $this->getData('id_package'));
                if (BimpObject::objectLoaded($package)) {
                    $html .= '<div style="margin-top: 15px; text-align: right">';

                    if ($package->isActionAllowed('moveEquipment') && $package->canSetAction('moveEquipment')) {
                        $onclick = $package->getJsActionOnclick('moveEquipment', array(
                            'id_equipment' => (int) $this->id
                                ), array(
                            'form_name'        => 'move_equipment',
                            'success_callback' => 'function() {triggerObjectChange(\'bimpequipment\', \'Equipment\', ' . (int) $this->id . ')}'
                        ));
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_arrow-circle-right', 'iconLeft') . 'Changer de package';
                        $html .= '</span>';
                    }
                    if ($package->isActionAllowed('removeEquipment') && $package->canSetAction('removeEquipment')) {
                        $onclick = $package->getJsActionOnclick('removeEquipment', array(
                            'id_equipment' => (int) $this->id
                                ), array(
                            'form_name'        => 'remove_equipment',
                            'success_callback' => 'function() {triggerObjectChange(\'bimpequipment\', \'Equipment\', ' . (int) $this->id . ')}'
                        ));
                        $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Retirer du package';
                        $html .= '</span>';
                    }

                    $html .= '</div>';
                }
            }

            return $html;
        }

        return '';
    }

    // Traitements: 

    public function onNewPlace()
    {
        if ($this->isLoaded()) {
            $place = BimpObject::getInstance($this->module, 'BE_Place');
            $items = $place->getList(array(
                'id_equipment' => $this->id
                    ), 2, 1, 'id', 'desc', 'array', array(
                'id'
            ));

            $new_place = BimpCache::getBimpObjectInstance($this->module, 'BE_Place', $items[0]['id']);

            if (!(string) $this->getData('ref_immo') && in_array((int) $new_place->getData('type'), BE_Place::$immos_types)) {
                $ref_immo = BimpTools::getNextRef('be_equipment', 'ref_immo', 'IMMO{AA}{MM}-');
                if (!(string) $ref_immo) {
                    $ref_immo = 'IMMO-1';
                }

                $this->updateField('ref_immo', $ref_immo);
                $this->updateField('date_immo', date('Y-m-d'));
            }

            $product = $this->getChildObject('bimp_product');

            if (!defined('DONT_CHECK_SERIAL') && BimpObject::objectLoaded($product)) {
                $origin = $new_place->getData('origin');
                $id_origin = (int) $new_place->getData('id_origin');

                if (!$origin || !(int) $id_origin) {
                    global $user;
                    $origin = 'user';
                    $id_origin = (int) $user->id;
                }

                $codemove = $new_place->getData('code_mvt');
                if (is_null($codemove) || !$codemove) {
                    $codemove = 'EQ' . (int) $this->id . '_PLACE' . (int) $new_place->id;
                }

                $new_place_infos = $new_place->getData('infos');
                $label = ($new_place_infos ? $new_place_infos . ' - ' : '') . 'Produit "' . $product->getRef() . '" - serial: "' . $this->getData('serial') . '"';

                if (isset($items[1])) {
                    $prev_place = BimpCache::getBimpObjectInstance($this->module, 'BE_Place', $items[1]['id']);
                    if (BimpObject::objectLoaded($prev_place)) {
                        if ((int) $prev_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT && (int) $prev_place->getData('id_entrepot')) {
                            $product->correctStocks((int) $prev_place->getData('id_entrepot'), 1, Bimp_Product::STOCK_OUT, $codemove, $label . ' - Emplacement de destination: ' . $new_place->getPlaceName(), $origin, $id_origin);
                        }
                    }
                }

                if ((int) $new_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT && (int) $new_place->getData('id_entrepot')) {
                    if (BimpObject::objectLoaded($prev_place)) {
                        $label .= ' - Emplacement d\'origine: ' . $prev_place->getPlaceName();
                    }
                    $product->correctStocks((int) $new_place->getData('id_entrepot'), 1, Bimp_Product::STOCK_IN, $codemove, $label, $origin, $id_origin);
                }
            }

            $this->current_place = $new_place;
        }
    }

    public function getInfoCard()
    {
        $html = '';
        $place = $this->getCurrentPlace();
        if (BimpObject::objectLoaded($place) && $place->getData("type") == $place::BE_PLACE_VOL) {
            $html .= BimpRender::renderAlerts("Cet équipement est en Vol");
        }

        return $html;
    }

    public function gsxLookup($serial, &$errors)
    {
        if (preg_match('/^S([A-Z0-9]{11,12})$/', $serial, $matches)) {
            $serial = $matches[1];
        } elseif (preg_match('/^S[0-9]{15,16}$/', $serial, $matches)) {
            $serial = $matches[1];
        }

        if (preg_match('/^[0-9]{15,16}$/', $serial)) {
            $isIphone = true;
        } else {
            $isIphone = false;
        }

        $errors = array();

        $result = array(
            'product_label'     => '',
            'date_purchase'     => '',
            'date_warranty_end' => '',
            'warranty_type'     => '',
            'warning'           => ''
        );

        $use_gsx_v2 = (int) BimpCore::getConf('use_gsx_v2');

        if (!$use_gsx_v2) { // V2 => gsxController::gsxGetEquipmentInfos(). 
            $gsx = new GSX($isIphone);
            if (!$gsx->connect) {
                $errors[] = BimpTools::getMsgFromArray($gsx->errors['init'], 'Echec de la connexion GSX');
            } else {
                $response = $gsx->lookup($serial);

                if (isset($response) && count($response)) {
                    if (isset($response['ResponseArray']) && count($response['ResponseArray'])) {
                        if (isset($response['ResponseArray']['responseData']) && count($response['ResponseArray']['responseData'])) {
                            $data = $response['ResponseArray']['responseData'];
                            if (isset($data['productDescription']) && $data['productDescription']) {
                                $result['product_label'] = $data['productDescription'];
                            }
                            if (isset($data['estimatedPurchaseDate']) && $data['estimatedPurchaseDate']) {
                                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $data['estimatedPurchaseDate'], $matches)) {
                                    $result['date_purchase'] = '20' . $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                                }
                            }
                            if (isset($data['coverageEndDate']) && $data['coverageEndDate']) {
                                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $data['coverageEndDate'], $matches)) {
                                    $result['date_warranty_end'] = '20' . $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                                }
                            }
                            if (isset($data['warrantyStatus']) && $data['warrantyStatus']) {
                                $result['warranty_type'] = $data['warrantyStatus'];
                            }
                            if (isset($data['activationLockStatus']) && $data['activationLockStatus']) {
                                $result['warning'] = $data['activationLockStatus'];
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function findEquipments($serial = '', $id_client = 0)
    {
        global $db;
        $bdb = new BimpDb($db);
        BimpObject::loadClass('bimpequipment', 'BE_Place');

        $joins = array();
        $filters = array();

        if ((string) $serial) {
            $filters['or_serial'] = array(
                'or' => array(
                    'a.serial'              => $db->escape($serial),
                    'concat("S", a.serial)' => $db->escape($serial)
                )
            );
        }

        if ((int) $id_client) {
            $joins[] = array(
                'table' => 'be_equipment_place',
                'alias' => 'place',
                'on'    => 'place.id_equipment = a.id'
            );

            $filters['place.position'] = 1;
            $filters['place.type'] = BE_Place::BE_PLACE_CLIENT;
            $filters['place.id_client'] = (int) $id_client;
        }

        $sql = BimpTools::getSqlSelect(array('DISTINCT(a.id)'));
        $sql .= BimpTools::getSqlFrom('be_equipment', $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $rows = $bdb->executeS($sql, 'array');

        $equipments = array();

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $equipments[] = (int) $r['id'];
            }
        }

        return $equipments;
    }

    public function checkAvailability($id_entrepot = 0, $allowed_id_reservation = 0)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'Equipement invalide';
        } else {
            $reservations = $this->getReservationsList();
            if (count($reservations)) {
                if (!$allowed_id_reservation || ($allowed_id_reservation && !in_array($allowed_id_reservation, $reservations))) {
                    $errors[] = 'L\'équipement ' . $this->getData('serial') . ' est réservé';
                }
            }

            if ($id_entrepot) {
                $place = $this->getCurrentPlace();
                if (BimpObject::objectLoaded($place)) {
                    if ((int) $place->getData('type') !== BE_Place::BE_PLACE_ENTREPOT ||
                            (int) $place->getData('id_entrepot') !== $id_entrepot) {
                        BimpTools::loadDolClass('product/stock', 'entrepot');
                        $entrepot = new Entrepot($this->db->db);
                        $entrepot->fetch($id_entrepot);
                        if (BimpObject::objectLoaded($entrepot)) {
                            $label = '"' . $entrepot->libelle . '"';
                        } else {
                            $label = 'sélectionné';
                        }
                        $errors[] = 'L\'équipement ' . $this->getData('serial') . ' n\'est pas disponible dans l\'entrepot ' . $label;
                    }
                } else {
                    $errors[] = 'Aucun emplacement défini pour l\'équipement "' . $this->getData('serial') . '"';
                }
            }
        }
        return $errors;
    }

    public function checkPlaceForReturn($id_client = 0)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $place = $this->getCurrentPlace();
            if (!BimpObject::objectLoaded($place)) {
                $errors[] = 'Aucun emplacement enregistré pour l\'équipement ' . $this->getNomUrl(0, 1, 1, 'default');
            } elseif ((int) $id_client) {
                if ((int) $place->getData('type') !== BE_Place::BE_PLACE_CLIENT || (int) $place->getData('id_client') !== (int) $id_client) {
                    $msg = 'L\'emplacement actuel de l\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' ne correspond pas au client sélectionné.<br/>';
                    $msg .= 'Emplacement actuel: ' . $place->displayPlace();
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    public function removeInventaire($idI)
    {
        $line = BimpObject::getInstance('bimplogistique', 'InventoryLine');
        $rows = $line->getList(array(
            'fk_equipment' => (int) $this->id,
            'fk_inventory' => $idI
                ), null, null, 'id', 'desc', 'array', array('id'));


        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimplogistique', 'InventoryLine', $r['id']);
                $obj->delete();
            }
        }
    }

    public function moveToPlaceType($type, $idI = 0, $force = 0)
    {
        $errors = array();
        $current_place = $this->getCurrentPlace();
        if (!in_array($current_place->getData('type'), BE_Place::$entrepot_types)) {
            $errors[] = "Pas en entrepôt";
        }
        if (!isset($type) || $type < 1) {
            $errors[] = "Pas de type";
        }

        if (!count($errors)) {
            // Correction de l'emplacement initial en cas d'erreur: 
            $text = "Transfert auto via Inventaire";
            if ($idI > 0)
                $text .= '-' . $idI;
            $text .= '-SN:' . $this->getData('serial');
            $this->moveToPlace($type, (int) $current_place->getData('id_entrepot'), '', $text, $force);
        }

        return $errors;
    }

    public function moveToPackage($id_package, $code_mvt, $stock_label, $force = 0, $date = null, $origin = '', $id_origin = 0)
    {
        $errors = array();
        $warnings = array();

        $package_dest = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);

        if (!$package_dest->isLoaded()) {
            return array('Le package d\'ID ' . $id_package . ' n\'existe pas');
        }

        if ($force == 1 and $this->getData('id_package'))
            $this->updateField('id_package', 0);


        if ($date == null)
            $date = date('Y-m-d H:i:s');


        $errors = BimpTools::merge_array($errors, $package_dest->addEquipment($this->id, $code_mvt, $stock_label, $date, $warnings, 1, $origin, $id_origin));

        return $errors;
    }

    public function moveToPlace($type, $idOrLabel, $code_mvt, $stock_label, $force = 0, $date = null, $origin = '', $id_origin = 0, $id_contact = 0)
    {
        if ($force == 1 and 0 < (int) $this->getData('id_package')) {
            $this->updateField('id_package', 0);
            $this->addNote('Sortie du package pour déplacement automatique');
            $stock_label .= ' - Sortie du package pour déplacement automatique';
        }

        if (is_null($date) || !$date) {
            $date = date('Y-m-d H:i:s');
        }

        $place = BimpObject::getInstance($this->module, 'BE_Place');

        $data = array(
            'id_equipment' => (int) $this->id,
            'type'         => $type,
            'date'         => $date,
            'infos'        => $stock_label,
            'code_mvt'     => $code_mvt,
            'origin'       => $origin,
            'id_origin'    => $id_origin
        );

        if (in_array($type, BE_Place::$entrepot_types)) {
            $data['id_entrepot'] = $idOrLabel;
        } elseif ($type == BE_Place::BE_PLACE_CLIENT) {
            $data['id_client'] = $idOrLabel;
            $data['id_contact'] = $id_contact;
        } elseif ($type == BE_Place::BE_PLACE_USER) {
            $data['id_user'] = $idOrLabel;
        } elseif ($type == BE_Place::BE_PLACE_FREE) {
            $data['place_name'] = $idOrLabel;
        }

        $errors = $place->validateArray($data);

        if (!count($errors)) {
            $warnings = array();
            $errors = $place->create($warnings, true);
        }

        return $errors;
    }

    public function changeSerial($serial)
    {
        $imei1 = $this->getData('imei');
        $imei2 = $this->getData('imei2');
        $imei3 = $this->getData('meid');
        $oldS = "Serial : " . $this->getData('serial');
        if ($imei1 != '' && $imei1 != "n/a")
            $oldS .= "<br/>Imei : " . $imei1;
        if ($imei2 != '' && $imei2 != "n/a")
            $oldS .= "<br/>Imei2 : " . $imei2;
        if ($imei3 != '' && $imei3 != "n/a")
            $oldS .= "<br/>Meid : " . $imei3;
        if (!$this->getData("old_serial") || $this->getData("old_serial") == '')
            $this->updateField('old_serial', $oldS);
        else
            $this->updateField('old_serial', $this->getData("old_serial") . "<br/>" . $oldS);


        $identifiers = static::gsxFetchIdentifiers($serial);
        $this->updateField('serial', $serial);
        $this->updateField('imei', $identifiers['imei']);
        $this->updateField('imei2', $identifiers['imei2']);
        $this->updateField('meid', $identifiers['meid']);

        return $oldS;
    }

    public static function gsxFetchIdentifiers($serial, $gsx = null)
    {
        $identifiers = array(
            'serial' => $serial,
            'imei'   => '',
            'imei2'  => '',
            'meid'   => ''
        );
        if ((int) BimpCore::getConf('use_gsx_v2', 0)) {
            if (preg_match('/^S(.+)$/', $serial, $matches)) {
                $serial = $matches[1];
            }
            if (is_null($gsx)) {
                if (!class_exists('GSX_v2')) {
                    require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
                }
                $gsx = GSX_v2::getInstance();
            }

            if ($gsx->logged) {
                $data = $gsx->productDetailsBySerial($serial);

                if (isset($data['device'])) {
                    if (isset($data['device']['identifiers']['imei']) && $data['device']['identifiers']['imei']) {
                        $identifiers['imei'] = $data['device']['identifiers']['imei'];
                    } else {
                        $identifiers['imei'] = 'n/a';
                    }


                    if (isset($data['device']['productDescription']) && $data['device']['productDescription']) {
                        $identifiers['productDescription'] = $data['device']['productDescription'];
                    } else {
                        $identifiers['productDescription'] = '';
                    }

                    if (isset($data['device']['identifiers']['imei2']) && $data['device']['identifiers']['imei2']) {
                        $identifiers['imei2'] = $data['device']['identifiers']['imei2'];
                    } else {
                        $identifiers['imei2'] = 'n/a';
                    }

                    if (isset($data['device']['identifiers']['meid']) && $data['device']['identifiers']['meid']) {
                        $identifiers['meid'] = $data['device']['identifiers']['meid'];
                    } else {
                        $identifiers['meid'] = 'n/a';
                    }


                    if (isset($data['device']['identifiers']['serial']) && $data['device']['identifiers']['serial']) {
                        $identifiers['serial'] = $data['device']['identifiers']['serial'];
                    }
                }
            }
        }

        return $identifiers;
    }
    
    public function actionUpdateToNonSerilisable($data, &$success){
        $success = 'Corrigé';
        
        define('DONT_CHECK_SERIAL', true);
        $errors = $this->moveToPlace(BE_Place::BE_PLACE_FREE, 'Correction plus sérialisable', '', '', 1);
        return $errors;
    }

    // Renders: 
    
    public function renderHeader(){
        $product = $this->getChildObject('bimp_product');
        if(BimpObject::objectLoaded($product) && !$product->getData('serialisable')){
            $msg = 'Attention le produit n\'est pas serialisable ';
            $place = $this->getCurrentPlace();
            if (BimpObject::objectLoaded($place)) {
                if ($place->getData('type') == BE_Place::BE_PLACE_ENTREPOT){
                    $onclick = $this->getJsActionOnclick('updateToNonSerilisable', array(), array(
                        'success_callback' => 'function() {triggerObjectChange(\'bimpequipment\', \'Equipment\', ' . (int) $this->id . ')}'
                    ));
                    $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= BimpRender::renderIcon('fas_arrow-circle-right', 'iconLeft') . 'Résoudre';
                    $msg .= '</span>';
                }
            }
            $this->msgs['errors'][] = $msg;
        }
        return parent::renderHeader();
    }

    public function renderReservationsList()
    {
        $html = '';
        if ($this->isLoaded()) {
            $instance = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
            $title = 'Réservation de l\'équipement ' . $this->id . ' - serial ' . $this->getData('serial');
            $list = new BC_ListTable($instance, 'default', 1, null, $title);
            $list->params['add_form_name'] = 'equipment';
            $list->params['edit_form'] = 'equipment';
            $list->params['add_form_title'] = 'Ajout d\'une réservation pour l\'équipement ' . $this->id . ' (serial: ' . $this->getData('serial') . ')';
            $list->addFieldFilterValue('id_equipment', $this->id);
            $html = $list->renderHtml();
        }
        return $html;
    }

    // Actions: 

    public function actionGenerateEtiquette($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        if ($this->isLoaded()) {
            $url = DOL_URL_ROOT . '/bimpequipment/etiquette_equipment.php?id_equipment=' . $this->id;
        } elseif (isset($data['id_objects'])) {
            if (empty($data['id_objects'])) {
                $errors[] = 'Aucun équipement sélectionné';
            } else {
                $url = DOL_URL_ROOT . '/bimpequipment/etiquette_equipment.php?equipments=' . implode(',', $data['id_objects']);
            }
        } else {
            $errors[] = 'Aucun équipement spécifié';
        }

        if ($url) {
            $success_callback = 'window.open(\'' . $url . '\');';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionMoveToPlaceType($data, &$success)
    {
        $success = "Déplacé dans type " . $data['place_type'];
        $errors = array();

        $idI = GETPOST('id');
        if (!isset($idI) || $idI < 1)
            $errors[] = 'Pas d\'id inventaire';

        if (!count($errors)) {
            foreach ($data['id_objects'] as $id) {
                $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);
                $errors = BimpTools::merge_array($errors, $obj->moveToPlaceType($data['place_type'], $idI, 1));
            }
            $success_callback = 'bimp_reloadPage();';
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionRemoveInventaire($data, &$success)
    {
        $errors = array();

        $idI = GETPOST('id');

        if (!isset($idI) || $idI < 1)
            $errors[] = 'Pas d\'id inventaire';

        if (!count($errors)) {
            $success = "Retiré de l'inventaire " . $idI;
            foreach ($data['id_objects'] as $id) {
                $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);
                $errors = BimpTools::merge_array($errors, $obj->removeInventaire($idI));
            }
            $success_callback = 'bimp_reloadPage();';
        }
        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides

    public function reset()
    {
        parent::reset();

        unset($this->current_place);
        $this->current_place = null;
    }

    public function validate()
    {
        $serial = (string) $this->getData('serial');
        $id_product = (int) $this->getData('id_product');
        
        $prod = $this->getChildObject('product');
        if(is_object($prod) && $prod->barcode == $serial)
            return array('Le numéro de série ne peut être identique au code-bar du produit ' . $value);

        if ($serial && $id_product) {
            if (!defined('DONT_CHECK_SERIAL')) {
                $where = '`serial` = \'' . $serial . '\' AND `id_product` = ' . $id_product;
                if ($this->isLoaded()) {
                    $where .= ' AND `id` != ' . (int) $this->id;
                }

                $value = $this->db->getValue($this->getTable(), 'id', $where);
                if (!is_null($value) && (int) $value) {
                    return array('Ce numéro de série pour ce même produit est déjà associé à l\'équipement ' . $value);
                }
            }
        }

        $init_serial = (string) $this->getInitData('serial');

        if ($serial && (!(string) $this->getData('imei') || ($init_serial && $serial != $init_serial))) {
            $identifiers = self::gsxFetchIdentifiers($serial);
            $this->set('imei', $identifiers['imei']);
            $this->set('imei2', $identifiers['imei2']);
            $this->set('meid', $identifiers['meid']);

            if ($identifiers['serial']) {
                $this->set('serial', $identifiers['serial']);
                $serial = $identifiers['serial'];
            }
        }

        if (!$id_product && $serial && (!$this->getInitData('serial') || $this->getInitData('serial') !== $serial)) {
            // Pas de correction du id_product pour l'instant car trop dangereux (stocks, incohérences commandes / factures, etc.)
            if (preg_match('/^.+(.{4})$/', $serial, $matches)) {
                $apple_product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                            'code_config' => $matches[1],
                            'ref'         => array(
                                'part'      => 'APP-',
                                'part_type' => 'beginning'
                            )
                                ), true);

                if (BimpObject::objectLoaded($apple_product)) {
                    $this->set('id_product', $id_product);
                    $this->set('product_label', '');
                }
            }
        }

        if ($id_product) {
            $this->set('product_label', '');
        }

        return parent::validate();
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_id_product = (int) $this->getInitData('id_product');

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if (!$init_id_product && (int) $this->getData('id_product')) {
                // Pour la gestion des stocks: 
                $this->onNewPlace();
            }
        }
        
        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $current_place = $this->getCurrentPlace();
        $id_entrepot = 0;
        $codemove = '';
        $label = '';
        $product = null;

        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        if (!is_null($current_place) && $current_place->isLoaded()) {
            if ((int) $current_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                $product = $this->getChildObject('bimp_product');
                $id_entrepot = (int) $current_place->getData('id_entrepot');
                $codemove = 'EQ' . $this->id . '_SUPPR';
                $label = 'Suppression de l\'équipement ' . $this->id . ' - serial: ' . $this->getData('serial');
            }
        }

        $errors = parent::delete($warnings, $force_delete);

        if (is_null($this->id) && $id_entrepot && BimpObject::objectLoaded($product)) {
            if (isset($this->delete_origin) && $this->delete_origin && isset($this->delete_id_origin) && (int) $this->delete_id_origin) {
                $origin = $this->delete_origin;
                $id_origin = (int) $this->delete_id_origin;
            } else {
                global $user;
                $origin = 'user';
                $id_origin = (int) $user->id;
            }

            $product->correctStocks($id_entrepot, 1, Bimp_Product::STOCK_OUT, $codemove, $label, $origin, $id_origin);
        }

        return $errors;
    }
}
