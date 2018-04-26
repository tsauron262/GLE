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

    public static function getCompTIACodes(&$errors = array())
    {
        if (is_null(self::$compTIACodes)) {
            self::$compTIACodes = array(
                'grps' => array(),
                'mods' => array()
            );
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';
            $gsx = new GSX();
            if ($gsx->connect) {
                $data = $gsx->obtainCompTIA();
                $check = true;
                if (isset($data['ComptiaCodeLookupResponse']['comptiaInfo']) && count($data['ComptiaCodeLookupResponse']['comptiaInfo'])) {
                    $data = $data['ComptiaCodeLookupResponse']['comptiaInfo'];

                    if (isset($data['comptiaGroup']) && count($data['comptiaGroup'])) {
                        foreach ($data['comptiaGroup'] as $i => $group) {
                            self::$compTIACodes['grps'][$group['componentId']] = array();
                            if ($i === 0) {
                                self::$compTIACodes['grps'][$group['componentId']]['000'] = '000 - Non applicable';
                            } elseif (isset($group['comptiaCodeInfo']) && count($group['comptiaCodeInfo'])) {
                                foreach ($group['comptiaCodeInfo'] as $codeInfo) {
                                    self::$compTIACodes['grps'][$group['componentId']][$codeInfo['comptiaCode']] = $codeInfo['comptiaCode'] . ' - ' . $codeInfo['comptiaDescription'];
                                }
                            }
                        }
                    } else {
                        $check = false;
                    }
                }

                if (isset($data['comptiaModifier']) && count($data['comptiaModifier'])) {
                    foreach ($data['comptiaModifier'] as $mod) {
                        self::$compTIACodes['mods'][$mod['modifierCode']] = $mod['modifierCode'] . ' - ' . $mod['comptiaDescription'];
                    }
                } else {
                    $check = false;
                }

                if (!$check) {
                    $errors[] = 'Echec de la récupération des codes CompTIA';
                }
            } else {
                $errors[] = 'Echec de la connexion GSX';
            }
        }

        return self::$compTIACodes;
    }

    public function getComptia_codesArray()
    {
        $codes = self::getCompTIACodes();

        $compTIACodes = array();

        $group = $this->getData('component_code');

        if ($group !== 0) {
            $compTIACodes[''] = '';
        }

        if (isset($codes['grps'][$group])) {
            foreach ($codes['grps'][$group] as $value => $label) {
                $compTIACodes[$value] = $label;
            }
        }

        return $compTIACodes;
    }

    public function getComptia_modifiersArray()
    {
        $codes = self::getCompTIACodes();

        if (isset($codes['mods'])) {
            foreach ($codes['mods'] as $value => $label) {
                $compTIAMods[$value] = $label;
            }
        }

        return $compTIAMods;
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
