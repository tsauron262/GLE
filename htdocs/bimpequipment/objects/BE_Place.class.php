<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

class BE_Place extends BimpObject
{

    const BE_PLACE_CLIENT = 1;
    const BE_PLACE_ENTREPOT = 2;
    const BE_PLACE_USER = 3;
    const BE_PLACE_FREE = 4;
    const BE_PLACE_PRESENTATION = 5;
    const BE_PLACE_VOL = 6;
    const BE_PLACE_PRET = 7;
    const BE_PLACE_SAV = 8;
    const BE_PLACE_INTERNE = 9;
    const BE_PLACE_ENQUETE = 90;

    public static $types = array(
        1  => 'Client',
        2  => 'En Stock',
        3  => 'Utilisateur',
        4  => 'Champ libre',
        5  => 'En Présentation',
        6  => 'Vol',
        7  => 'Matériel de prêt',
        8  => 'SAV',
        9  => 'Utilisation interne',
        90 => 'Enquête'
    );
    public static $origins = array(
        ''               => '',
        'commande'       => 'Commande client',
        'vente_caisse'   => 'Vente en caisse',
        'transfert'      => 'Transfert',
        'order_supplier' => 'Commande fournisseur',
        'sav'            => 'SAV',
        'facture'        => 'Facture',
        'package'        => 'Package',
        'user'           => 'Utilisateur',
        'societe'        => 'Société',
        'inventory'      => 'Inventaire',
        'inventory2'     => 'Inventaire',
        'pret'           => 'Prêt'
    );
    public static $entrepot_types = array(self::BE_PLACE_ENTREPOT, self::BE_PLACE_PRESENTATION, self::BE_PLACE_PRET, self::BE_PLACE_SAV, self::BE_PLACE_VOL, self::BE_PLACE_INTERNE, self::BE_PLACE_ENQUETE);
    public static $immos_types = array(self::BE_PLACE_USER, self::BE_PLACE_INTERNE);

    // Getters booléens:

    public function isCreatable($force_create = false, &$errors = array())
    {
        if ($force_create) {
            return 1;
        }

        $equipment = $this->getParentInstance();

        if (!BimpObject::objectLoaded($equipment)) {
            $errors[] = 'ID de l\'équipement absent';
            return 0;
        }
        
        
        $product = $equipment->getChildObject('bimp_product');
        if(BimpObject::objectLoaded($product) && !$product->getData('serialisable')){
            $errors[] = 'Le produit n\'est pas serialisable déplacement impossible';
            return 0;
        }

        if (!$force_create && (int) $equipment->getData('id_package')) {
            $package = $equipment->getChildObject('package');
            $msg = 'L\'équipement ' . $equipment->getNomUrl(0, 1, 1, 'default') . ' est inclus dans le package ';
            if (BimpObject::objectLoaded($package)) {
                $msg .= $package->getNomUrl(0, 1, 1, 'default');
            } else {
                $msg .= ' #' . $equipment->getData('id_package');
            }

            $msg .= '.<br/>Il n\'est pas possible de modifier l\'emplacement de cet équipement';

            $errors[] = $msg;
            return 0;
        }

        return 1;
    }

    public function getContactsArray()
    {
        $contacts = array();

        $id_client = $this->getData('id_client');
        if (!is_null($id_client)) {
            $where = '`fk_soc` = ' . (int) $id_client;
            $rows = $this->db->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $contacts[(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                }
            }
        }

        return $contacts;
    }

    public function getTdStyle()
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();
            if ($parent->isLoaded()) {
                $place = $parent->getCurrentPlace();
                if (!BimpObject::objectLoaded($place) || ((int) $place->id !== (int) $this->id)) {
                    return 'background-color: #D2D2D2!important;';
                }
            }
        }

        return '';
    }

    public function getPlaceName()
    {
        global $langs;
        $name = '';
        $type = $this->getData('type');
        if (!is_null($type)) {
            switch ($type) {
                case self::BE_PLACE_CLIENT:
                    $client = $this->getChildObject('client');
                    if (BimpObject::ObjectLoaded($client)) {
                        $name = 'Client "' . $client->nom . '"';
                    }
                    break;

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                case self::BE_PLACE_PRET:
                case self::BE_PLACE_INTERNE:
                case self::BE_PLACE_ENQUETE:
                    $entrepot = $this->getChildObject('entrepot');
                    if (BimpObject::ObjectLoaded($entrepot)) {
                        $name = 'Entrepôt "' . $entrepot->lieu . '"';

                        switch ($type) {
                            case self::BE_PLACE_PRESENTATION:
                                $name .= ' (Présentation)';
                                break;
                            case self::BE_PLACE_VOL:
                                $name .= ' (Vol)';
                                break;
                            case self::BE_PLACE_SAV:
                                $name .= ' (SAV)';
                                break;
                            case self::BE_PLACE_PRET:
                                $name .= ' (Prêt)';
                                break;
                            case self::BE_PLACE_INTERNE:
                                $name .= ' (Utilisation interne)';
                                break;
                        }
                    }
                    break;

                case self::BE_PLACE_USER:
                    $user = $this->getChildObject('user');
                    if (BimpObject::ObjectLoaded($user)) {
                        $name = 'Utilisateur "' . $user->getFullName($langs) . '"';
                    }
                    break;

                case self::BE_PLACE_FREE:
                    $name = $this->getData('place_name');
                    break;
            }
        }

        if (!$name) {
            $name = 'inconnu';
        }

        return $name;
    }

    public function displayPlace($with_type = false)
    {
        $html = '';
        $type = $this->getData('type');
        if (!is_null($type)) {
            switch ($type) {
                case self::BE_PLACE_CLIENT:
                    if ($with_type) {
                        $html .= 'Client: ';
                    }
                    $html .= $this->displayData('id_client', 'nom_url', false);
                    break;

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                case self::BE_PLACE_PRET:
                case self::BE_PLACE_INTERNE:
                case self::BE_PLACE_ENQUETE:
                    if ($with_type) {
                        $html .= 'Entrepôt: ';
                    }
                    $html .= $this->displayData('id_entrepot', 'nom_url');
                    if ($with_type) {
                        switch ($type) {
                            case self::BE_PLACE_PRESENTATION:
                                $html .= ' (Présentation)';
                                break;
                            case self::BE_PLACE_VOL:
                                $html .= ' (Vol)';
                                break;
                            case self::BE_PLACE_SAV:
                                $html .= ' (SAV)';
                                break;
                            case self::BE_PLACE_PRET:
                                $html .= ' (Prêt)';
                                break;
                            case self::BE_PLACE_INTERNE:
                                $html .= ' (Utilisation interne)';
                                break;
                        }
                    }
                    break;

                case self::BE_PLACE_USER:
                    if ($with_type) {
                        $html .= 'Utilisateur: ';
                    }
                    $html .= $this->displayData('id_user', 'nom_url');
                    break;

                case self::BE_PLACE_FREE:
                    $html .= $this->getData('place_name');
                    break;
            }
        }

        return $html;
    }

    public function displayOrigin()
    {
        if ($this->getData('origin') && (int) $this->getData('id_origin') && array_key_exists($this->getData('origin'), self::$origins)) {
            switch ($this->getData('origin')) {
                case 'vente_caisse':
                    $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($vente)) {
                        return $vente->getLink();
                    } else {
                        return BimpRender::renderAlerts('La vente d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }

                    break;

                case 'order_supplier':
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($comm)) {
                        return $comm->getLink();
                    } else {
                        return BimpRender::renderAlerts('La commande fournisseur d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'commande':
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($comm)) {
                        return $comm->getLink();
                    } else {
                        return BimpRender::renderAlerts('La commande d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'facture':
                    $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($fac)) {
                        return $fac->getLink();
                    } else {
                        return BimpRender::renderAlerts('La facture d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'sav':
                    $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($sav)) {
                        return $sav->getLink();
                    } else {
                        return BimpRender::renderAlerts('Le SAV d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'transfert':
                    $transfert = BimpCache::getBimpObjectInstance('bimptransfer', 'Transfer', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($transfert)) {
                        return $transfert->getNomUrl(1, 0, 1, 'default');
                    } else {
                        return BimpRender::renderAlerts('Le transfert d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'user':
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($user)) {
                        return $user->getLink();
                    } else {
                        return BimpRender::renderAlerts('L\'utilisateur d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'societe':
                    $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($soc)) {
                        return $soc->getLink();
                    } else {
                        return BimpRender::renderAlerts('La société d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'inventory':
                    $inv = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($inv)) {
                        return $inv->getNomUrl(1, 1, 1, 'default');
                    } else {
                        return BimpRender::renderAlerts('L\'inventaire d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'inventory2':
                    $inv = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory2', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($inv)) {
                        return $inv->getNomUrl(1, 1, 1, 'default');
                    } else {
                        return BimpRender::renderAlerts('L\'inventaire d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;

                case 'pret':
                    $pret = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Pret', (int) $this->getData('id_origin'));
                    if (BimpObject::objectLoaded($pret)) {
                        return $pret->getNomUrl(1, 1, 1, 'default');
                    } else {
                        return BimpRender::renderAlerts('Le prêt d\'ID ' . $this->getData('id_origin') . ' n\'existe plus', 'danger');
                    }
                    break;
            }
        }

        return '';
    }

    // Overrides:

    public function validate()
    {
        $type = $this->getData('type');

        if (!is_null($type) && array_key_exists($type, self::$types)) {
            switch ((int) $type) {
                case self::BE_PLACE_CLIENT:
                    $id_client = $this->getData('id_client');
                    if (is_null($id_client) || !$id_client) {
                        return array('Valeur obligatoire absente: "Client"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_ENTREPOT:
                case self::BE_PLACE_PRESENTATION:
                case self::BE_PLACE_INTERNE:
                case self::BE_PLACE_VOL:
                case self::BE_PLACE_SAV:
                case self::BE_PLACE_ENQUETE:
                    $id_entrepot = $this->getData('id_entrepot');
                    if (is_null($id_entrepot) || !$id_entrepot) {
                        return array('Valeur obligatoire absente: "Entrepôt"');
                    }
                    $this->set('id_client', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_PRET:
                    if (!(int) $this->getData('id_entrepot')) {
                        if (!$this->getData('code_centre')) {
                            return array('Valeur obligatoire absente: "Centre" ou "Entrepot"');
                        }
                        global $tabCentre;
                        $this->set('id_entrepot', (int) $tabCentre[$this->getData('code_centre')][8]);
                    }

                    $this->set('id_client', 0);
                    $this->set('id_user', 0);
                    $this->set('place_name', '');
                    break;

                case self::BE_PLACE_USER:
                    $id_user = $this->getData('id_user');
                    if (is_null($id_user) || !$id_user) {
                        return array('Valeur obligatoire absente: "Utilisateur"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_client', 0);
                    $this->set('place_name', '');
                    $this->set('code_centre', '');
                    break;

                case self::BE_PLACE_FREE:
                    $name = $this->getData('place_name');
                    if (is_null($name) || !$name) {
                        return array('Valeur obligatoire absente: "Nom de l\'emplacement"');
                    }
                    $this->set('id_entrepot', 0);
                    $this->set('id_user', 0);
                    $this->set('id_client', 0);
                    $this->set('code_centre', '');
                    break;
            }

            return parent::validate();
        }

        return array('Type invalide ou absent');
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if ($this->isLoaded()) {
            $equipment = $this->getParentInstance();
            if ($equipment->isLoaded()) {
                $equipment->onNewPlace();
            }
        }

        return $errors;
    }
}
