<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";

class Equipment extends BimpObject
{

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

    public function __construct($db)
    {
        require_once(DOL_DOCUMENT_ROOT . "/bimpequipment/objects/BE_Place.class.php");
        self::$typesPlace = BE_Place::$types;
        parent::__construct("bimpequipment", get_class($this));
        $this->iconeDef = "fa-laptop";
    }

    // Getters booléens: 

    public function isAvailable($id_entrepot = 0, &$errors = array(), $allowed = array())
    {
        // *** Valeurs possibles dans $allowed: ***
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
                $errors[] = 'L\'équipement ' . $this->getNomUrl(0, 1, 1, 'default') . ' est réservé';
            }
        }

        // Check des ventes caisse en cours (Brouillons): 
        $this->isNotInVenteBrouillon($errors, isset($allowed['id_vente']) ? (int) $allowed['id_vente'] : 0);

        // Check des SAV: 
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

        // Check retour en commande client: 
        if ((int) $this->getData('id_commande_line_return')) {
            if (!isset($allowed['id_commande_line_return']) || ((int) $allowed['id_commande_line_return'] !== (int) $this->getData('id_commande_line_return'))) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $this->getData('id_commande_line_return'));
                if (BimpObject::objectLoaded($line) && $line->getFullQty() < 0) {
                    $commande = $line->getParentInstance();
                    if (BimpObject::objectLoaded($commande)) {
                        $msg = 'Un retour est en cours pour l\'équipement ' . $this->getNomUrl(0, 1, 1, 'default');
                        $msg .= ' dans la commande client ' . $commande->getNomUrl(0, 1, 1, 'full') . " a la ligne ".$this->getData('id_commande_line_return');
                        $errors[] = $msg;
                    }
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
        if (in_array($place->getData('type'), BE_Place::$entrepot_types)) {
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

    // Getters données: 

    public function getName()
    {
        if ($this->isLoaded()) {
            return 'Equipement #' . $this->id;
        }

        return '';
    }

    public function getRef()
    {
        return $this->getData("serial");
    }

    public function getHasReservationsArray()
    {
        return array(
            '' => '',
            1  => 'OUI',
            0  => 'NON'
        );
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
            $filters['or_placeType'] = array(
                'or' => array(
                    'placeType.type' => array(
                        'part_type' => 'middle',
                        'part'      => $value
                    )
                )
            );
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
                        $equipments[(int) $equipment->id] = $equipment->getRef();
                    }
                }
            }
        }

        return $equipments;
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
                    if (!class_exists('Societe')) {
                        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                    }
                    $soc = new Societe($db);
                    if ($soc->fetch($id_element) <= 0) {
                        return BimpRender::renderAlerts('La société d\'ID ' . $id_element . ' n\'existe pas');
                    }
                    return $soc->getNomUrl(1) . BimpRender::renderObjectIcons($soc, true, null);

                case 3:
                    global $db;
                    if (!class_exists('CommandeFournisseur')) {
                        require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
                    }
                    $comm = new CommandeFournisseur($db);
                    if ($comm->fetch($id_element) <= 0) {
                        return BimpRender::renderAlerts('La commande fournisseur d\'ID ' . $id_element . ' n\'existe pas');
                    }
                    $url = DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $id_element;
                    return $comm->getNomUrl(1) . BimpRender::renderObjectIcons($comm, true, null, $url);
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

    public function displayProduct($display_name = 'default', $no_html = false)
    {
        if ((int) $this->getData('id_product')) {
            return $this->displayData('id_product', $display_name, ($no_html ? 0 : 1), $no_html);
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
            $html .= BimpRender::renderAlerts('Equipement disponible', 'success');
        }

        return $html;
    }

    // Traitements: 

    public function onNewPlace()
    {
        if ($this->isLoaded() && !defined('DONT_CHECK_SERIAL')) {
            $product = $this->getChildObject('product');
            if (!is_null($product) && isset($product->id) && $product->id) {
                $place = BimpObject::getInstance($this->module, 'BE_Place');
                $items = $place->getList(array(
                    'id_equipment' => $this->id
                        ), 2, 1, 'id', 'desc', 'array', array(
                    'id'
                ));

                global $user;

                $prev_place_element = '';
                $prev_place_id_element = null;

                $new_place_element = '';
                $new_place_id_element = null;

                $new_place = BimpCache::getBimpObjectInstance($this->module, 'BE_Place', $items[0]['id']);
                $codemove = $new_place->getData('code_mvt');
                if (is_null($codemove) || !$codemove) {
                    $codemove = dol_print_date(dol_now(), 'EQ' . (int) $this->id . '_PLACE' . (int) $new_place->id);
                }
                $new_place_infos = $new_place->getData('infos');
                $label = ($new_place_infos ? $new_place_infos . ' - ' : '') . 'Produit "' . $product->ref . '" - serial: "' . $this->getData('serial') . '"';

                switch ((int) $new_place->getData('type')) {
                    case BE_Place::BE_PLACE_CLIENT:
                        $new_place_element = 'societe';
                        $new_place_id_element = (int) $new_place->getData('id_client');
                        break;

                    case BE_Place::BE_PLACE_ENTREPOT:
                    case BE_Place::BE_PLACE_PRESENTATION:
                    case BE_Place::BE_PLACE_VOL:
                    case BE_Place::BE_PLACE_PRET:
                    case BE_Place::BE_PLACE_SAV:
                        $new_place_element = 'entrepot';
                        $new_place_id_element = (int) $new_place->getData('id_entrepot');
                        break;

                    case BE_Place::BE_PLACE_USER:
                        $new_place_element = 'user';
                        $new_place_id_element = (int) $new_place->getData('id_user');
                        break;
                }

                if (isset($items[1])) {
                    $prev_place = BimpCache::getBimpObjectInstance($this->module, 'BE_Place', $items[1]['id']);
                    switch ((int) $prev_place->getData('type')) {
                        case BE_Place::BE_PLACE_CLIENT:
                            $prev_place_element = 'societe';
                            $prev_place_id_element = (int) $prev_place->getData('id_client');
                            break;

                        case BE_Place::BE_PLACE_ENTREPOT:
                        case BE_Place::BE_PLACE_PRESENTATION:
                        case BE_Place::BE_PLACE_VOL:
                        case BE_Place::BE_PLACE_PRET:
                        case BE_Place::BE_PLACE_SAV:
                            $prev_place_element = 'entrepot';
                            $prev_place_id_element = (int) $prev_place->getData('id_entrepot');
                            if ((int) $prev_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                                $product->correct_stock($user, $prev_place_id_element, 1, 1, $label, 0, $codemove, $new_place_element, $new_place_id_element);
                            }
                            break;

                        case BE_Place::BE_PLACE_USER:
                            $prev_place_element = 'user';
                            $prev_place_id_element = (int) $prev_place->getData('id_user');
                            break;
                    }
                } else {
                    $prev_place_element = $this->getData('origin_element');
                    if (in_array($prev_place_element, array(1, 2))) {
                        $prev_place_element = 'societe';
                    }
                    $prev_place_id_element = $this->getData('origin_id_element');
                    if (!$prev_place_id_element) {
                        $prev_place_id_element = null;
                    }
                }

                if ((int) $new_place->getData('type') === BE_Place::BE_PLACE_ENTREPOT) {
                    $product->correct_stock($user, $new_place_id_element, 1, 0, $label, 0, $codemove, $prev_place_element, $prev_place_id_element);
                }
            }

            $this->current_place = $new_place;
        }
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
        $gsx = new GSX($isIphone);

        $errors = array();

        $result = array(
            'product_label'     => '',
            'date_purchase'     => '',
            'date_warranty_end' => '',
            'warranty_type'     => '',
            'warning'           => ''
        );

        if (!$gsx->connect) {
            $errors = BimpTools::getMsgFromArray($gsx->errors['init'], 'Echec de la connexion GSX');
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

    // Renders: 

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

    // Overrides

    public function reset()
    {
        parent::reset();

        unset($this->current_place);
        $this->current_place = null;
    }

    public function validate()
    {
        $serial = $this->getData('serial');
        $id_product = (int) $this->getData('id_product');

        if (!is_null($serial) && $serial && $id_product && !defined('DONT_CHECK_SERIAL')) {
            $where = '`serial` = \'' . $serial . '\' AND `id_product` = ' . $id_product;
            if ($this->isLoaded()) {
                $where .= ' AND `id` != ' . (int) $this->id;
            }

            $value = $this->db->getValue($this->getTable(), 'id', $where);
            if (!is_null($value) && (int) $value) {
                return array('Ce numéro de série pour ce même produit est déjà associé à l\'équipement ' . $value);
            }
        }

        if ($id_product) {
            $this->set('product_label', '');
        }

        return parent::validate();
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $current_place = $this->getCurrentPlace();
        $id_entrepot = 0;
        $codemove = '';
        $label = '';
        $product = null;

        if (!is_null($current_place) && $current_place->isLoaded()) {
            if (in_array((int) $current_place->getData('type'), array(
                        BE_Place::BE_PLACE_ENTREPOT,
//                        BE_Place::BE_PLACE_PRESENTATION,
//                        BE_Place::BE_PLACE_VOL,
//                        BE_Place::BE_PLACE_PRET
                    ))) {
                $product = $this->getChildObject('product');
                $id_entrepot = (int) $current_place->getData('id_entrepot');
                $codemove = $current_place->getData('code_mvt');
                if (is_null($codemove) || !$codemove) {
                    $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
                }
                $label = 'Suppression de l\'équipement ' . $this->id . ' - serial: ' . $this->getData('serial');
            }
        }

        $errors = parent::delete($warnings, $force_delete);

        if (is_null($this->id) && $id_entrepot && !is_null($product) && isset($product->id) && $product->id) {
            global $user;
            $product->correct_stock($user, $id_entrepot, 1, 1, $label, 0, $codemove, 'entrepot', $id_entrepot);
        }

        return $errors;
    }

    // Gestion des droits: 

    public function canDelete()
    {
        global $user;
        return (int) $user->admin;
    }
    public function canEdit()
    {
        global $user;
//        if($user->rights->admin or $user->rights->produit->creer)
            return 1;
    }
    public function canCreate()
    {
        return $this->canEdit();
    }
}
