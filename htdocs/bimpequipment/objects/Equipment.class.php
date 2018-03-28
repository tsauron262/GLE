<?php

class Equipment extends BimpObject
{

    public static $types = array(
        1 => 'Ordinateur',
        2 => 'Accessoire',
        3 => 'License',
        4 => 'Serveur',
        5 => 'Matériel réseau',
        6 => 'Autre'
    );
    public static $warranty_types = array(
        0 => ' - ',
        1 => 'Type 1',
        2 => 'Type 2'
    );
    public static $origin_elements = array(
        0 => '',
        1 => 'Fournisseur',
        2 => 'Client',
        3 => 'Commande Fournisseur'
    );
    protected $current_place = null;

    public function equipmentExists($serial, $id_product)
    {
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
                        'operator' => '>',
                        'value'    => 199
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

    public function onNewPlace()
    {
        if ($this->isLoaded()) {
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

                $new_place = BimpObject::getInstance($this->module, 'BE_Place', $items[0]['id']);
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
                        $new_place_element = 'entrepot';
                        $new_place_id_element = (int) $new_place->getData('id_entrepot');
                        break;

                    case BE_Place::BE_PLACE_USER:
                        $new_place_element = 'user';
                        $new_place_id_element = (int) $new_place->getData('id_user');
                        break;
                }

                if (isset($items[1])) {
                    $prev_place = BimpObject::getInstance($this->module, 'BE_Place', $items[1]['id']);
                    switch ((int) $prev_place->getData('type')) {
                        case BE_Place::BE_PLACE_CLIENT:
                            $prev_place_element = 'societe';
                            $prev_place_id_element = (int) $prev_place->getData('id_client');
                            break;

                        case BE_Place::BE_PLACE_ENTREPOT:
                        case BE_Place::BE_PLACE_PRESENTATION:
                        case BE_Place::BE_PLACE_VOL:
                        case BE_Place::BE_PLACE_PRET:
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
                    if ($place->fetch((int) $items[0]['id'])) {
                        $this->current_place = $place;
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

    public function displayReserved()
    {
        if (count($this->getReservationsList())) {
            return '<span class="success">OUI</span>';
        }

        return '</span class="danger">NON</span>';
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

        if (!is_null($serial) && $serial && $id_product) {
            $where = '`serial` = \'' . $serial . '\' AND `id_product` = ' . $id_product;
            if ($this->isLoaded()) {
                $where .= ' AND `id` != ' . (int) $this->id;
            }

            $value = $this->db->getValue($this->getTable(), 'id', $where);
            if (!is_null($value) && (int) $value) {
                return array('Ce numéro de série pour ce même produit est déjà associé à l\'équipement ' . $value);
            }
        }

        return parent::validate();
    }
}
