<?php

class BR_ReservationCmdFourn extends BimpObject
{

    public function getProductFournisseursArray()
    {
        $fournisseurs = array();

        $product = $this->getChildObject('product');

        if (!is_null($product) && $product->isLoaded()) {
            $list = $product->dol_object->list_suppliers();
            foreach ($list as $id_fourn) {
                if (!array_key_exists($id_fourn, $fournisseurs)) {
                    $result = $this->db->getRow('societe', '`rowid` = ' . (int) $id_fourn, array('nom', 'code_fournisseur'));
                    if (!is_null($result)) {
                        $fournisseurs[(int) $id_fourn] = $result->code_fournisseur . ' - ' . $result->nom;
                    } else {
                        echo $this->db->db->error();
                    }
                }
            }
        }

        return $fournisseurs;
    }

    public function getProductFournisseursPricesArray()
    {
        $prices = array(
            0 => ''
        );

        $id_product = (int) $this->getData('id_product');
        $id_price = (int) $this->getData('id_price');

        $filters = array();

        if ($id_product) {
            $filters['fp.fk_product'] = $id_product;
        }

        if ($id_price) {
            $filters['fp.rowid'] = $id_price;
        }

        $sql = 'SELECT fp.rowid as id, fp.unitprice as price, fp.quantity as qty, fp.tva_tx as tva, s.nom, s.code_fournisseur as ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price fp';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON fp.fk_soc = s.rowid';
        $sql .= BimpTools::getSqlWhere($filters);
        $sql .= ' ORDER BY fp.unitprice ASC';

        $rows = $this->db->executeS($sql, 'array');

        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $label = $r['nom'] . ($r['ref'] ? ' - Réf. ' . $r['ref'] : '') . ' (';
                $label .= BimpTools::displayMoneyValue((float) $r['price'], 'EUR');
                $label .= ' - TVA: ' . BimpTools::displayFloatValue((float) $r['tva']) . '%';
                $label .= ' - Qté min: ' . $r['qty'] . ')';
                $prices[(int) $r['id']] = $label;
            }
        }

        return $prices;
    }

    public function getCommandesFournisseurArray()
    {
        return array();
    }

    public function displayFournisseur()
    {
        $type = (int) $this->getData('type');

        switch ($type) {
            case 1:
                $fp = $this->getChildObject('fournisseur_price');
                if (!is_null($fp) && $fp->isLoaded()) {
                    return $fp->displayData('fk_soc', 'nom_url');
                }
                break;

            case 2:
                return $this->displayData('id_fournisseur', 'nom_url');
        }

        return '';
    }

    public function displayPrice()
    {
        $type = (int) $this->getData('type');

        switch ($type) {
            case 1:
                $fp = $this->getChildObject('fournisseur_price');
                if (!is_null($fp) && $fp->isLoaded()) {
                    return BimpTools::displayMoneyValue($fp->getData('unitprice'), 'EUR');
                }
                break;

            case 2:
                return $this->displayData('special_price');
        }

        return '';
    }

    public function getListExtraBtn()
    {
        $buttons = array();
        
        if (!(int) $this->getData('id_commande_fournisseur')) {
            $title = 'Ajout à une commande fournisseur';
            $onclick = 'loadModalForm($(this), {module: \'bimpreservation\', object_name: \'BR_ReservationCmdFourn\', id_object: ' . $this->id . ', ';
            $onclick .= 'form_name: \'supplier\'}, \'' . $title . '\');';
            $buttons[] = array(
                'label'   => 'Ajouter à une commande fournisseur',
                'icon'    => 'plus-circle',
                'onclick' => $onclick
            );
        } else {
            
        }
        
        return $buttons;
    }

    // Overrides:

    public function create()
    {
        $errors = parent::create();

        if (!count($errors)) {
            $reservation = $this->getChildObject('reservation');

            $res_errors = array();
            if (is_null($reservation) || !$reservation->isLoaded()) {
                $res_errors[] = 'ID de la réservation absent ou invalide';
            } else {
                $qty = (int) $this->getData('qty');
                $res_errors = $reservation->setNewStatus(100, $qty);
            }

            if (count($res_errors)) {
                $errors[] = 'Echec de la mise à jour du statut de la réservation';
                $errors = array_merge($errors, $res_errors);
            } else {
                $reservation->update();
            }
        }

        return $errors;
    }
}
