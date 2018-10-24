<?php

class BS_ApplePart extends BimpObject
{ 
    private static $tabRefCommenceIos = array("661-05511", "DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "XG", "ZD661", "ZK661", "ZP661");
    private static $tabDescCommenceIos = array("SVC,IPOD", "Ipod nano");

    private static $tabRefCommenceIosDouble = array("661", "Z661");
    private static $tabDescCommenceIosDouble = array("iphone", "BAT,IPHONE", "SVC,IPHONE"); //design commence par
    private static $tabDescContientIosDouble = array("Ipad Pro", "Ipad mini", "Apple Watc"); //design contient

    private static $tabRefCommenceBatterie = array("661-02909", "661-04479", "661-04579", "661-04580", "661-04581", "661-04582", "661-05421", "661-05755", "661-08935"); //Prix a 29

    private static $tabRefCommencePrixEcran = array("661-07285" => "142,58", "661-07286" => "142,58", "661-07287" => "142,58", "661-07288" => "142,58", "661-07289" => "159,25", "661-07290" => "159,25", "661-07291" => "159,25", "661-07292" => "159,25", "661-07293" => "142,58", "661-07294" => "142,58", "661-07295" => "142,58", "661-07296" => "142,58", "661-07297" => "159,25", "661-07298" => "159,25", "661-07299" => "159,25", "661-07300" => "159,25", "661-08933" => "142,58", "661-08934" => "142,58", "661-09081" => "142,58", "661-10102" => "142,58", "661-09032" => "159,25", "661-09033" => "159,25", "661-09034" => "159,25", "661-10103" => "159,25", "661-09294" => "259,25", "661-10608" => "259,25", "661-11037" => "300,91");


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

    public function canDelete()
    {
        global $user;
        return (int) $user->rights->BimpSupport->read;
    }

    public function getComptia_modifiersArray()
    {
        BimpObject::loadClass('bimpapple', 'GSX_CompTIA');
        return GSX_CompTIA::getCompTIAModifiers();
    }

    public static function getCategProdApple($ref, $desc){
        $type = "autre";
        
        
        //Premier cas les ios
        foreach (self::$tabDescCommenceIos as $val)//desc commence par rajout 45€
            if (stripos($desc, $val) === 0)
                $type = "ios";
        foreach (self::$tabRefCommenceIos as $val)//ref commence par rajout 45€
            if (stripos($ref, $val) === 0)
                $type = "ios";
        foreach (self::$tabRefCommenceIosDouble as $val){//ref commence par rajout 45€
            if (stripos($ref, $val) === 0){
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
            
        
        //troisieme cas les ecran
        
        foreach (self::$tabRefCommencePrixEcran as $val => $inut)
            if (stripos($ref, $val) === 0) 
                    $type = "ecran";
            
        return $type;
    }

    public static function convertPrix($prix, $ref, $desc)
    {
        $coefPrix = 1;
        $constPrix = 0;
        $newPrix = 0;
        
        $type = self::getCategProdApple($ref, $desc);

        
        //ou batterie
        //ou ecran
        // ou  ios cas 1
        //ou autre
        
        
        $cas = 0;

        //Application des coef et constantes
        if ($type == "ios") {
            $constPrix = 45;
        } elseif($type == "batt"){
            $newPrix = 32.5;
        } 
        elseif($type == "ecran"){
            foreach(self::$tabRefCommencePrixEcran as $refT => $prixT)
                if($ref == $refT)
                    $newPrix = str_replace(",",".", $prixT);
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

//        if (($cas == 1) && $this->fraisP < 1)
//            $this->fraisP = 0;
//        else
//            $this->fraisP = 1;

        return $prix;
    }

    public function isCartEditable()
    {
        $sav = $this->getParentInstance();
        if (!is_null($sav) && $sav->isLoaded()) {
            return (int) $sav->isPropalEditable();
        }
        return 0;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        $errors = parent::create($warnings, $force_create);

        $line_errors = array();
        if (!count($errors)) {
            $sav = $this->getParentInstance();
            if (BimpObject::objectLoaded($sav)) {
                if ((int) $sav->getData('id_propal')) {
                    $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                    $line_errors = $line->validateArray(array(
                        'id_obj'             => (int) $sav->getData('id_propal'),
                        'type'               => BS_SavPropalLine::LINE_FREE,
                        'deletable'          => 0,
                        'editable'           => 0,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'sav_apple_part',
                        'out_of_warranty'    => (int) $this->getData('out_of_warranty'),
                    ));
                    if (!count($line_errors)) {
                        $label = $this->getData('part_number') . ' - ' . $this->getData('label');
                        if ((int) $this->getData('no_order')) {
                            $label .= ' APPRO';
                        }
                        $line->pa_ht = ((int) $this->getData('no_order') || ($this->getData('exchange_price') < 1)) ? (float) $this->getData('stock_price') : (float) $this->getData('exchange_price');
                        $line->desc = $label;
                        $line->qty = (int) $this->getData('qty');
                        $line->tva_tx = 20;
                        $line->pu_ht = self::convertPrix($line->pa_ht, $this->getData('part_number'), $this->getData('label'));

                        $line_warnings = array();
                        $line_errors = $line->create($line_warnings, true);

                        if (count($line_warnings)) {
                            $line_errors = array_merge($line_errors, $line_warnings);
                        }
                    }
                } else {
                    $line_errors[] = 'ID de la propal absent';
                }
            } else {
                $line_errors[] = 'ID du SAV absent';
            }
        }

        if (count($line_errors)) {
            $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la création de la ligne du devis');
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $line_errors = array();
            $sav = $this->getParentInstance();
            $id_line = 0;
            if (BimpObject::objectLoaded($sav)) {
                if ((int) $sav->getData('id_propal')) {
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
                    ));

                    if (!count($line_errors)) {
                        $label = $this->getData('part_number') . ' - ' . $this->getData('label');
                        if ((int) $this->getData('no_order')) {
                            $label .= ' APPRO';
                        }
                        $line->pa_ht = ((int) $this->getData('no_order') || ($this->getData('exchange_price') < 1)) ? (float) $this->getData('stock_price') : (float) $this->getData('exchange_price');
                        $line->desc = $this->getData('label');
                        $line->qty = (int) $this->getData('qty');
                        $line->tva_tx = 20;
                        $line->pu_ht = self::convertPrix($line->pa_ht, $this->getData('part_number'), $this->getData('label'));

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
                } else {
                    $line_errors[] = 'ID de la propal absent';
                }
            } else {
                $line_errors[] = 'ID du SAV absent';
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

    public function delete($force_delete = false)
    {
        $sav = $this->getParentInstance();

        if (!BimpObject::objectLoaded($sav)) {
            return array('ID du SAV absent');
        }

        $id = (int) $this->id;

        $errors = parent::delete($force_delete);

        if (!count($errors)) {
            if ((int) $sav->getData('id_propal')) {
                $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');
                if ($line->find(array(
                            'id_obj'             => (int) $sav->getData('id_propal'),
                            'linked_id_object'   => (int) $id,
                            'linked_object_name' => 'sav_apple_part'
                        ))) {
                    $line_errors = $line->delete(true);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Des erreurs sont survenues lors de la suppression de la ligne du devis');
                    }
                }
            }
        }

        return $errors;
    }
}
