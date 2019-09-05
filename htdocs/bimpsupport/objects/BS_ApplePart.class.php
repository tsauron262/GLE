<?php

class BS_ApplePart extends BimpObject
{

    private static $tabRefCommenceIos = array("661-05511", "DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "XG", "ZD661", "ZK661", "ZP661");
    private static $tabDescCommenceIos = array("SVC,IPOD", "Ipod nano");
    private static $tabRefCommenceIosDouble = array("661", "Z661");
    private static $tabDescCommenceIosDouble = array("iphone", "BAT,IPHONE", "SVC,IPHONE"); //design commence par
    private static $tabDescContientIosDouble = array("Ipad", "Ipad Pro", "Ipad mini", "Apple Watc", "Ipad Air", "iPhone 7"); //design contient
    private static $tabRefCommenceBatterie = array("661-04577", "661-04576", "661-08917", "661-02909", "661-04479", "661-04579", "661-04580", "661-04581", "661-04582", "661-05421", "661-05755", "661-08935", "661-8216", "661-04578"); //Prix a 59
    private static $tabRefCommenceBatterieX = array("661-08932", "661-10565", "661-10850", "661-11035"); //Prix a 84
    private static $tabRefCommencePrixEcran = array("661-11232" => array("184,25"), "661-07285" => array("142,58"), "661-07286" => array("142,58"), "661-07287" => array("142,58"), "661-07288" => array("142,58"), "661-07289" => array("159,25"), "661-07290" => array("159,25"), "661-07291" => array("159,25"), "661-07292" => array("159,25"), "661-07293" => array("142,58"), "661-07294" => array("142,58"), "661-07295" => array("142,58"), "661-07296" => array("142,58"), "661-07297" => array("159,25"), "661-07298" => array("159,25"), "661-07299" => array("159,25"), "661-07300" => array("159,25"), "661-08933" => array("142,58"), "661-08934" => array("142,58"), "661-09081" => array("142,58"), "661-10102" => array("142,58"), "661-09032" => array("159,25"), "661-09033" => array("159,25"), "661-09034" => array("159,25"), "661-10103" => array("159,25"), "661-09294" => array("259,25"), "661-13114" => array("259,25"), "661-10608" => array("259,25"), "661-11037" => array("300,91"));
    public static $componentsTypes = array(
        0   => 'Général',
        1   => 'Visuel',
        2   => 'Moniteurs',
        3   => 'Mémoire auxiliaire',
        4   => 'Périphériques d\'entrées',
        5   => 'Cartes',
        6   => 'Alimentation',
        7   => 'Imprimantes',
        8   => 'Périphériques multi-fonctions',
        9   => 'Périphériques de communication',
        'A' => 'Partage',
        'B' => 'iPhone',
        'E' => 'iPod',
        'F' => 'iPad',
        'W' => 'Watch'
    );
    protected static $compTIACodes = null;

    // Getters: 

    public static function getCompTIACodes()
    {
        BimpObject::loadClass('bimpapple', 'GSX_CompTIA');

        return array(
            'grps' => GSX_CompTIA::getCompTIACodes(),
            'mods' => GSX_CompTIA::getCompTIAModifiers()
        );
    }

    public function getComptia_codesArray()
    {
        BimpObject::loadClass('bimpapple', 'GSX_CompTIA');

        $group = $this->getData('component_code');

        $compTIACodes = array();

        if ($group !== 0) {
            $compTIACodes[''] = '';
        }

        foreach (GSX_CompTIA::getCompTIACodes($group) as $code => $label) {
            $compTIACodes[$code] = $label;
        }

        return $compTIACodes;
    }

    public function getPrice_typesArray()
    {
        $prices = array();
        if ((float) $this->getData('exchange_price') !== 0.0 && !(int) $this->getData('no_order')) {
            $prices['EXCHANGE'] = BimpTools::displayMoneyValue((float) $this->getData('exchange_price'), 'EUR') . ' (Prix d\'échange)';
        }

        if ((float) $this->getData('exchange_price') === 0.0 || (int) $this->getData('no_order')) {
            $prices['STOCK'] = BimpTools::displayMoneyValue((float) $this->getData('stock_price'), 'EUR') . ' (Prix stock)';
        }

        $options = $this->getData('price_options');

        if (is_array($options)) {
            foreach ($options as $code => $option) {
                if (isset($option['price'])) {
                    $label = BimpTools::displayMoneyValue((float) $option['price'], 'EUR');

                    if (isset($option['description'])) {
                        $label .= ' (' . $option['description'] . ')';
                    }
                    $prices[$code] = $label;
                }
            }
        }

        return $prices;
    }

    public function getComptia_modifiersArray()
    {
        BimpObject::loadClass('bimpapple', 'GSX_CompTIA');
        return GSX_CompTIA::getCompTIAModifiers();
    }

    public static function getCategProdApple($ref, $desc)
    {
        $type = "autre";


        //Premier cas les ios
        foreach (self::$tabDescCommenceIos as $val)//desc commence par rajout 45€
            if (stripos($desc, $val) === 0)
                $type = "ios";
        foreach (self::$tabRefCommenceIos as $val)//ref commence par rajout 45€
            if (stripos($ref, $val) === 0)
                $type = "ios";
        foreach (self::$tabRefCommenceIosDouble as $val) {//ref commence par rajout 45€
            if (stripos($ref, $val) === 0) {
                foreach (self::$tabDescCommenceIosDouble as $val)
                    if (stripos($desc, $val) === 0)
                        $type = "ios";
                foreach (self::$tabDescContientIosDouble as $val)
                    if (stripos($desc, $val) !== false)
                        $type = "ios";
            }
        }


        //deuxieme cas les Batterie

        foreach (self::$tabRefCommenceBatterie as $val)
            if (stripos($ref, $val) === 0)
                $type = "batt";

        //deuxieme cas bis  les Batterie X
        foreach (self::$tabRefCommenceBatterieX as $val)
            if (stripos($ref, $val) === 0)
                $type = "battX";


        //troisieme cas les ecran

        foreach (self::$tabRefCommencePrixEcran as $val => $inut)
            if (stripos($ref, $val) === 0)
                $type = "ecran";

        return $type;
    }

    public function isCartEditable()
    {
//        $sav = $this->getParentInstance();
//        if (!is_null($sav) && $sav->isLoaded()) {
//            return (int) $sav->isPropalEditable();
//        }
//        return 0;
        return 1;
    }

    public function checkPrice($no_update = false)
    {
        $type = $this->getData('price_type');
        $priceOptions = $this->getData('price_options');
        if (!is_array($priceOptions)) {
            $priceOptions = array();
        }

        if (!$type) {
            $type = 'EXCHANGE';
        }

        if ($type === 'EXCHANGE') {
            if (!(float) $this->getData('exchange_price')) {
                $type = 'STOCK';
            }
        } elseif ($type === 'STOCK') {
            if (!(float) $this->getData('stock_price')) {
                $type = 'EXCHANGE';
            }
        } else {
            if (!array_key_exists($type, $priceOptions)) {
                $type = 'EXCHANGE';
            }
        }

        if ($type === 'STOCK' && !(int) $this->getData('no_order') && (float) $this->getData('exchange_price') !== 0.0) {
            $type = 'EXCHANGE';
        }

        if ($type === 'EXCHANGE' && (int) $this->getData('no_order') && (float) $this->getData('stock_price') !== 0.0) {
            $type = 'STOCK';
        }

        if ($type === 'EXCHANGE' && (float) $this->getData('exchange_price') === 0.0) {
            $type = 'STOCK';
        }

        if ($type !== $this->getData('price_type')) {
            $this->set('price_type', $type);

            if (!$no_update) {
                $this->update();
            }
        }
    }

    public function getPrice()
    {
        $type = $this->getData('price_type');

        switch ($type) {
            case 'EXCHANGE':
                return (float) $this->getData('exchange_price');

            case 'STOCK':
                return (float) $this->getData('stock_price');

            default:
                $priceOptions = $this->getData('price_options');
                if (isset($priceOptions[$type]['price'])) {
                    return (float) $priceOptions[$type]['price'];
                }
                return 0;
        }
    }

    // Traitements: 

    public function convertPrix($type, $prix, $ref, $desc)
    {
        $coefPrix = 1;
        $constPrix = 0;
        $newPrix = 0;


        //Application des coef et constantes
        if ($type == "ios") {
            $constPrix = 45;
        } elseif ($type == "batt" && $this->getData('price_type') == "EXCHANGE") {
            $newPrix = 49.16666666;
        } elseif ($type == "battX" && $this->getData('price_type') == "EXCHANGE") {
            $newPrix = 70;
        } elseif ($type == "ecran" && $this->getData('price_type') == "EXCHANGE") {
            foreach (self::$tabRefCommencePrixEcran as $refT => $tabInfo)
                if ($ref == $refT)
                    $newPrix = str_replace(",", ".", $tabInfo[0]);
        }
        else {
            if ($prix > 300)
                $coefPrix = 0.8;
            elseif ($prix > 150)
                $coefPrix = 0.7;
            elseif ($prix > 50)
                $coefPrix = 0.6;
            else {
                $coefPrix = 0.6;
                $constPrix = 10;
            }
        }


        if ($newPrix > 0)
            $prix = $newPrix;
        else
            $prix = (($prix + $constPrix) / $coefPrix);


        return $prix;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $sav = $this->getParentInstance();
        if (!BimpObject::objectLoaded($sav)) {
            $errors[] = 'ID du SAV absent';
        } elseif (!is_a($sav, 'BS_SAV')) {
            $errors[] = 'SAV invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        $this->checkPrice(true);

        $errors = parent::create($warnings, $force_create);

        if (!count($errors) && $sav->isPropalEditable()) {
            $line_errors = array();
            if (!(int) $sav->getData('id_propal')) {
                $line_errors[] = 'ID de la propal absent';
            } else {
                $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                $line_errors = $line->validateArray(array(
                    'id_obj'             => (int) $sav->getData('id_propal'),
                    'type'               => BS_SavPropalLine::LINE_FREE,
                    'deletable'          => 0,
                    'editable'           => 0,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'sav_apple_part',
                    'out_of_warranty'    => (int) $this->getData('out_of_warranty'),
                    'remisable'          => 1
                ));
                if (!count($line_errors)) {
                    $label = $this->getData('part_number') . ' - ' . $this->getData('label');
                    if ((int) $this->getData('no_order')) {
                        $label .= ' APPRO';
                    }
                    $line->pa_ht = $this->getPrice();
                    $line->desc = $label;
                    $line->qty = (int) $this->getData('qty');
                    $line->tva_tx = 20;

                    $type = self::getCategProdApple($this->getData('part_number'), $this->getData('label'));
                    $line->pu_ht = $this->convertPrix($type, $line->pa_ht, $this->getData('part_number'), $this->getData('label'));

                    if ($type == "ecran" && isset(self::$tabRefCommencePrixEcran[$this->getData('part_number')][1])) {
                        $lineT = BimpObject::getInstance("bimpsupport", "BS_SavPropalLine");
                        $lineT->id_product = self::$tabRefCommencePrixEcran[$this->getData('part_number')][1];
                        $values = array("type" => 1, "id_obj" => $sav->getData('id_propal'));
                        $errorsT = $lineT->validateArray($values);
                        if (count($errorsT) == 0)
                            $errorsT = $lineT->create();

                        if (count($errorsT)) {
                            $errors = array_merge($errors, $errorsT);
                        }
                    }

                    $line_warnings = array();
                    $line_errors = $line->create($line_warnings, true);

                    if (count($line_warnings)) {
                        $line_errors = array_merge($line_errors, $line_warnings);
                    }
                }
            }
            if (count($line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la création de la ligne du devis');
            } else {
                // Création de la remise client par défaut. 
                $client = $sav->getChildObject('client');
                if (BimpObject::objectLoaded($client)) {
                    $remise_percent = (float) $client->dol_object->remise_percent;
                    if ($remise_percent > 0) {
                        $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
                        $remise->validateArray(array(
                            'id_object_line' => (int) $line->id,
                            'object_type'    => $line::$parent_comm_type,
                            'label'          => 'Remise client par défaut',
                            'type'           => (int) ObjectLineRemise::OL_REMISE_PERCENT,
                            'percent'        => $remise_percent
                        ));
                        $remise_errors = $remise->create($warnings, true);
                        if (count($remise_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise client par défaut');
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = array();

        $sav = $this->getParentInstance();
        if (!BimpObject::objectLoaded($sav)) {
            $errors[] = 'ID du SAV absent';
        } elseif (!is_a($sav, 'BS_SAV')) {
            $errors[] = 'SAV invalide';
        }

        if (count($errors)) {
            return $errors;
        }

        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        $this->checkPrice(true);

        $errors = parent::update($warnings, $force_update);

        if (!count($errors) && $sav->isPropalEditable()) {
            $line_errors = array();

            if (!(int) $sav->getData('id_propal')) {
                $line_errors[] = 'ID de la propal absent';
            } else {
                $id_line = 0;
                $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                if ($line->find(array(
                            'id_obj'             => (int) $sav->getData('id_propal'),
                            'linked_id_object'   => (int) $this->id,
                            'linked_object_name' => 'sav_apple_part'
                        ))) {
                    $id_line = (int) $line->id;
                }

                $line_errors = $line->validateArray(array(
                    'id_obj'             => (int) $sav->getData('id_propal'),
                    'type'               => BS_SavPropalLine::LINE_FREE,
                    'deletable'          => 0,
                    'editable'           => 0,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'sav_apple_part',
                    'out_of_warranty'    => (int) $this->getData('out_of_warranty'),
                    'remisable'          => 1
                ));

                if (!count($line_errors)) {
                    $label = $this->getData('part_number') . ' - ' . $this->getData('label');
                    if ((int) $this->getData('no_order')) {
                        $label .= ' APPRO';
                    }
                    if (!$id_line) {
                        $line->desc = $this->getData('label');
                    }
                    $line->pa_ht = $this->getPrice();
                    $line->qty = (int) $this->getData('qty');
                    $line->tva_tx = 20;
                    $type = self::getCategProdApple($this->getData('part_number'), $this->getData('label'));
                    $line->pu_ht = $this->convertPrix($type, $line->pa_ht, $this->getData('part_number'), $this->getData('label'));

                    $line_warnings = array();

                    if ($id_line) {
                        if ($line->isEditable(true)) {
                            $line_errors = $line->update($line_warnings, true);
                        }
                    } else {
                        $line_errors = $line->create($line_warnings, true);
                    }

                    if (count($line_warnings)) {
                        $line_errors = array_merge($line_errors, $line_warnings);
                    }
                }
            }


            if (count($line_errors)) {
                if ($id_line) {
                    $title = 'Des erreurs sont survenues lors de la mise à jour de la ligne du devis';
                } else {
                    $title = 'Des erreurs sont survenues lors de la création de la ligne du devis';
                }
                $warnings[] = BimpTools::getMsgFromArray($line_errors, $title);
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $sav = $this->getParentInstance();

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV absent');
        }

        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if ((int) $sav->isPropalEditable()) {
                $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                if ($line->find(array(
                            'id_obj'             => (int) $sav->getData('id_propal'),
                            'linked_id_object'   => (int) $id,
                            'linked_object_name' => 'sav_apple_part'
                        ))) {
                    $line_warnings = array();
                    $line_errors = $line->delete($line_warnings, true);
                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la suppression de la ligne du devis');
                    }
                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Des erreurs sont survenues suite à la suppression de la ligne du devis');
                    }
                }
            }
        }

        return $errors;
    }

    // Gestion des droits user: 

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }
}
