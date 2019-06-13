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

    public function equipmentExists($serial, $id_product)
    {
        if (is_null($id_product)) {
            $id_product = 0;
        }

        $value = $this->db->getValue($this->getTable(), 'id', '`serial` = \'' . $serial . '\' AND `id_product` = ' . (int) $id_product);
        if (!is_null($value) && $value) {
            return true;
        }

        return false;
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
            'a.position'   => 1,
            'eq.available' => 1
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
                    if (!count($equipment->checkAvailability($id_entrepot))) {
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

//    Traitements: 

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
                    $codemove = dol_print_date(dol_now(), '%y%m%d%H%M%S');
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
            if (!(int) $this->getData('available')) {
                $errors[] = 'L\'équipement ' . $this->getData('serial') . ' n\'est pas disponible';
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
        }
        return $errors;
    }

//    Renders: 

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

//    Overrides

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
}
