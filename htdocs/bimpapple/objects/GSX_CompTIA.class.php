<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';

class GSX_CompTIA extends BimpObject
{

    public static $codes = null;
    public static $mods = null;

    public static function updateCodesFromGSX()
    {
        $gsx = new GSX();
        $error_msg = 'Echec de la récupération des codes CompTIA';
        if (!$gsx->connect) {
            dol_syslog($error_msg . ' - Echec de la connexion GSX', LOG_ERR);
        } else {
            $compTIACodes = array(
                'grps' => array(),
                'mods' => array()
            );
            $check = true;
            $data = $gsx->obtainCompTIA();
            if (isset($data['ComptiaCodeLookupResponse']['comptiaInfo']) && count($data['ComptiaCodeLookupResponse']['comptiaInfo'])) {
                $data = $data['ComptiaCodeLookupResponse']['comptiaInfo'];

                if (isset($data['comptiaGroup']) && count($data['comptiaGroup'])) {
                    foreach ($data['comptiaGroup'] as $i => $group) {
                        $compTIACodes['grps'][$group['componentId']] = array();
                        if ($i === 0) {
                            $compTIACodes['grps'][$group['componentId']]['000'] = 'Non applicable';
                        } elseif (isset($group['comptiaCodeInfo']) && count($group['comptiaCodeInfo'])) {
                            foreach ($group['comptiaCodeInfo'] as $codeInfo) {
                                $compTIACodes['grps'][$group['componentId']][$codeInfo['comptiaCode']] = $codeInfo['comptiaDescription'];
                            }
                        }
                    }
                } else {
                    $check = false;
                }
            }

            if (isset($data['comptiaModifier']) && count($data['comptiaModifier'])) {
                foreach ($data['comptiaModifier'] as $mod) {
                    $compTIACodes['mods'][$mod['modifierCode']] = $mod['comptiaDescription'];
                }
            } else {
                $check = false;
            }
            if (!$check) {
                dol_syslog($error_msg . '<pre>' . print_r($gsx->errors, 1) . '</pre>', LOG_ERR);
            } else {
                global $db;
                $bdb = new BimpDb($db);
                $bdb->execute('TRUNCATE ' . MAIN_DB_PREFIX . 'gsx_comptia');

                foreach ($compTIACodes['grps'] as $group => $codes) {
                    foreach ($codes as $code => $label) {
                        $bdb->insert('gsx_comptia', array(
                            'type'  => 'code',
                            'grp'   => $group,
                            'code'  => $code,
                            'label' => $label
                        ));
                    }
                }

                foreach ($compTIACodes['mods'] as $code => $label) {
                    $bdb->insert('gsx_comptia', array(
                        'type'  => 'mod',
                        'code'  => $code,
                        'label' => $label
                    ));
                }
            }
        }
    }

    public static function fetchCodes()
    {
        $intance = BimpObject::getInstance('bimpapple', 'GSX_CompTIA');
        $list = $intance->getList();

        self::$codes = array();
        self::$mods = array();

        if (is_null($list) || !count($list)) {
            self::updateCodesFromGSX();
            $list = $intance->getList();
        }
        
        if (!is_null($list)) {
            foreach ($list as $item) {
                switch ($item['type']) {
                    case 'code':
                        if (!isset(self::$codes[(string) $item['grp']])) {
                            self::$codes[(string) $item['grp']] = array();
                        }
                        self::$codes[(string) $item['grp']][(string) $item['code']] = $item['code'] . ' - ' . $item['label'];
                        break;

                    case 'mod':
                        self::$mods[(string) $item['code']] = $item['code'] . ' - ' . $item['label'];
                        break;
                }
            }
        }
    }

    public static function getCompTIACodes($group = null)
    {
        if (is_null(self::$codes)) {
            self::fetchCodes();
        }

        if (!is_null($group)) {
            if (isset(self::$codes[$group])) {
                return self::$codes[$group];
            }
            return array();
        }

        return self::$codes;
    }

    public static function getCompTIAModifiers()
    {
        if (is_null(self::$mods)) {
            self::fetchCodes();
        }

        return self::$mods;
    }
}
