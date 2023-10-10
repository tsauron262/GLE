<?php

class BS_ApplePart extends BimpObject
{

    private static $tabRefCommenceIos = array("661-05511", "DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "XG", "ZD661", "ZK661", "ZP661");
    private static $tabDescCommenceIos = array("SVC,IPOD", "Ipod nano");
    private static $tabRefCommenceIosDouble = array("661", "Z661", "B661", "J661");
    private static $tabDescCommenceIosDouble = array("iphone", "BAT,IPHONE", "SVC,IPHONE"); //design commence par
    private static $tabDescContientIosDouble = array("Ipad", "Ipad Pro", "Ipad mini", "Apple Watc", "Ipad Air", "iPhone 7", "iPhone 8", "iPhone XR"); //design contient
    private static $tabRefCommenceBatterie = array("661-15741", "661-04577", "661-04576", "661-08917", "661-02909", "661-04479", "661-04579", "661-04580", "661-04581", "661-04582", "661-05421", "661-05755", "661-08935", "661-8216", "661-04578"); //Prix a 59
    private static $tabRefCommenceBatterieX = array("661-08932", "661-10565", "661-10850", "661-11035", //X
        "661-13574", "661-13569", "661-13624", //11   Prix a 84
        "661-17920", //12 pro
        "661-17939"); //12 mini
    private static $tabRefCommenceBatterie14 = array('661-30397', '661-30382', '661-30394', '661-30373'); // Iphone 14 prix à 119,00 TTC
    private static $tabRefCommencePrixEcran = array("661-30401" => array("489,00"), "661-29370" => array("405,00"), "661-30366" => array("338,99"), "661-04856" => array("229"), "661-13114" => array("338,99"), "661-07285" => array("185,00"), "661-07286" => array("185,00"), "661-07287" => array("185,00"), "661-07288" => array("185,00"), "661-07289" => array("209,00"), "661-07290" => array("209,00"), "661-07291" => array("209,00"), "661-07292" => array("209,00"), "661-07293" => array("185,00"), "661-07294" => array("185,00"), "661-07295" => array("185,00"), "661-07296" => array("185,00"), "661-07297" => array("209,00"), "661-07298" => array("209,00"), "661-07299" => array("209,00"), "661-07300" => array("209,00"), "661-08933" => array("185,00"), "661-08934" => array("185,00"), "661-09081" => array("185,00"), "661-10102" => array("185,00"), "661-09032" => array("209,00"), "661-09033" => array("209,00"), "661-09034" => array("209,00"), "661-10103" => array("209,00"), "661-09294" => array("338,99"), "661-10608" => array("338,99"), "661-11037" => array("405,00"), "661-11232 " => array("239,00"), "661-14098" => array("239,00"), "661-14096" => array("338,99"), "661-14099" => array("405,00"), "661-17940" => array("279,00"), "661-18503" => array("338,99"), "661-18504" => array("338,99"), "661-18466" => array("405,00"), "661-22311" => array("279,00"), "661-21988" => array("338,99"), "661-21993" => array("338,99"), "661-22309" => array("405,00"), "661-15743" => array("185,00"), "661-26353" => array("185,00"));
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
    public static $partsWithNonIssue = array('011-00212', '011-00214', '011-00213');
    public static $refLiee = array(array(array(
                "661-08937",
                "661-08932",
                "923-01962",
                "923-01966",
                "661-10566",
                "923-02609",
                "923-02592",
                "661-10565",
                "661-11036",
                "923-02649",
                "923-02682",
                "661-11035",
                "923-02609",
                "661-10851",
                "923-02610",
                "661-10850",
                "661-15741",
                "661-15742",
                "923-04089",
                "923-04080",
                "661-13575",
                "923-03512",
                "923-03506",
                "661-13574",
                "661-13570",
                "923-03479",
                "923-03470",
                "661-13569",
                "923-03535",
                "923-03537",
                "661-13624",
                "661-13572",
                "661-17939",
                "923-04999",
                "923-05011",
                "661-17920 ",
                "923-04979",
                "923-0415",
                "661-18428",
                "923-05036",
                "923-05104",
                "661-22374",
                "923-06682",
                "923-06692",
                "661-21991",
                "923-06237",
                "923-06235",
                "661-21996",
                "923-06258",
                "923-06256",
                "661-22294",
                "923-06643",
                "923-06648",
                "661-08935",
                "661-08936",
                "923-01983",
                "652-00230",
                "661-08918",
                "923-02056",
                "923-01925", "661-02553"
            ), array("011-00212" => "SVC,TRACK USAGE,DISPLAY SCREW KIT", "011-00213" => "SVC,TRACK USAGE,DOCK SCREWS", "011-00214" => "SVC,TRACK USAGE,PSA")),
        array(array(
                "661-13114",
                "661-10608",
                "661-11037",
                "661-11232",
                "661-15743",
                "661-14098",
                "661-14096",
                "661-14099",
                "661-17940",
                "661-18503",
                "661-18504",
                "661-18466",
                "661-22311",
                "661-21988",
                "661-21993",
                "661-22309",
                "661-08933",
                "661-08934",
                "661-09081",
                "661-10102",
                "661-09032",
                "661-09033",
                "661-09034",
                "661-10103"),
            array("011-00213" => "SVC,TRACK USAGE,DOCK SCREWS", "011-00214" => "SVC,TRACK USAGE,PSA"))
    );
    protected static $compTIACodes = null;

    // Droits user: 

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    // Getters booléens: 

    public function isCartEditable()
    {
        return 1;
    }

    public function isPropalEditable()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            return (int) $sav->isPropalEditable();
        }
        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array(/* 'qty', */'stock_price', 'exchange_price', 'out_of_warranty', 'price_type', 'not_invoiced'))) {
            return (int) $this->isPropalEditable();
        }
        return (int) parent::isFieldEditable($field, $force_edit);
    }

    public function isInternalStockAllowed()
    {
        // En prévision...
        return 1;
    }

    public function isConsignedStockAllowed()
    {
        $issue = $this->getChildObject('issue');
        return (int) !((BimpObject::objectLoaded($issue) && $issue->isTierPart()) || in_array($this->getData('part_number'), self::$partsWithNonIssue));
    }

    // Getters array: 

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

    public function getReproducibiliesArray()
    {
        if (!class_exists('GSX_v2')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        }

        return GSX_v2::$reproducibilities;
    }

    public function getSavIssuesArray()
    {
        if ($this->isLoaded()) {
            $sav = $this->getParentInstance();
            if (BimpObject::objectLoaded($sav)) {
                return $sav->getIssuesArray(true);
            }
        }

        return array();
    }

    // Getters config: 

    public function getNoIssueExtaButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if (!(int) $this->getData('id_issue')) {
                $buttons[] = array(
                    'label'   => 'Attribuer à un problème composant',
                    'icon'    => 'fas_arrow-circle-right',
                    'onclick' => $this->getJsActionOnclick('attributeToIssue', array(), array(
                        'form_name' => 'select_issue'
                    ))
                );
            }
        }

        return $buttons;
    }

    // Getters données

    public function getThisCategProdApple()
    {
        return $this->getCategProdApple($this->getData('part_number'), $this->getData('label'));
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


        //rer systéme 
        if (stripos($desc, 'REAR SYSTEM') !== false)
            $type = "rear";



        //deuxieme cas les Batterie

        foreach (self::$tabRefCommenceBatterie as $val)
            if (stripos($ref, $val) === 0)
                $type = "batt";

        //deuxieme cas bis  les Batterie X
        foreach (self::$tabRefCommenceBatterieX as $val)
            if (stripos($ref, $val) === 0)
                $type = "battX";

        // Batteries IPhone 14
        foreach (self::$tabRefCommenceBatterie14 as $val)
            if (stripos($ref, $val) === 0)
                $type = "batt14";


        //troisieme cas les ecran

        foreach (self::$tabRefCommencePrixEcran as $val => $inut)
            if (stripos($ref, $val) === 0)
                $type = "ecran";

        return $type;
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

    public function convertPrix($type, $prix, $ref, $desc = '')
    {
        $sav = $this->getParentInstance();
        $equipment = $sav->getChildObject('equipment');
        return self::convertPrixStatic($type, $prix, $ref, $equipment->isIphone(), $this->getData('price_type'));
    }

    public static function convertPrixStatic($type, $prix, $ref, $isIphone, $price_type = 'STOCK')
    {
        if ($prix < 1)
            return 0;

        if ($prix == 180.62) {
            return (289 / 1.2);
        }

        /* a voir avec JC selon karim pour batterie mac book 2015 */
        if ($prix == 130.63) {
            return (289 / 1.2);
        }

        if ($prix == 143.12) {
            return (229 / 1.2);
        }

        if ($prix == 130.63 || $prix == 115.63) {
            // return (229 / 1.2); old
            return (185 / 1.2);
        }

        if ($prix == 93.13) {
            return (149 / 1.2);
        }

        if ($prix == 86.29) { // Batterie IPad
            return 107.5;
        }

        if ($prix == 76.72) {
            return 99.16667;
        }

        if ($prix == 65.87) {
            return 82.5;
        }

        if ($prix == 51.57) {
            return (119 / 1.2);
        }

        if ($prix == 42.90) {
            return (99 / 1.2);
        }

        if ($prix == 40.62) {
            return 70;
        }
        if ($prix == 34.23) {
            return (79 / 1.2);
        }

        $coefPrix = 1;
        $constPrix = 0;
        $newPrix = 0;
        //Application des coef et constantes
        if ($type == 'batt14' && $price_type == 'EXCHANGE') {
            $newPrix = 119 / 1.2;
        } elseif ($type == "batt" && $price_type == "EXCHANGE") {
            $newPrix = 79 / 1.2;
        } elseif ($type == "battX" && $price_type == "EXCHANGE") {
            $newPrix = 99 / 1.2;
        } elseif ($type == "ecran" && $price_type == "EXCHANGE") {
            foreach (self::$tabRefCommencePrixEcran as $refT => $tabInfo)
                if ($ref == $refT)
                    $newPrix = str_replace(",", ".", $tabInfo[0]) / 1.2;
        } elseif ($type == 'rear') {
            if ($prix > 400)
                $constPrix = $prix * 0.1 + 24.17;
            elseif ($prix > 350)
                $constPrix = $prix * 0.13 + 24.17;
            elseif ($prix > 300)
                $constPrix = $prix * 0.15 + 24.17;
            elseif ($prix > 250)
                $constPrix = $prix * 0.17 + 24.17;
            elseif ($prix > 200)
                $constPrix = $prix * 0.2 + 24.17;
        } elseif ($isIphone) {
//            $constPrix = 45;
            if ($prix > 400)
                $constPrix = $prix * 0.1;
            elseif ($prix > 350)
                $constPrix = $prix * 0.13;
            elseif ($prix > 300)
                $constPrix = $prix * 0.15;
            elseif ($prix > 250)
                $constPrix = $prix * 0.17;
            elseif ($prix > 200)
                $constPrix = $prix * 0.2;
            elseif ($prix > 150)
                $constPrix = $prix * 0.25;
            elseif ($prix > 100)
                $constPrix = $prix * 0.4;
            else
                $constPrix = $prix * 0.5 + 24.17;
        } else {
            if ($prix > 300)
                $coefPrix = 0.8;
            elseif ($prix > 150)
                $coefPrix = 0.7;
            elseif ($prix > 50)
                $coefPrix = 0.6;
            elseif ($prix > 0.01) {
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

    public function checkIsNotInvoiced()
    {
        if (!BimpCore::getConf('use_gsx_v2', null, 'bimpapple')) {
            return 0;
        }

        if (!(int) $this->getData('is_tier')) {
            $sav = $this->getParentInstance();
            if (BimpObject::objectLoaded($sav)) {
                if ((int) $sav->hasTierParts()) {
                    return 1;
                }
            }
        }

        return 0;
    }

    public function setPropalLinePrices($line)
    {
        if ($this->isLoaded() && is_a($line, 'ObjectLine')) {
            $type = self::getCategProdApple($this->getData('part_number'), $this->getData('label'));

            if ((int) $this->getData('not_invoiced')) {//Pour l'ajout des vices sur iPhones
                $this->pa_ht = 0;
                $line->pu_ht = 0;
            } else {
                $line->pa_ht = $this->getPrice();
                $line->pu_ht = $this->convertPrix($type, $line->pa_ht, $this->getData('part_number'), $this->getData('label'));
            }
        }
    }

    public function onSavPartsChange()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $not_invoiced = $this->checkIsNotInvoiced();
            if ($not_invoiced !== (int) $this->getInitData('not_invoiced') && $this->getPrice() < 30) {//POUR ajouter les vis (VOIR 011-00212) gratuitement mais pas le reste
                $this->set('not_invoiced', $not_invoiced);
                $part_warnings = array();
                $part_errors = $this->update($part_warnings);

                if (count($part_warnings)) {
                    $errors[] = BimpTools::getMsgFromArray($part_warnings, 'Erreur suite à la mise à jour du composant "' . $this->getData('part_number') . '"');
                }
                if (count($part_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($part_errors, 'Echec de la mise à jour du composant "' . $this->getData('part_number') . '"');
                }
            }
        }

        return $errors;
    }

    public function setPrice($new_price)
    {
        $type = $this->getData('price_type');

        switch ($type) {
            case 'EXCHANGE':
                $this->set('exchange_price', (float) $new_price);
                break;

            case 'STOCK':
                $this->set('stock_price', (float) $new_price);
                break;

            default:
                $priceOptions = $this->getData('price_options');
                if (isset($priceOptions[$type]['price'])) {
                    $priceOptions[$type]['price'] = (float) $new_price;
                    $this->set('price_options', $priceOptions);
                }
                break;
        }
    }

    // Actions: 

    public function actionAttributeToIssue($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Attribution au problème composant effectuée avec succès';

        if ($this->isLoaded($errors)) {
            $sav = $this->getParentInstance();
            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'ID du SAV absent';
            } else {
                $id_issue = (isset($data['id_issue']) ? (int) $data['id_issue'] : 0);

                if (!$id_issue) {
                    $errors[] = 'Aucun problème composant sélectionné';
                } else {
                    $errors = $this->updateField('id_issue', $id_issue, null, true);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $this->checkPrice(true);

        if (BimpCore::getConf('use_gsx_v2', null, 'bimpapple')) {
            $is_tier = 0;
            if ((int) $this->getData('id_issue')) {
                $issue = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_Issue', (int) $this->getData('id_issue'));
                if (BimpObject::objectLoaded($issue)) {
                    $is_tier = (int) $issue->isTierPart();
                }
            }
            $this->set('is_tier', $is_tier);
            $this->set('component_code', '');
            $this->set('comptia_code', '');
            $this->set('comptia_modifier', '');
            $this->set('not_invoiced', (int) $this->checkIsNotInvoiced());
        } else {
            if ($this->getData('component_code') === ' ') {
                $this->set('component_code', 0);
                $this->set('comptia_code', '000');
                $this->set('is_tier', 0);
                $this->set('not_invoiced', 0);
            }
        }

        return parent::validate();
    }

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

                    $type = self::getCategProdApple($this->getData('part_number'), $this->getData('label'));

                    $line->desc = $label;
                    $line->qty = 1; //(int) $this->getData('qty');
                    $line->tva_tx = 20;

                    $this->setPropalLinePrices($line);

                    $line_warnings = array();
                    $line_errors = $line->create($line_warnings, true);

                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la création de la ligne du devis');
                    }

                    if ($type == "ecran" && isset(self::$tabRefCommencePrixEcran[$this->getData('part_number')][1])) {
                        $lineT = BimpObject::getInstance("bimpsupport", "BS_SavPropalLine");
                        $lineT->id_product = self::$tabRefCommencePrixEcran[$this->getData('part_number')][1];
                        $values = array("type" => 1, "id_obj" => $sav->getData('id_propal'));
                        $errorsT = $lineT->validateArray($values);
                        if (!count($errorsT)) {
                            $errorsT = $lineT->create();
                        }

                        if (count($errorsT)) {
                            $warnings[] = BimpTools::getMsgFromArray($errorsT);
                        }
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

                        $remise_warnings = array();
                        $remise_errors = $remise->create($remise_warnings, true);
                        if (count($remise_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise client par défaut');
                        }
                        if (count($remise_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($remise_warnings, 'Erreurs suite à la création de la remise client par défaut');
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            foreach (self::$refLiee as $data) {
//                echo '<pre>';print_r($data);
                foreach ($data[0] as $ref) {
                    if ($this->getData('part_number') == $ref) {
                        foreach ($data[1] as $refAJ => $libAj) {
                            $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name);
                            $obj->validateArray(array('part_number' => $refAJ, 'id_sav' => $this->getData('id_sav'), 'id_issue' => $this->getData('id_issue'), 'label' => $libAj . '. Pour : ' . $ref));
                            $errors = BimpTools::merge_array($errors, $obj->create());
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
                    $line->desc = $label;
                    $line->qty = 1; //(int) $this->getData('qty');
                    $line->tva_tx = 20;

                    $this->setPropalLinePrices($line);

                    $line_warnings = array();

                    if ($id_line) {
                        $line_errors = $line->update($line_warnings, true);
                    } else {
                        $line_errors = $line->create($line_warnings, true);
                    }

                    if (count($line_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreur suite à la ' . ($id_line ? 'mise à jour' : 'création') . ' de la ligne du devis');
                    }
                }
            }

            if (count($line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la ' . ($id_line ? 'mise à jour' : 'création') . ' de la ligne du devis');
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
                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs suite à la suppression de la ligne du devis');
                    }
                }
            }
        }

        return $errors;
    }
}
