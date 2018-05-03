<?php

class BS_ApplePart extends BimpObject
{

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

    public function getComptia_modifiersArray()
    {
        BimpObject::loadClass('bimpapple', 'GSX_CompTIA');
        return GSX_CompTIA::getCompTIAModifiers();
    }

    public static function convertPrix($prix, $ref, $desc) {
        $coefPrix = 1;
        $constPrix = 0;
        $newPrix = 0;
        $tabCas1 = array("DN661", "FD661", "NF661", "RA", "RB", "RC", "RD", "RE", "RG", "SA", "SB", "SC", "SD", "SE", "X661", "XB", "XC", "XD", "XE", "XF", "XG", "ZD661", "ZK661", "ZP661");
        $tabCas2 = array("SVC,IPOD", "Ipod nano");
        $tabCas3 = array("661", "Z661");
        $tabCas35 = array("iphone", "BAT,IPHONE", "SVC,IPHONE");//design commence par
        $tabCas36 = array("Ipad Pro", "Ipad mini", "Apple Watc");//design contient
        $tabCas9 = array("661-02909", "661-04479","661-04579","661-04580","661-04581","661-04582","661-05421","661-05755");//Prix a 29

        $cas = 0;
        foreach ($tabCas1 as $val)
            if (stripos($ref, $val) === 0)
                $cas = 1;
        foreach ($tabCas2 as $val)
            if (stripos($desc, $val) === 0)
                $cas = 1;
        foreach ($tabCas3 as $val)
            if (stripos($ref, $val) === 0)
                $cas = 3;
            
            
        //Application double contrainte    
        if ($cas == 3){ 
            foreach ($tabCas35 as $val)
                if (stripos($desc, $val) === 0)
                    $cas = 1;
            foreach ($tabCas36 as $val)
                if (stripos($desc, $val) !== false)
                    $cas = 1;
        }

        //Application des coef et constantes
        if ($cas == 1) {
            $constPrix = 45;
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
        
        foreach ($tabCas9 as $val){
            if (stripos($ref, $val) === 0){
                $coefPrix = 1;
                $constPrix = 28.15;
            }
        }
        
        if($newPrix > 0)
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

    public function create()
    {
        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        return parent::create();
    }

    public function update()
    {
        if ($this->getData('component_code') === ' ') {
            $this->set('component_code', 0);
            $this->set('comptia_code', '000');
        }

        return parent::update();
    }
}
